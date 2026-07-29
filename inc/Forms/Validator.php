<?php
/**
 * AAE Forms — server-side field validation (Milestone 5).
 *
 * Validates a posted payload against the ACTIVE schema snapshot (never the
 * live document — spec pipeline step 5), repeating everything the frontend
 * checks: backend validation is authoritative, the frontend is UX only.
 *
 * Output is both the verdict and the sanitized values: `clean` contains
 * ONLY schema-known fields (unknown posted keys are dropped, not stored),
 * ready for Milestone 6 to persist as submission_values.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Validator {

	/** Hard caps, defense-in-depth against oversized payloads. */
	const MAX_SINGLE_LINE = 2000;
	const MAX_MULTI_LINE  = 20000;

	/**
	 * @param array $schema The active canonical schema (Schema_Walker::build shape).
	 * @param array $posted field key => scalar|array, as decoded from the request.
	 *
	 * @return array [ 'clean' => [key => string|string[]], 'errors' => [key => message] ]
	 */
	public static function validate( array $schema, array $posted ): array {
		$clean  = [];
		$errors = [];

		foreach ( self::group_fields( $schema['fields'] ?? [] ) as $key => $group ) {
			$field = $group['field'];

			/**
			 * Skip a field entirely (no validation, no storage). The pro
			 * Conditional Display engine hooks this to honor fields the
			 * visitor never saw — hidden fields must not block submit.
			 *
			 * @param bool  $skip   Default false.
			 * @param array $field  Schema field entry.
			 * @param array $posted Full posted payload.
			 * @param array $schema Active schema snapshot.
			 */
			if ( apply_filters( 'aae_form/validator/skip_field', false, $field, $posted, $schema ) ) {
				continue;
			}

			$value = $posted[ $key ] ?? null;

			switch ( $field['type'] ) {
				case 'checkbox':
					$checked = null !== $value && '' !== $value && false !== $value;

					if ( $field['required'] && ! $checked ) {
						$errors[ $key ] = self::msg_required( $field );
						break;
					}

					if ( $checked ) {
						$clean[ $key ] = sanitize_text_field( self::scalar( $value ) );
					}
					break;

				case 'radio':
					$value = null === $value ? '' : sanitize_text_field( self::scalar( $value ) );

					if ( '' === $value ) {
						if ( $group['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( ! empty( $group['allowed'] ) && ! in_array( $value, $group['allowed'], true ) ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$clean[ $key ] = $value;
					break;

				case 'country': // identical mechanics to a single select: required + options whitelist.
				case 'select':
					$allowed  = self::parse_options( (string) ( $field['options'] ?? '' ) );
					$multiple = ! empty( $field['multiple'] );

					$values = $multiple
						? array_map( 'sanitize_text_field', array_filter( (array) $value, 'is_scalar' ) )
						: array_filter( [ sanitize_text_field( self::scalar( $value ) ) ], 'strlen' );

					if ( empty( $values ) ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( ! empty( $allowed ) && array_diff( $values, $allowed ) ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$clean[ $key ] = $multiple ? array_values( $values ) : $values[0];
					break;

				case 'file':
					// Value is a list of pre-upload refs [{id,key},…] — the
					// files themselves were already validated and stored by
					// the uploads endpoint; here we verify the refs belong to
					// THIS form and are still unclaimed, then normalize to
					// [{id,name,size},…] for storage. Claiming happens after
					// the submission row exists (Uploads::claim_for_submission).
					$refs = self::file_refs( $value );

					if ( empty( $refs ) ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					$max_files = ! empty( $field['multiple'] ) ? max( 1, (int) ( $field['max_files'] ?? 0 ) ) : 1;
					if ( 0 === (int) ( $field['max_files'] ?? 0 ) && ! empty( $field['multiple'] ) ) {
						$max_files = 10; // sane ceiling when the field sets none.
					}

					if ( count( $refs ) > $max_files ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$verified = Uploads::verify_refs( $refs, (string) ( $schema['form_key'] ?? '' ) );

					if ( null === $verified ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$clean[ $key ] = $verified;
					break;

				case 'textarea':
					$value = sanitize_textarea_field( self::scalar( $value ) );

					if ( '' === trim( $value ) ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( mb_strlen( $value ) > self::MAX_MULTI_LINE ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$extra_error = self::extra_value_error( $value, $field, $posted, $schema );
					if ( null !== $extra_error ) {
						$errors[ $key ] = $extra_error;
						break;
					}

					$clean[ $key ] = $value;
					break;

				case 'range':
					// A native <input type="range"> always posts a value (its
					// own min/midpoint by default) — there is no "empty" state
					// and therefore no required check, same reasoning as Rating's
					// toggle-off-to-0 being the closest it gets to "unset".
					$value = sanitize_text_field( self::scalar( $value ) );

					if ( ! is_numeric( $value ) ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$range_error = self::check_range( (float) $value, $field );
					if ( null !== $range_error ) {
						$errors[ $key ] = $range_error;
						break;
					}

					$clean[ $key ] = $value;
					break;

				case 'password':
					// NOT sanitize_text_field: that strips characters that are
					// perfectly legal (and desirable) in a password. The value
					// is never rendered as HTML — it is either discarded or
					// one-way hashed by Rest.php before storage — so it only
					// needs to be a string, not display-safe.
					$value = is_scalar( $value ) ? (string) $value : '';

					if ( '' === $value ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( mb_strlen( $value ) > self::MAX_SINGLE_LINE ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$min_length = (int) ( $field['min_length'] ?? 0 );
					if ( $min_length > 0 && mb_strlen( $value ) < $min_length ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					// Confirm-match: this field must equal the field whose
					// _cssid it names. Re-checked here because the frontend
					// check alone is never trusted.
					$match_field = (string) ( $field['match_field'] ?? '' );
					if ( '' !== $match_field ) {
						$other_key = self::key_for_css_id( $match_field, $schema );
						$other_val = null !== $other_key && isset( $posted[ $other_key ] ) && is_scalar( $posted[ $other_key ] )
							? (string) $posted[ $other_key ]
							: '';

						if ( ! hash_equals( $other_val, $value ) ) {
							$mismatch = (string) ( $field['mismatch_message'] ?? '' );
							$errors[ $key ] = '' !== $mismatch
								? $mismatch
								: __( 'Passwords do not match.', 'animation-addons-for-elementor' );
							break;
						}
					}

					$clean[ $key ] = $value;
					break;

				case 'rating':
					$value = sanitize_text_field( self::scalar( $value ) );

					if ( '' === $value || '0' === $value ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( ! is_numeric( $value ) || (float) $value !== (float) (int) $value ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$range_error = self::check_range( (float) $value, $field );
					if ( null !== $range_error ) {
						$errors[ $key ] = $range_error;
						break;
					}

					$clean[ $key ] = $value;
					break;

				case 'calculation':
					// The posted value is DISCARDED — this field's value is
					// derived, so the server recomputes it from the other
					// posted values using the formula in the schema snapshot.
					// A visitor editing the hidden input in DevTools therefore
					// changes nothing about what gets stored or emailed.
					//
					// Deliberately never an error: an uncomputable formula
					// (empty inputs, division by zero, a typo the builder left
					// behind) stores nothing rather than blocking a submission
					// the visitor cannot fix.
					$computed = Formula::evaluate( (string) ( $field['formula'] ?? '' ), $posted );

					if ( null !== $computed ) {
						$formatted     = Formula::format( $computed, (int) ( $field['decimals'] ?? 2 ) );
						$clean[ $key ] = $formatted;

						// Feed the result back into the working payload so a
						// LATER calculation can reference this one
						// ({subtotal} → {rush_surcharge} → {project_total}).
						// Fields are grouped in document order, so a formula
						// may only read calculations declared above it — the
						// same order the frontend resolves them in, and the
						// reason a circular reference is impossible.
						$posted[ $key ] = $formatted;
					}
					break;

				default: // input family: text / email / number / tel / url / password.
					$value = sanitize_text_field( self::scalar( $value ) );

					if ( '' === $value ) {
						if ( $field['required'] ) {
							$errors[ $key ] = self::msg_required( $field );
						}
						break;
					}

					if ( mb_strlen( $value ) > self::MAX_SINGLE_LINE ) {
						$errors[ $key ] = self::msg_invalid( $field );
						break;
					}

					$format_error = self::check_format( $field['type'], $value, $field );
					if ( null !== $format_error ) {
						$errors[ $key ] = $format_error;
						break;
					}

					$extra_error = self::extra_value_error( $value, $field, $posted, $schema );
					if ( null !== $extra_error ) {
						$errors[ $key ] = $extra_error;
						break;
					}

					$clean[ $key ] = $value;
			}
		}

		return [
			'clean'  => $clean,
			'errors' => $errors,
		];
	}

	/**
	 * Extension point for value checks BEYOND the built-in ones — runs for
	 * text-like fields (input family + textarea) only after every built-in
	 * check passed. Mirrors the frontend's 'aae_form/validate/value_error'
	 * JS filter: backend validation must repeat whatever an extension
	 * enforces client-side (the pro Validation module's regex rules hook
	 * here).
	 */
	private static function extra_value_error( string $value, array $field, array $posted, array $schema ): ?string {
		/**
		 * Fail a value that passed the built-in checks. Return a message
		 * string to reject it, or null (the default) to accept.
		 *
		 * @param string|null $error  Default null (passes).
		 * @param string      $value  Sanitized posted value.
		 * @param array       $field  Schema field entry.
		 * @param array       $posted Full posted payload.
		 * @param array       $schema Active schema snapshot.
		 */
		$error = apply_filters( 'aae_form/validator/value_error', null, $value, $field, $posted, $schema );

		return is_string( $error ) && '' !== $error ? $error : null;
	}

	/** Per-type format checks; null means the value passes. */
	private static function check_format( string $type, string $value, array $field ): ?string {
		switch ( $type ) {
			case 'email':
				return is_email( $value ) ? null : self::msg_or( $field, __( 'Please enter a valid email address.', 'animation-addons-for-elementor' ) );

			case 'url':
				return false !== filter_var( $value, FILTER_VALIDATE_URL ) ? null : self::msg_or( $field, __( 'Please enter a valid URL.', 'animation-addons-for-elementor' ) );

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return self::msg_or( $field, __( 'Please enter a number.', 'animation-addons-for-elementor' ) );
				}
				return self::check_range( (float) $value, $field );

			case 'tel':
				// Basic-format-only per spec's Free tier (real validation is a Pro adapter).
				return preg_match( '/^\+?[0-9\-().\s]{3,30}$/', $value ) ? null : self::msg_or( $field, __( 'Please enter a valid phone number.', 'animation-addons-for-elementor' ) );

			case 'date':
				// Native <input type="date"> posts ISO 8601 (YYYY-MM-DD) regardless
				// of the visitor's locale display format.
				if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) || ! checkdate( (int) substr( $value, 5, 2 ), (int) substr( $value, 8, 2 ), (int) substr( $value, 0, 4 ) ) ) {
					return self::msg_or( $field, __( 'Please enter a valid date.', 'animation-addons-for-elementor' ) );
				}
				return self::check_date_range( $value, $field );

			case 'time':
				// Native <input type="time"> posts HH:MM (or HH:MM:SS with the
				// step attribute) in 24-hour form.
				return preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $value ) ? null : self::msg_or( $field, __( 'Please enter a valid time.', 'animation-addons-for-elementor' ) );
		}

		/**
		 * Extend format checking for a field type this switch doesn't handle
		 * (e.g. a Pro-added input type). Return a message string to fail
		 * validation, or null (the default) to pass.
		 *
		 * @param string|null $error Default null (passes).
		 * @param string      $type  The field's `type` value.
		 * @param string      $value Sanitized posted value.
		 * @param array       $field Schema field entry.
		 */
		return apply_filters( 'aae_form/validator/check_format', null, $type, $value, $field );
	}

	/**
	 * Number min/max from the schema snapshot — repeats the frontend's range
	 * check so a crafted request can't store an out-of-range value.
	 */
	private static function check_range( float $value, array $field ): ?string {
		$min = $field['min'] ?? '';
		$max = $field['max'] ?? '';

		if ( '' !== $min && is_numeric( $min ) && $value < (float) $min ) {
			/* translators: %s: minimum allowed value */
			return self::msg_or( $field, sprintf( __( 'Please enter a value of at least %s.', 'animation-addons-for-elementor' ), $min ) );
		}

		if ( '' !== $max && is_numeric( $max ) && $value > (float) $max ) {
			/* translators: %s: maximum allowed value */
			return self::msg_or( $field, sprintf( __( 'Please enter a value of at most %s.', 'animation-addons-for-elementor' ), $max ) );
		}

		return null;
	}

	/**
	 * Date min/max from the schema snapshot ("YYYY-MM-DD" strings, same as the
	 * native <input type="date"> min/max attributes) — compared as timestamps
	 * so "2026-01-01" < "2026-02-01" regardless of string sort quirks.
	 */
	private static function check_date_range( string $value, array $field ): ?string {
		$min = (string) ( $field['min'] ?? '' );
		$max = (string) ( $field['max'] ?? '' );
		$ts  = strtotime( $value );

		if ( '' !== $min && false !== ( $min_ts = strtotime( $min ) ) && $ts < $min_ts ) {
			/* translators: %s: earliest allowed date */
			return self::msg_or( $field, sprintf( __( 'Please choose a date on or after %s.', 'animation-addons-for-elementor' ), $min ) );
		}

		if ( '' !== $max && false !== ( $max_ts = strtotime( $max ) ) && $ts > $max_ts ) {
			/* translators: %s: latest allowed date */
			return self::msg_or( $field, sprintf( __( 'Please choose a date on or before %s.', 'animation-addons-for-elementor' ), $max ) );
		}

		return null;
	}

	/**
	 * Collapse the schema field list into one entry per submission key.
	 * Radio widgets sharing a name are ONE group: required if any member is,
	 * allowed values pooled from every member's `value` prop.
	 *
	 * @return array key => [ 'field' => array, 'required' => bool, 'allowed' => string[] ]
	 */
	private static function group_fields( array $fields ): array {
		$groups = [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || '' === (string) ( $field['key'] ?? '' ) ) {
				continue;
			}

			$key = (string) $field['key'];

			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = [
					'field'    => $field,
					'required' => ! empty( $field['required'] ),
					'allowed'  => [],
				];
			} else {
				$groups[ $key ]['required'] = $groups[ $key ]['required'] || ! empty( $field['required'] );
			}

			if ( 'radio' === ( $field['type'] ?? '' ) && '' !== (string) ( $field['value'] ?? '' ) ) {
				$groups[ $key ]['allowed'][] = (string) $field['value'];
			}
		}

		return $groups;
	}

	/**
	 * Resolve a field's posted KEY from its _cssid — the password widget's
	 * "Must match field ID" names the partner by CSS id, but the payload is
	 * keyed by the field key (name → _cssid → element id).
	 */
	private static function key_for_css_id( string $css_id, array $schema ): ?string {
		foreach ( (array) ( $schema['fields'] ?? [] ) as $field ) {
			if ( is_array( $field ) && $css_id === (string) ( $field['css_id'] ?? '' ) ) {
				return (string) ( $field['key'] ?? '' );
			}
		}

		return null;
	}

	/**
	 * Replace every password value with what may actually be persisted /
	 * handed to actions, per that field's `store_mode`:
	 *
	 *   never (default) — a fixed mask; the real value never leaves this
	 *                     request.
	 *   hash            — a one-way wp_hash_password() digest.
	 *
	 * Called by Rest.php on the Validator's `clean` output BEFORE storage,
	 * the submission_validated/submission_saved hooks and the action queue,
	 * so no downstream consumer (DB, admin email, webhook, CSV export) can
	 * ever see a readable password — there is deliberately no opt-out.
	 *
	 * @param array $clean  Validator 'clean' output.
	 * @param array $schema Active schema snapshot.
	 *
	 * @return array `clean` with password values redacted.
	 */
	public static function redact_passwords( array $clean, array $schema ): array {
		foreach ( (array) ( $schema['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) || 'password' !== ( $field['type'] ?? '' ) ) {
				continue;
			}

			$key = (string) ( $field['key'] ?? '' );
			if ( '' === $key || ! isset( $clean[ $key ] ) ) {
				continue;
			}

			$clean[ $key ] = 'hash' === ( $field['store_mode'] ?? 'never' )
				? wp_hash_password( (string) $clean[ $key ] )
				: '********';
		}

		return $clean;
	}

	/** Mirror of the select twig's options parsing: "value|Label" per line. */
	private static function parse_options( string $options ): array {
		$allowed = [];

		foreach ( preg_split( '/\r\n|\r|\n/', $options ) as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$parts     = explode( '|', $line, 2 );
			$allowed[] = trim( $parts[0] );
		}

		return $allowed;
	}

	/** Normalize a posted file-field value to [ ['id'=>int,'key'=>string], … ]. */
	private static function file_refs( $value ): array {
		$refs = [];

		foreach ( is_array( $value ) ? $value : [] as $entry ) {
			if ( is_array( $entry ) && isset( $entry['id'], $entry['key'] ) ) {
				$refs[] = [
					'id'  => (int) $entry['id'],
					'key' => is_string( $entry['key'] ) ? $entry['key'] : '',
				];
			}
		}

		return $refs;
	}

	private static function scalar( $value ): string {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/** The field's authored error message, or the given fallback — mirrors
	 * the frontend's messageFor(): one custom message covers every failure
	 * kind for that field. */
	private static function msg_or( array $field, string $fallback ): string {
		$custom = trim( (string) ( $field['error_message'] ?? '' ) );

		return '' !== $custom ? $custom : $fallback;
	}

	private static function msg_required( array $field ): string {
		return self::msg_or( $field, __( 'This field is required.', 'animation-addons-for-elementor' ) );
	}

	private static function msg_invalid( array $field ): string {
		return self::msg_or( $field, __( 'Please enter a valid value.', 'animation-addons-for-elementor' ) );
	}
}
