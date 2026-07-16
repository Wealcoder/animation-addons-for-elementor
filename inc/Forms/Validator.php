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

					$clean[ $key ] = $value;
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

					$clean[ $key ] = $value;
			}
		}

		return [
			'clean'  => $clean,
			'errors' => $errors,
		];
	}

	/** Per-type format checks; null means the value passes. */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- $field is reserved for per-field validation_rules (Validation Pro).
	private static function check_format( string $type, string $value, array $field ): ?string {
		switch ( $type ) {
			case 'email':
				return is_email( $value ) ? null : __( 'Please enter a valid email address.', 'animation-addons-for-elementor' );

			case 'url':
				return false !== filter_var( $value, FILTER_VALIDATE_URL ) ? null : __( 'Please enter a valid URL.', 'animation-addons-for-elementor' );

			case 'number':
				return is_numeric( $value ) ? null : __( 'Please enter a number.', 'animation-addons-for-elementor' );

			case 'tel':
				// Basic-format-only per spec's Free tier (real validation is a Pro adapter).
				return preg_match( '/^\+?[0-9\-().\s]{3,30}$/', $value ) ? null : __( 'Please enter a valid phone number.', 'animation-addons-for-elementor' );
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

	private static function scalar( $value ): string {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- $field is reserved for per-field custom messages (Validation Pro).
	private static function msg_required( array $field ): string {
		return __( 'This field is required.', 'animation-addons-for-elementor' );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- $field is reserved for per-field custom messages (Validation Pro).
	private static function msg_invalid( array $field ): string {
		return __( 'Please enter a valid value.', 'animation-addons-for-elementor' );
	}
}
