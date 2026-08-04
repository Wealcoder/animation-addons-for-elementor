<?php
/**
 * Stops a document ever PERSISTING an orphaned Mobile Nav.
 *
 * A Mobile Nav (`e-aae-a-mobile-nav`) is a SIBLING of the Nav it serves, so
 * Elementor does not cascade-delete it. Two editor-side sweeps now handle the
 * live case — `startNavCompanionLifecycle()` in the editor-bridge (document-wide)
 * and the older one inside `MobileNavLifecycleControl` (selection-scoped). Both
 * are best-effort JavaScript: they are skipped while a MUI popover is open, they
 * cannot run in a context where the editor never booted, and they cannot fix a
 * document that was already saved with an orphan by an older plugin version.
 *
 * This is the belt that does not depend on any of that. It runs on save, sees
 * the whole tree at once, and is the only layer that can state a guarantee.
 *
 * DELIBERATELY CONSERVATIVE. Dropping an element at save time is destructive and
 * unattended, so it only removes a companion whose `source_nav_id` is
 * NON-EMPTY and resolves to no element ANYWHERE in the same document:
 *
 *   - empty `source_nav_id`  → left alone. Either hand-placed, or caught
 *                              mid-creation before the reconciler stamped it.
 *   - id resolves            → left alone, obviously.
 *   - anything not a Mobile Nav → never touched.
 *
 * The id is searched across the ENTIRE document, not among siblings: a Nav can
 * legitimately be moved into a different container from its companion, and
 * scoping the search to the parent would delete a perfectly live companion.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Nav_Companion_Sweep {

	const COMPANION_TYPE = 'e-aae-a-mobile-nav';

	public static function register(): void {
		// Priority 20: after Elementor's own save-data handlers have settled, so
		// we prune what is actually about to be written.
		add_filter( 'elementor/document/save/data', [ __CLASS__, 'sweep_document' ], 20 );
	}

	/**
	 * @param mixed $data Element tree about to be saved.
	 * @return mixed
	 */
	public static function sweep_document( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$live_ids = [];
		self::collect_ids( $data, $live_ids );

		// No companion in the tree at all — the overwhelmingly common case, and
		// worth short-circuiting before walking a second time.
		if ( ! self::has_companion( $data ) ) {
			return $data;
		}

		return self::prune( $data, $live_ids );
	}

	/**
	 * Every element id present in the tree, as a lookup set.
	 *
	 * @param array $elements
	 * @param array $ids Collected by reference.
	 */
	private static function collect_ids( array $elements, array &$ids ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['id'] ) && is_string( $element['id'] ) ) {
				$ids[ $element['id'] ] = true;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				self::collect_ids( $element['elements'], $ids );
			}
		}
	}

	/**
	 * @param array $elements
	 */
	private static function has_companion( array $elements ): bool {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( self::is_companion( $element ) ) {
				return true;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] )
				&& self::has_companion( $element['elements'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Atomic elements save as elType `widget` + widgetType, but check elType too
	 * — element-style saves exist for atomic containers.
	 *
	 * @param array $element
	 */
	private static function is_companion( array $element ): bool {
		return self::COMPANION_TYPE === ( $element['widgetType'] ?? null )
			|| self::COMPANION_TYPE === ( $element['elType'] ?? null );
	}

	/**
	 * @param array $elements
	 * @param array $live_ids
	 * @return array
	 */
	private static function prune( array $elements, array $live_ids ): array {
		$kept = [];

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				$kept[] = $element;
				continue;
			}

			if ( self::is_companion( $element ) && self::is_orphan( $element, $live_ids ) ) {
				continue;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$element['elements'] = self::prune( $element['elements'], $live_ids );
			}

			$kept[] = $element;
		}

		// Re-index: a JSON array with a hole serialises as an OBJECT, and
		// Elementor's parser expects a list. Skipping this turns "one orphan
		// removed" into "this container's children are corrupt".
		return array_values( $kept );
	}

	/**
	 * @param array $element
	 * @param array $live_ids
	 */
	private static function is_orphan( array $element, array $live_ids ): bool {
		$source_id = $element['settings']['source_nav_id'] ?? null;

		// The prop is stored as `{ '$$type' => 'string', 'value' => '<id>' }`.
		if ( is_array( $source_id ) ) {
			$source_id = $source_id['value'] ?? null;
		}

		if ( ! is_string( $source_id ) || '' === $source_id ) {
			return false;
		}

		return ! isset( $live_ids[ $source_id ] );
	}
}
