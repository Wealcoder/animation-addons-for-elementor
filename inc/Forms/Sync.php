<?php
/**
 * AAE Forms — save-time schema sync orchestrator (Milestone 4).
 *
 * Runs on `elementor/document/after_save` (the $data it receives already
 * went through Identity's save/data filter, so every form has a reconciled
 * form_key). For each TOP-LEVEL form (nested forms are invalid — skipped):
 *
 *   1. upsert the identity row (form_key ↔ post_id + element_id)
 *   2. walk the subtree into the canonical schema
 *   3. write a new schema version iff the hash changed
 *
 * Autosaves/revisions are skipped — versioning tracks real saves only.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sync {

	/** `elementor/document/after_save` callback. */
	public static function on_after_save( $document, $data ): void {
		if ( empty( $data['elements'] ) || ! is_array( $data['elements'] ) ) {
			return;
		}

		// Versioning tracks real saves, not autosave churn.
		if ( is_object( $document ) && method_exists( $document, 'is_autosave' ) && $document->is_autosave() ) {
			return;
		}

		$post_id = ( is_object( $document ) && method_exists( $document, 'get_main_id' ) )
			? (int) $document->get_main_id()
			: 0;

		foreach ( self::find_top_level_forms( $data['elements'] ) as $form ) {
			$form_key = (string) Prop::read( $form['settings'] ?? [], 'form_key', '' );
			if ( '' === $form_key ) {
				continue; // Identity filter didn't run — don't invent rows.
			}

			$form_id = Schema_Store::upsert_form( $form_key, $post_id, (string) ( $form['id'] ?? '' ) );
			if ( $form_id > 0 ) {
				Schema_Store::sync_schema( $form_id, Schema_Walker::build( $form ) );
			}
		}
	}

	/**
	 * All e-aae-a-form nodes that are NOT inside another form. Nested forms
	 * are invalid per spec — no identity/schema rows for them (a save-time
	 * UI warning is a later pass).
	 *
	 * @return array[] form element nodes
	 */
	public static function find_top_level_forms( array $elements ): array {
		$found = [];

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['elType'] ?? '' ) === Identity::FORM_TYPE ) {
				$found[] = $element; // don't descend — inner forms are ignored.
				continue;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = array_merge( $found, self::find_top_level_forms( $element['elements'] ) );
			}
		}

		return $found;
	}
}
