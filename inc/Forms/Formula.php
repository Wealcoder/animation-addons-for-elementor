<?php
/**
 * AAE Forms — safe arithmetic formula evaluator for Calculation fields.
 *
 * Deliberately NOT eval(): a builder-authored formula is untrusted input that
 * ends up running on the server for every submit. This is a hand-written
 * tokenizer + shunting-yard parser supporting exactly:
 *
 *   numbers, {field_key} references, + - * / %, unary minus, parentheses,
 *   and the functions round/floor/ceil/min/max/abs (comma-separated args).
 *
 * Anything else (names, quotes, assignment, semicolons) fails to parse and
 * returns null — the caller then treats the field as "no computable value"
 * rather than guessing.
 *
 * The JS mirror is assets/js/lib/calculation.js. THE TWO MUST AGREE: the
 * server re-computes every Calculation field from the posted values and
 * rejects a submission whose client-sent total doesn't match, so a divergence
 * between the two implementations would reject honest submissions. Keep the
 * operator table, precedence, division-by-zero rule and rounding identical
 * when changing either file.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Formula {

	/** Hard cap on formula length — a sane ceiling, not a real-world limit. */
	const MAX_LENGTH = 1000;

	/** operator => [ precedence, right_associative ] */
	const OPERATORS = [
		'+' => [ 1, false ],
		'-' => [ 1, false ],
		'*' => [ 2, false ],
		'/' => [ 2, false ],
		'%' => [ 2, false ],
	];

	const FUNCTIONS = [
		'round' => [ 1, 2 ], // [min args, max args] — round(x) or round(x, decimals)
		'floor' => [ 1, 1 ],
		'ceil'  => [ 1, 1 ],
		'abs'   => [ 1, 1 ],
		'min'   => [ 2, 8 ],
		'max'   => [ 2, 8 ],
		// Whole days from the first date to the second (later − earlier, so a
		// backwards range reads negative). Operands must be date-ish; anything
		// else makes the whole formula uncomputable rather than silently 0.
		'days_between' => [ 2, 2 ],
	];

	/**
	 * Functions whose arguments are DATES, not numbers. Their operands keep
	 * the raw posted string alongside the numeric reading (see tokenize()),
	 * because "2026-09-04" has no meaningful numeric value — number_of()
	 * would read it as 1, and `{check_out} - {check_in}` would be 0.
	 */
	const DATE_FUNCTIONS = [ 'days_between' ];

	/**
	 * Evaluate a formula against a map of field values.
	 *
	 * @param string $formula e.g. "{qty} * {price} * 1.15"
	 * @param array  $values  field key => scalar (non-numeric reads as 0).
	 *
	 * @return float|null null when the formula can't be parsed or evaluated
	 *                    (syntax error, unknown function, division by zero).
	 */
	public static function evaluate( string $formula, array $values ): ?float {
		if ( '' === trim( $formula ) || strlen( $formula ) > self::MAX_LENGTH ) {
			return null;
		}

		$tokens = self::tokenize( $formula, $values );
		if ( null === $tokens ) {
			return null;
		}

		$rpn = self::to_rpn( $tokens );
		if ( null === $rpn ) {
			return null;
		}

		return self::eval_rpn( $rpn );
	}

	/**
	 * Every {field_key} referenced by a formula — used by the runtime to know
	 * which inputs to watch, and by the schema to record dependencies.
	 *
	 * @return string[]
	 */
	public static function referenced_fields( string $formula ): array {
		if ( ! preg_match_all( '/\{([^{}]{1,128})\}/', $formula, $matches ) ) {
			return [];
		}

		return array_values( array_unique( array_map( 'trim', $matches[1] ) ) );
	}

	/**
	 * A posted number as the runtime would read it: empty/non-numeric is 0, so
	 * a half-filled form still totals instead of going blank. Mirrors
	 * calculation.js's numberOf().
	 */
	public static function number_of( $value ): float {
		// A multi-value field (multi-select posts name[]) SUMS its picks —
		// the only sensible numeric reading of "several values at once", and
		// what makes `{addons[]}` work as an add-on total. Mirrors
		// calculation.js's valuesOf(), which sums selectedOptions the same way.
		if ( is_array( $value ) ) {
			$total = 0.0;
			foreach ( $value as $item ) {
				$total += self::number_of( $item );
			}

			return $total;
		}

		if ( is_bool( $value ) ) {
			return $value ? 1.0 : 0.0;
		}

		if ( ! is_scalar( $value ) ) {
			return 0.0;
		}

		$value = trim( (string) $value );

		// A checkbox posts its own value string ("on" by default) — anything
		// non-numeric but present counts as 1, so `{consent} * 50` works.
		if ( '' === $value ) {
			return 0.0;
		}

		return is_numeric( $value ) ? (float) $value : 1.0;
	}

	/**
	 * Format a computed value for display/storage: fixed decimals, no
	 * thousands separators (the stored value must stay machine-readable —
	 * prefix/suffix are display-only and live on the widget, not here).
	 */
	public static function format( float $value, int $decimals ): string {
		$decimals = max( 0, min( 6, $decimals ) );

		return number_format( $value, $decimals, '.', '' );
	}

	/**
	 * Whole days from $from to $to (later − earlier, so a backwards range is
	 * negative). Accepts the ISO date a native <input type="date"> posts
	 * ("YYYY-MM-DD"), optionally with a time part which is ignored — the
	 * question is "how many nights", not "how many hours".
	 *
	 * Deliberately date-only arithmetic (no timestamps): counting whole days
	 * from Y-M-D avoids DST and timezone offsets shifting a night boundary,
	 * and lets the JS mirror produce identical results without either side
	 * knowing the site's timezone.
	 *
	 * @return float|null null when either side isn't a real calendar date.
	 */
	public static function days_between( string $from, string $to ): ?float {
		$a = self::date_parts( $from );
		$b = self::date_parts( $to );

		if ( null === $a || null === $b ) {
			return null;
		}

		return (float) ( self::day_number( $b ) - self::day_number( $a ) );
	}

	/** "YYYY-MM-DD…" → [y, m, d], or null when it isn't a real date. */
	private static function date_parts( string $value ): ?array {
		$value = trim( $value );

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $value, $m ) ) {
			return null;
		}

		$year  = (int) $m[1];
		$month = (int) $m[2];
		$day   = (int) $m[3];

		return checkdate( $month, $day, $year ) ? [ $year, $month, $day ] : null;
	}

	/**
	 * Days since a fixed epoch — the standard civil-from-days algorithm, so
	 * PHP and JS agree exactly without either using a Date/DateTime object.
	 *
	 * @param array $parts [ year, month, day ]
	 */
	private static function day_number( array $parts ): int {
		list( $y, $m, $d ) = $parts;

		$y -= $m <= 2 ? 1 : 0;
		$era = intdiv( $y >= 0 ? $y : $y - 399, 400 );
		$yoe = $y - $era * 400;                                       // [0, 399]
		$doy = intdiv( 153 * ( $m + ( $m > 2 ? -3 : 9 ) ) + 2, 5 ) + $d - 1; // [0, 365]
		$doe = $yoe * 365 + intdiv( $yoe, 4 ) - intdiv( $yoe, 100 ) + $doy;

		return $era * 146097 + $doe - 719468;
	}

	/* ------------------------------------------------------------------ */
	/* Tokenizer                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array|null List of [type, value] tokens, or null on a bad char.
	 *                    Types: num, op, lparen, rparen, comma, func.
	 */
	private static function tokenize( string $formula, array $values ): ?array {
		$tokens = [];
		$len    = strlen( $formula );
		$i      = 0;

		while ( $i < $len ) {
			$char = $formula[ $i ];

			if ( ' ' === $char || "\t" === $char || "\n" === $char || "\r" === $char ) {
				$i++;
				continue;
			}

			// {field_key} → its posted value as a number.
			if ( '{' === $char ) {
				$close = strpos( $formula, '}', $i );
				if ( false === $close ) {
					return null;
				}
				$key = trim( substr( $formula, $i + 1, $close - $i - 1 ) );

				// A multi-select's DOM name carries the PHP array suffix
				// (`addons[]`) while the schema keys it without one
				// (`addons`). Accept either spelling so the same formula
				// resolves identically here and in the browser — mirrors
				// calculation.js's valuesOf(), which registers both.
				$raw = $values[ $key ] ?? null;
				if ( null === $raw && str_ends_with( $key, '[]' ) ) {
					$raw = $values[ substr( $key, 0, -2 ) ] ?? null;
				}

				// Third slot keeps the RAW value: date functions need the
				// original "2026-09-04" string, which has no numeric reading.
				$tokens[] = [ 'num', self::number_of( $raw ), is_scalar( $raw ) ? (string) $raw : '' ];
				$i        = $close + 1;
				continue;
			}

			if ( ctype_digit( $char ) || ( '.' === $char && $i + 1 < $len && ctype_digit( $formula[ $i + 1 ] ) ) ) {
				$start = $i;
				while ( $i < $len && ( ctype_digit( $formula[ $i ] ) || '.' === $formula[ $i ] ) ) {
					$i++;
				}
				$number = substr( $formula, $start, $i - $start );
				if ( ! is_numeric( $number ) ) {
					return null; // e.g. "1.2.3"
				}
				$tokens[] = [ 'num', (float) $number ];
				continue;
			}

			if ( isset( self::OPERATORS[ $char ] ) ) {
				// Unary minus/plus: at the start, or right after an operator or
				// an opening paren/comma. Rewritten as (0 - x) via a marker.
				$prev = end( $tokens );
				if ( ( '-' === $char || '+' === $char )
					&& ( false === $prev || in_array( $prev[0], [ 'op', 'lparen', 'comma' ], true ) ) ) {
					$tokens[] = [ 'num', 0.0 ];
				}
				$tokens[] = [ 'op', $char ];
				$i++;
				continue;
			}

			if ( '(' === $char ) {
				$tokens[] = [ 'lparen', '(' ];
				$i++;
				continue;
			}

			if ( ')' === $char ) {
				$tokens[] = [ 'rparen', ')' ];
				$i++;
				continue;
			}

			if ( ',' === $char ) {
				$tokens[] = [ 'comma', ',' ];
				$i++;
				continue;
			}

			// A bare name — only valid as a whitelisted function immediately
			// followed by '('.
			if ( ctype_alpha( $char ) ) {
				$start = $i;
				while ( $i < $len && ( ctype_alnum( $formula[ $i ] ) || '_' === $formula[ $i ] ) ) {
					$i++;
				}
				$name = strtolower( substr( $formula, $start, $i - $start ) );

				if ( ! isset( self::FUNCTIONS[ $name ] ) ) {
					return null;
				}

				// Skip whitespace between the name and its '('.
				$j = $i;
				while ( $j < $len && ' ' === $formula[ $j ] ) {
					$j++;
				}
				if ( $j >= $len || '(' !== $formula[ $j ] ) {
					return null;
				}

				$tokens[] = [ 'func', $name ];
				continue;
			}

			return null; // anything else is a syntax error.
		}

		return $tokens;
	}

	/* ------------------------------------------------------------------ */
	/* Shunting-yard → RPN                                                 */
	/* ------------------------------------------------------------------ */

	private static function to_rpn( array $tokens ): ?array {
		$output    = [];
		$stack     = [];
		$arg_count = []; // parallel stack: args seen for each pending function.

		foreach ( $tokens as $token ) {
			list( $type, $value ) = $token;

			if ( 'num' === $type ) {
				$output[] = $token;
				continue;
			}

			if ( 'func' === $type ) {
				$stack[]     = $token;
				$arg_count[] = 1;
				continue;
			}

			if ( 'comma' === $type ) {
				while ( ! empty( $stack ) && 'lparen' !== end( $stack )[0] ) {
					$output[] = array_pop( $stack );
				}
				if ( empty( $stack ) || empty( $arg_count ) ) {
					return null; // comma outside a function call.
				}
				$arg_count[ count( $arg_count ) - 1 ]++;
				continue;
			}

			if ( 'op' === $type ) {
				list( $precedence, $right_assoc ) = self::OPERATORS[ $value ];

				while ( ! empty( $stack ) ) {
					$top = end( $stack );
					if ( 'op' !== $top[0] ) {
						break;
					}
					$top_precedence = self::OPERATORS[ $top[1] ][0];
					if ( $top_precedence > $precedence || ( $top_precedence === $precedence && ! $right_assoc ) ) {
						$output[] = array_pop( $stack );
						continue;
					}
					break;
				}

				$stack[] = $token;
				continue;
			}

			if ( 'lparen' === $type ) {
				$stack[] = $token;
				continue;
			}

			if ( 'rparen' === $type ) {
				$matched = false;
				while ( ! empty( $stack ) ) {
					$top = array_pop( $stack );
					if ( 'lparen' === $top[0] ) {
						$matched = true;
						break;
					}
					$output[] = $top;
				}
				if ( ! $matched ) {
					return null; // unbalanced parens.
				}

				// A function call closing: emit it with its arg count.
				if ( ! empty( $stack ) && 'func' === end( $stack )[0] ) {
					$func = array_pop( $stack );
					$args = (int) array_pop( $arg_count );

					list( $min_args, $max_args ) = self::FUNCTIONS[ $func[1] ];
					if ( $args < $min_args || $args > $max_args ) {
						return null;
					}

					$output[] = [ 'call', $func[1], $args ];
				}
				continue;
			}
		}

		while ( ! empty( $stack ) ) {
			$top = array_pop( $stack );
			if ( 'lparen' === $top[0] || 'func' === $top[0] ) {
				return null; // unbalanced.
			}
			$output[] = $top;
		}

		return $output;
	}

	/* ------------------------------------------------------------------ */
	/* RPN evaluation                                                      */
	/* ------------------------------------------------------------------ */

	private static function eval_rpn( array $rpn ): ?float {
		// Values on the stack are floats; `$raw` runs in parallel, holding the
		// original string for the slots that came straight from a {field} so
		// date functions can still see "2026-09-04". Any computed value has no
		// raw form and stores ''.
		$stack = [];
		$raw   = [];

		$push = function ( $value, string $raw_value = '' ) use ( &$stack, &$raw ) {
			$stack[] = $value;
			$raw[]   = $raw_value;
		};
		$pop = function () use ( &$stack, &$raw ) {
			array_pop( $raw );

			return array_pop( $stack );
		};

		foreach ( $rpn as $token ) {
			$type = $token[0];

			if ( 'num' === $type ) {
				$push( (float) $token[1], isset( $token[2] ) ? (string) $token[2] : '' );
				continue;
			}

			if ( 'op' === $type ) {
				if ( count( $stack ) < 2 ) {
					return null;
				}
				$right = $pop();
				$left  = $pop();

				switch ( $token[1] ) {
					case '+':
						$push( $left + $right );
						break;
					case '-':
						$push( $left - $right );
						break;
					case '*':
						$push( $left * $right );
						break;
					case '/':
						if ( 0.0 === $right ) {
							return null; // JS mirror returns null here too (not Infinity).
						}
						$push( $left / $right );
						break;
					case '%':
						if ( 0.0 === $right ) {
							return null;
						}
						$push( fmod( $left, $right ) );
						break;
					default:
						return null;
				}
				continue;
			}

			if ( 'call' === $type ) {
				$name = $token[1];
				$argc = (int) $token[2];

				if ( count( $stack ) < $argc ) {
					return null;
				}

				$args     = [];
				$raw_args = [];
				for ( $i = 0; $i < $argc; $i++ ) {
					array_unshift( $raw_args, end( $raw ) );
					array_unshift( $args, $pop() );
				}

				switch ( $name ) {
					case 'round':
						$decimals = isset( $args[1] ) ? (int) $args[1] : 0;
						$push( round( $args[0], max( 0, min( 6, $decimals ) ) ) );
						break;
					case 'floor':
						$push( floor( $args[0] ) );
						break;
					case 'ceil':
						$push( ceil( $args[0] ) );
						break;
					case 'abs':
						$push( abs( $args[0] ) );
						break;
					case 'min':
						$push( min( $args ) );
						break;
					case 'max':
						$push( max( $args ) );
						break;
					case 'days_between':
						$days = self::days_between( $raw_args[0] ?? '', $raw_args[1] ?? '' );
						if ( null === $days ) {
							return null; // not two real dates — uncomputable, not 0.
						}
						$push( $days );
						break;
					default:
						return null;
				}
				continue;
			}

			return null;
		}

		if ( 1 !== count( $stack ) ) {
			return null;
		}

		$result = $stack[0];

		return is_finite( $result ) ? (float) $result : null;
	}
}
