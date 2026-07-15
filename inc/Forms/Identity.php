<?php
/**
 * AAE Forms — form identity (Milestone 4).
 *
 * Every e-aae-a-form instance carries one `form_key` (hidden prop, tied to
 * post_id + element_id). Keys are assigned/reconciled SERVER-SIDE on every
 * Elementor document save via the `elementor/document/save/data` filter —
 * one choke point that covers create, duplicate, copy/paste, and
 * programmatic import in a single pass (per CLAUDE.md "Form identity"):
 *
 *   - empty key                  → generate
 *   - key duplicated in this doc → regenerate (in-document duplicate)
 *   - key owned by ANOTHER post/element in aae_forms → regenerate
 *     (cross-page paste/import)
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Identity {

	const FORM_TYPE = 'e-aae-a-form';

	/**
	 * Collision oracle — returns true when a key is taken by a different
	 * post/element. Injectable for tests; defaults to the aae_forms table.
	 *
	 * @var callable|null fn( string $key, int $post_id, string $element_id ): bool
	 */
	public static $key_taken_elsewhere = null;

	/**
	 * `elementor/document/save/data` filter callback.
	 *
	 * @param array $data     Document data (elements + settings).
	 * @param mixed $document \Elementor\Core\Base\Document.
	 * @return array Mutated data with reconciled form keys.
	 */
	public static function filter_save_data( $data, $document ) {
		if ( empty( $data['elements'] ) || ! is_array( $data['elements'] ) ) {
			return $data;
		}

		$post_id = ( is_object( $document ) && method_exists( $document, 'get_main_id' ) )
			? (int) $document->get_main_id()
			: 0;

		$seen             = [];
		$data['elements'] = self::reconcile_tree( $data['elements'], $post_id, $seen );

		return $data;
	}

	/** Recursively reconcile form keys in an elements tree. */
	public static function reconcile_tree( array $elements, int $post_id, array &$seen ): array {
		foreach ( $elements as $i => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['elType'] ?? '' ) === self::FORM_TYPE ) {
				$elements[ $i ] = self::reconcile_form( $element, $post_id, $seen );
			}

			if ( ! empty( $elements[ $i ]['elements'] ) && is_array( $elements[ $i ]['elements'] ) ) {
				$elements[ $i ]['elements'] = self::reconcile_tree( $elements[ $i ]['elements'], $post_id, $seen );
			}
		}

		return $elements;
	}

	private static function reconcile_form( array $form, int $post_id, array &$seen ): array {
		$element_id = (string) ( $form['id'] ?? '' );
		$key        = (string) Prop::read( $form['settings'] ?? [], 'form_key', '' );

		$needs_new = '' === $key
			|| isset( $seen[ $key ] )
			|| self::taken_elsewhere( $key, $post_id, $element_id );

		if ( $needs_new ) {
			$key = self::generate_key( $seen, $post_id, $element_id );

			if ( ! isset( $form['settings'] ) || ! is_array( $form['settings'] ) ) {
				$form['settings'] = [];
			}
			$form['settings']['form_key'] = Prop::string( $key );
		}

		$seen[ $key ] = true;

		return $form;
	}

	/** Generate a fresh key that collides with nothing known. */
	private static function generate_key( array $seen, int $post_id, string $element_id ): string {
		do {
			$key = 'aaef_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 12 );
		} while ( isset( $seen[ $key ] ) || self::taken_elsewhere( $key, $post_id, $element_id ) );

		return $key;
	}

	private static function taken_elsewhere( string $key, int $post_id, string $element_id ): bool {
		if ( '' === $key ) {
			return false;
		}

		if ( is_callable( self::$key_taken_elsewhere ) ) {
			return (bool) call_user_func( self::$key_taken_elsewhere, $key, $post_id, $element_id );
		}

		return Schema_Store::key_taken_elsewhere( $key, $post_id, $element_id );
	}
}
