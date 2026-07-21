/**
 * AAE Form — Calculation field runtime.
 *
 * Recomputes each `[data-aae-calc]` field from the form's current values
 * whenever any input changes, writes the result into the field's hidden real
 * input (so it submits, stores and reaches emails/CSV like any other field)
 * and paints the formatted value into the visible display box.
 *
 * The evaluator below MIRRORS inc/Forms/Formula.php exactly — same operators,
 * precedence, unary-minus handling, function list, division-by-zero rule and
 * rounding. The server RE-COMPUTES every calculation from the posted values
 * and rejects a mismatch, so any divergence here would reject honest
 * submissions. Change one, change both.
 *
 * Loaded via a STATIC import from form.js but INVOKED only when a form
 * actually contains a calculation field — inert otherwise.
 *
 * No framework, no GSAP (MVP form constraint).
 */

const MAX_LENGTH = 1000;

const OPERATORS = {
	'+': { precedence: 1, rightAssoc: false },
	'-': { precedence: 1, rightAssoc: false },
	'*': { precedence: 2, rightAssoc: false },
	'/': { precedence: 2, rightAssoc: false },
	'%': { precedence: 2, rightAssoc: false },
};

const FUNCTIONS = {
	round: [ 1, 2 ],
	floor: [ 1, 1 ],
	ceil: [ 1, 1 ],
	abs: [ 1, 1 ],
	min: [ 2, 8 ],
	max: [ 2, 8 ],
	// Whole days from the first date to the second. Its operands are DATES,
	// so they read the raw posted string ("2026-09-04") rather than the
	// numeric reading — see the raw stack in evalRpn().
	days_between: [ 2, 2 ],
};

/** "YYYY-MM-DD…" → [y, m, d], or null when it isn't a real calendar date. */
function dateParts( value ) {
	const m = String( value ).trim().match( /^(\d{4})-(\d{2})-(\d{2})/ );
	if ( ! m ) {
		return null;
	}
	const year = Number( m[ 1 ] );
	const month = Number( m[ 2 ] );
	const day = Number( m[ 3 ] );

	if ( month < 1 || month > 12 || day < 1 ) {
		return null;
	}
	// Days in month, leap year included — mirrors PHP's checkdate().
	const leap = ( year % 4 === 0 && year % 100 !== 0 ) || year % 400 === 0;
	const lengths = [ 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];
	if ( day > lengths[ month - 1 ] ) {
		return null;
	}

	return [ year, month, day ];
}

/**
 * Days since a fixed epoch — the same civil-from-days algorithm as
 * Formula::day_number(), deliberately NOT a Date object: counting whole days
 * from Y-M-D keeps DST and timezone offsets from shifting a night boundary,
 * and lets both sides agree without knowing the site's timezone.
 */
function dayNumber( [ y, m, d ] ) {
	const year = y - ( m <= 2 ? 1 : 0 );
	const era = Math.floor( ( year >= 0 ? year : year - 399 ) / 400 );
	const yoe = year - era * 400;
	const doy = Math.floor( ( 153 * ( m + ( m > 2 ? -3 : 9 ) ) + 2 ) / 5 ) + d - 1;
	const doe = yoe * 365 + Math.floor( yoe / 4 ) - Math.floor( yoe / 100 ) + doy;

	return era * 146097 + doe - 719468;
}

/** Mirror of Formula::days_between(). null when either side isn't a date. */
export function daysBetween( from, to ) {
	const a = dateParts( from );
	const b = dateParts( to );

	return a && b ? dayNumber( b ) - dayNumber( a ) : null;
}

const isDigit = ( ch ) => ch >= '0' && ch <= '9';
const isAlpha = ( ch ) => /[a-z]/i.test( ch );
const isAlnum = ( ch ) => /[a-z0-9]/i.test( ch );

/** Mirror of Formula::number_of() — empty is 0, non-numeric-but-present is 1. */
export function numberOf( value ) {
	// A multi-value field (multi-select posts name[]) SUMS its picks — the
	// only sensible numeric reading of "several values at once", and what
	// makes `{addons[]}` work as an add-on total. valuesOf() below sums
	// selectedOptions the same way for the live DOM path.
	if ( Array.isArray( value ) ) {
		return value.reduce( ( sum, item ) => sum + numberOf( item ), 0 );
	}
	if ( typeof value === 'boolean' ) {
		return value ? 1 : 0;
	}
	if ( value === null || value === undefined ) {
		return 0;
	}
	const text = String( value ).trim();
	if ( text === '' ) {
		return 0;
	}
	const num = Number( text );
	return Number.isFinite( num ) ? num : 1;
}

/** Mirror of Formula::referenced_fields(). */
export function referencedFields( formula ) {
	const found = String( formula || '' ).match( /\{[^{}]{1,128}\}/g ) || [];
	return [ ...new Set( found.map( ( m ) => m.slice( 1, -1 ).trim() ) ) ];
}

/* ------------------------------------------------------------------ */
/* Tokenizer → RPN → evaluate (mirrors Formula.php)                     */
/* ------------------------------------------------------------------ */

function tokenize( formula, values ) {
	const tokens = [];
	const len = formula.length;
	let i = 0;

	while ( i < len ) {
		const ch = formula[ i ];

		if ( ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r' ) {
			i++;
			continue;
		}

		if ( ch === '{' ) {
			const close = formula.indexOf( '}', i );
			if ( close === -1 ) {
				return null;
			}
			const key = formula.slice( i + 1, close ).trim();
			// A multi-select's DOM name carries the PHP array suffix
			// (`addons[]`) while the schema keys it without one — accept
			// either spelling, same as Formula::tokenize().
			let raw = values[ key ];
			if ( raw === undefined && key.endsWith( '[]' ) ) {
				raw = values[ key.slice( 0, -2 ) ];
			}
			// Third slot keeps the RAW value: date functions need the original
			// "2026-09-04" string, which has no numeric reading.
			const rawText = raw === null || raw === undefined || Array.isArray( raw ) ? '' : String( raw );
			tokens.push( [ 'num', numberOf( raw ), rawText ] );
			i = close + 1;
			continue;
		}

		if ( isDigit( ch ) || ( ch === '.' && i + 1 < len && isDigit( formula[ i + 1 ] ) ) ) {
			const start = i;
			while ( i < len && ( isDigit( formula[ i ] ) || formula[ i ] === '.' ) ) {
				i++;
			}
			const text = formula.slice( start, i );
			const num = Number( text );
			if ( ! Number.isFinite( num ) ) {
				return null; // e.g. "1.2.3"
			}
			tokens.push( [ 'num', num ] );
			continue;
		}

		if ( OPERATORS[ ch ] ) {
			const prev = tokens[ tokens.length - 1 ];
			if (
				( ch === '-' || ch === '+' ) &&
				( ! prev || prev[ 0 ] === 'op' || prev[ 0 ] === 'lparen' || prev[ 0 ] === 'comma' )
			) {
				tokens.push( [ 'num', 0 ] ); // unary → (0 - x)
			}
			tokens.push( [ 'op', ch ] );
			i++;
			continue;
		}

		if ( ch === '(' ) {
			tokens.push( [ 'lparen', '(' ] );
			i++;
			continue;
		}
		if ( ch === ')' ) {
			tokens.push( [ 'rparen', ')' ] );
			i++;
			continue;
		}
		if ( ch === ',' ) {
			tokens.push( [ 'comma', ',' ] );
			i++;
			continue;
		}

		if ( isAlpha( ch ) ) {
			const start = i;
			while ( i < len && ( isAlnum( formula[ i ] ) || formula[ i ] === '_' ) ) {
				i++;
			}
			const name = formula.slice( start, i ).toLowerCase();
			if ( ! FUNCTIONS[ name ] ) {
				return null;
			}
			let j = i;
			while ( j < len && formula[ j ] === ' ' ) {
				j++;
			}
			if ( j >= len || formula[ j ] !== '(' ) {
				return null;
			}
			tokens.push( [ 'func', name ] );
			continue;
		}

		return null;
	}

	return tokens;
}

function toRpn( tokens ) {
	const output = [];
	const stack = [];
	const argCount = [];

	for ( const token of tokens ) {
		const [ type, value ] = token;

		if ( type === 'num' ) {
			output.push( token );
			continue;
		}

		if ( type === 'func' ) {
			stack.push( token );
			argCount.push( 1 );
			continue;
		}

		if ( type === 'comma' ) {
			while ( stack.length && stack[ stack.length - 1 ][ 0 ] !== 'lparen' ) {
				output.push( stack.pop() );
			}
			if ( ! stack.length || ! argCount.length ) {
				return null;
			}
			argCount[ argCount.length - 1 ]++;
			continue;
		}

		if ( type === 'op' ) {
			const { precedence, rightAssoc } = OPERATORS[ value ];
			while ( stack.length ) {
				const top = stack[ stack.length - 1 ];
				if ( top[ 0 ] !== 'op' ) {
					break;
				}
				const topPrecedence = OPERATORS[ top[ 1 ] ].precedence;
				if ( topPrecedence > precedence || ( topPrecedence === precedence && ! rightAssoc ) ) {
					output.push( stack.pop() );
					continue;
				}
				break;
			}
			stack.push( token );
			continue;
		}

		if ( type === 'lparen' ) {
			stack.push( token );
			continue;
		}

		if ( type === 'rparen' ) {
			let matched = false;
			while ( stack.length ) {
				const top = stack.pop();
				if ( top[ 0 ] === 'lparen' ) {
					matched = true;
					break;
				}
				output.push( top );
			}
			if ( ! matched ) {
				return null;
			}
			if ( stack.length && stack[ stack.length - 1 ][ 0 ] === 'func' ) {
				const func = stack.pop();
				const args = argCount.pop();
				const [ minArgs, maxArgs ] = FUNCTIONS[ func[ 1 ] ];
				if ( args < minArgs || args > maxArgs ) {
					return null;
				}
				output.push( [ 'call', func[ 1 ], args ] );
			}
		}
	}

	while ( stack.length ) {
		const top = stack.pop();
		if ( top[ 0 ] === 'lparen' || top[ 0 ] === 'func' ) {
			return null;
		}
		output.push( top );
	}

	return output;
}

function evalRpn( rpn ) {
	// Values are numbers; `raws` runs in parallel holding the original string
	// for slots that came straight from a {field}, so date functions can still
	// see "2026-09-04". Computed values have no raw form and store ''.
	const stack = [];
	const raws = [];
	const push = ( value, raw = '' ) => {
		stack.push( value );
		raws.push( raw );
	};
	const pop = () => {
		raws.pop();
		return stack.pop();
	};

	for ( const token of rpn ) {
		const type = token[ 0 ];

		if ( type === 'num' ) {
			push( token[ 1 ], token[ 2 ] ?? '' );
			continue;
		}

		if ( type === 'op' ) {
			if ( stack.length < 2 ) {
				return null;
			}
			const right = pop();
			const left = pop();
			switch ( token[ 1 ] ) {
				case '+':
					push( left + right );
					break;
				case '-':
					push( left - right );
					break;
				case '*':
					push( left * right );
					break;
				case '/':
					if ( right === 0 ) {
						return null;
					}
					push( left / right );
					break;
				case '%':
					if ( right === 0 ) {
						return null;
					}
					push( left % right );
					break;
				default:
					return null;
			}
			continue;
		}

		if ( type === 'call' ) {
			const name = token[ 1 ];
			const argc = token[ 2 ];
			if ( stack.length < argc ) {
				return null;
			}
			const rawArgs = raws.slice( raws.length - argc );
			const args = [];
			for ( let n = 0; n < argc; n++ ) {
				args.unshift( pop() );
			}

			switch ( name ) {
				case 'round': {
					const decimals = Math.max( 0, Math.min( 6, Math.trunc( args[ 1 ] ?? 0 ) ) );
					push( roundHalfUp( args[ 0 ], decimals ) );
					break;
				}
				case 'floor':
					push( Math.floor( args[ 0 ] ) );
					break;
				case 'ceil':
					push( Math.ceil( args[ 0 ] ) );
					break;
				case 'abs':
					push( Math.abs( args[ 0 ] ) );
					break;
				case 'min':
					push( Math.min( ...args ) );
					break;
				case 'max':
					push( Math.max( ...args ) );
					break;
				case 'days_between': {
					const days = daysBetween( rawArgs[ 0 ] ?? '', rawArgs[ 1 ] ?? '' );
					if ( days === null ) {
						return null; // not two real dates — uncomputable, not 0.
					}
					push( days );
					break;
				}
				default:
					return null;
			}
			continue;
		}

		return null;
	}

	if ( stack.length !== 1 ) {
		return null;
	}

	return Number.isFinite( stack[ 0 ] ) ? stack[ 0 ] : null;
}

/**
 * PHP's round() is half-away-from-zero; JS's Math.round() is half-up (so
 * -0.5 → -0 instead of -1) and both suffer binary representation drift
 * (1.005). Route through a string exponent shift so both sides agree.
 */
function roundHalfUp( value, decimals ) {
	if ( ! Number.isFinite( value ) ) {
		return value;
	}
	const sign = value < 0 ? -1 : 1;
	const shifted = Number( `${ Math.abs( value ) }e${ decimals }` );
	if ( ! Number.isFinite( shifted ) ) {
		return value;
	}
	return sign * Number( `${ Math.round( shifted ) }e-${ decimals }` );
}

/** Mirror of Formula::evaluate(). Returns null when uncomputable. */
export function evaluateFormula( formula, values ) {
	const text = String( formula || '' );
	if ( ! text.trim() || text.length > MAX_LENGTH ) {
		return null;
	}
	const tokens = tokenize( text, values );
	if ( ! tokens ) {
		return null;
	}
	const rpn = toRpn( tokens );
	if ( ! rpn ) {
		return null;
	}
	return evalRpn( rpn );
}

/** Mirror of Formula::format() — fixed decimals, no thousands separators. */
export function formatValue( value, decimals ) {
	const places = Math.max( 0, Math.min( 6, decimals ) );
	return roundHalfUp( value, places ).toFixed( places );
}

/* ------------------------------------------------------------------ */
/* Field wiring                                                        */
/* ------------------------------------------------------------------ */

/**
 * Current values of every named control in the form, keyed by submission
 * name. Conditionally-hidden fields (pro Conditional Display) read as
 * absent — a hidden field must not feed a total the visitor can't see.
 */
function valuesOf( form ) {
	const values = {};

	Array.from( form.elements || [] ).forEach( ( control ) => {
		if ( ! control.name || control.disabled || control.closest( '[data-aae-cond-hidden]' ) ) {
			return;
		}

		// A multi-select's DOM name carries the PHP array suffix (`addons[]`)
		// but the schema — and therefore the server-side formula evaluation —
		// keys it without one (`addons`). Normalise here so a builder writes
		// `{addons}` and it resolves identically in the browser and on the
		// server. Both spellings are registered: `{addons[]}` keeps working
		// for anyone who already wrote the raw DOM name.
		const name = control.name.endsWith( '[]' ) ? control.name.slice( 0, -2 ) : control.name;
		const rawName = control.name;

		if ( control.type === 'checkbox' ) {
			// Unchecked contributes nothing; checked contributes its value
			// ("on" → 1 via numberOf, or a real number if the builder set one).
			if ( control.checked ) {
				values[ name ] = control.value;
			}
			return;
		}

		if ( control.type === 'radio' ) {
			if ( control.checked ) {
				values[ name ] = control.value;
			}
			return;
		}

		if ( control.multiple && control.selectedOptions ) {
			// Multi-select sums its picked values — the only sensible numeric
			// reading of "several values at once". With NOTHING picked the key
			// stays absent rather than reading 0, so an untouched multi-select
			// can't make a total look "filled" (syncCalculations's placeholder
			// check treats a present-but-zero value as real input).
			const picked = Array.from( control.selectedOptions );
			if ( ! picked.length ) {
				return;
			}

			const total = picked.reduce( ( sum, option ) => sum + numberOf( option.value ), 0 );
			values[ name ] = total;
			values[ rawName ] = total;
			return;
		}

		values[ name ] = control.value;
	} );

	return values;
}

/** Recompute every calculation field in the form. */
export function syncCalculations( form ) {
	const fields = form.querySelectorAll( '[data-aae-calc]' );
	if ( ! fields.length ) {
		return;
	}

	const values = valuesOf( form );

	fields.forEach( ( wrap ) => {
		const input = wrap.querySelector( 'input.aae-a-form-calc-native' );
		const display = wrap.querySelector( '.aae-a-form-calc-display' );
		if ( ! input ) {
			return;
		}

		const formula = wrap.dataset.aaeCalc || '';
		const decimals = parseInt( wrap.dataset.aaeCalcDecimals, 10 ) || 0;

		// Untouched form: every referenced field is still empty, so the formula
		// would total 0 — showing "$0.00" reads as a real quote of nothing.
		// Hold the placeholder until the visitor has filled at least one of the
		// fields the formula actually reads. (The server has no equivalent case:
		// it only ever computes at submit time, and stores whatever the filled
		// form really totals.)
		// A chained calculation ({subtotal} → {project_total}) reads a value
		// this same pass just wrote, so an upstream calculation that resolved
		// to a real number counts as "filled" here too — that's why the check
		// runs against `values` (updated in place below), not the raw DOM.
		const referenced = referencedFields( formula );
		const anyFilled = referenced.some( ( key ) => {
			const raw = values[ key ];
			return raw !== undefined && raw !== null && String( raw ).trim() !== '';
		} );

		const result = anyFilled ? evaluateFormula( formula, values ) : null;

		if ( result === null ) {
			// Uncomputable (bad formula, division by zero) — submit nothing
			// rather than a wrong number; the server agrees, so an empty
			// value can't be mistaken for a real total.
			input.value = '';
			values[ input.name ] = '';
			if ( display ) {
				display.textContent = wrap.dataset.aaeCalcEmpty || '—';
			}
			return;
		}

		const formatted = formatValue( result, decimals );
		input.value = formatted;

		// Feed the result back into this pass's value map so a LATER
		// calculation can reference this one ({subtotal} → {rush_surcharge} →
		// {project_total}). Fields resolve in document order, so a formula may
		// only read calculations declared above it — which is also why a
		// circular reference is impossible. Validator.php does the same thing
		// server-side; the two must agree.
		values[ input.name ] = formatted;

		if ( display ) {
			const prefix = wrap.dataset.aaeCalcPrefix || '';
			const suffix = wrap.dataset.aaeCalcSuffix || '';
			display.textContent = `${ prefix }${ formatted }${ suffix }`;
		}
	} );
}

/**
 * Wire live recalculation for a form. Idempotent — a second call on the same
 * form only re-syncs (the editor re-renders forms freely; see multi-step.js's
 * resync reasoning).
 */
export function initCalculations( form ) {
	if ( ! form.querySelector( '[data-aae-calc]' ) ) {
		return;
	}

	if ( form.dataset.aaeCalcBound !== 'true' ) {
		form.dataset.aaeCalcBound = 'true';
		// One delegated pair on the form: any edit anywhere re-totals. Cheaper
		// and more robust than per-dependency listeners, and it survives fields
		// being added/removed by conditional display or a preset apply.
		form.addEventListener( 'input', () => syncCalculations( form ) );
		form.addEventListener( 'change', () => syncCalculations( form ) );
		form.addEventListener( 'reset', () => {
			// Native reset clears inputs AFTER the event, so re-sync next tick.
			setTimeout( () => syncCalculations( form ), 0 );
		} );
	}

	syncCalculations( form );
}
