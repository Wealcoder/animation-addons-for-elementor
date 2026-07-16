<?php
/**
 * AAE Forms — atomic prop-envelope helpers.
 *
 * Atomic settings persist as { $$type, value } envelopes. These helpers
 * read a scalar out of either shape (envelope or raw) and wrap one back,
 * so Identity/Schema code never hand-rolls envelope handling.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Prop {

	/** Read a scalar setting; unwraps { $$type, value } envelopes. */
	public static function read( $settings, string $key, $fallback = '' ) {
		if ( ! is_array( $settings ) || ! array_key_exists( $key, $settings ) ) {
			return $fallback;
		}

		$value = $settings[ $key ];

		if ( is_array( $value ) && array_key_exists( 'value', $value ) && array_key_exists( '$$type', $value ) ) {
			$value = $value['value'];
		}

		return null === $value ? $fallback : $value;
	}

	/**
	 * Read the plain-text content of an Html_V3 prop
	 * ({ $$type: html-v3, value: { content: { $$type: string, value }, children } }).
	 */
	public static function read_html_text( $settings, string $key, string $fallback = '' ): string {
		$value = self::read( $settings, $key, null );

		if ( is_array( $value ) && isset( $value['content'] ) ) {
			$content = $value['content'];
			if ( is_array( $content ) && array_key_exists( 'value', $content ) ) {
				$content = $content['value'];
			}
			return is_string( $content ) ? $content : $fallback;
		}

		return is_string( $value ) ? $value : $fallback;
	}

	/** Wrap a scalar in a string envelope. */
	public static function string( string $value ): array {
		return [
			'$$type' => 'string',
			'value'  => $value,
		];
	}
}
