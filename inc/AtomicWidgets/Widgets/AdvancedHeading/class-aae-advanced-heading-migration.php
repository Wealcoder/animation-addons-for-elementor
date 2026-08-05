<?php
/**
 * Converts pre-2026-08-04 Advanced Heading content to the html-v3 shape.
 *
 * WHY THIS EXISTS — deleting it is data loss, not cleanup.
 *
 * The widget's `content` prop used to be AAE_Html_Rich_Prop_Type, which reused
 * the plain `string` key, so a saved heading looks like:
 *
 *     "content": { "$$type": "string", "value": "Build your <b>x</b>" }
 *
 * It is now Html_V3_Prop_Type, which expects:
 *
 *     "content": { "$$type": "html-v3", "value": {
 *         "content":  { "$$type": "string", "value": "Build your <b>x</b>" },
 *         "children": []
 *     } }
 *
 * Rendering would actually survive without this class — the props resolver
 * picks its transformer by the value's own `$$type`, so the old string still
 * renders. Two other things would NOT survive, and both fail silently:
 *
 *   1. The panel. Inline_Editing_Control binds `htmlV3PropTypeUtil`, which
 *      cannot read a string-typed value, so the editor opens with an EMPTY
 *      box. The user types one character and the original text is gone.
 *   2. The next save. Props_Parser::validate() drops any prop whose value
 *      fails its schema type, and Html_V3_Prop_Type::validate_value() rejects
 *      a bare string outright. So merely re-saving an untouched page erases
 *      the heading.
 *
 * Running on `elementor/document/load/data` catches both, because that filter
 * feeds the editor (core/base/document.php) and the frontend renderer
 * (includes/frontend.php) alike. It rewrites the in-memory copy only; the DB
 * row is upgraded lazily, the next time that page is saved through the editor.
 * That is deliberate — a read-path filter must never write.
 *
 * SAFE TO DELETE once no site can still hold the old shape. There is no way to
 * detect that from here, so treat it as permanent.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_Advanced_Heading_Migration {

	const ELEMENT_TYPE = 'e-aae-a-advanced-heading';

	/**
	 * Hook the read path. Idempotent — the filter body no-ops on data that has
	 * already been converted, so registering twice is harmless.
	 */
	public static function register(): void {
		add_filter(
			'elementor/document/load/data',
			[ __CLASS__, 'migrate_document' ],
			// After Elementor's own Migrations_Orchestrator (priority 10), so
			// we are looking at data core has already finished normalising.
			11
		);
	}

	/**
	 * @param mixed $data Element tree as stored in _elementor_data.
	 * @return mixed The same tree, with legacy heading content upgraded.
	 */
	public static function migrate_document( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		return self::walk( $data );
	}

	/**
	 * Depth-first walk of the element tree.
	 *
	 * Recurses unconditionally rather than only into containers: an Advanced
	 * Heading can sit at any depth, inside a flexbox, a grid, a nested slider
	 * or a loop item, and a whitelist of "types that can have children" is one
	 * more list to keep in sync.
	 *
	 * @param array $elements
	 * @return array
	 */
	private static function walk( array $elements ): array {
		foreach ( $elements as $index => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( self::is_advanced_heading( $element ) ) {
				$elements[ $index ] = self::migrate_element( $element );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = self::walk( $element['elements'] );
			}
		}

		return $elements;
	}

	/**
	 * Atomic widgets save as elType `widget` + widgetType `<type>`, but check
	 * elType too — element-style saves exist for atomic CONTAINERS, and a
	 * future refactor of this widget must not silently skip the migration.
	 *
	 * @param array $element
	 */
	private static function is_advanced_heading( array $element ): bool {
		return self::ELEMENT_TYPE === ( $element['widgetType'] ?? null )
			|| self::ELEMENT_TYPE === ( $element['elType'] ?? null );
	}

	/**
	 * @param array $element
	 * @return array
	 */
	private static function migrate_element( array $element ): array {
		$content = $element['settings']['content'] ?? null;

		if ( ! is_array( $content ) ) {
			return $element;
		}

		// Already html-v3 (or something else we do not own) — leave it alone.
		if ( 'string' !== ( $content['$$type'] ?? null ) ) {
			return $element;
		}

		$html = $content['value'] ?? '';

		if ( ! is_string( $html ) ) {
			return $element;
		}

		$element['settings']['content'] = [
			'$$type' => 'html-v3',
			'value'  => [
				// The string envelope is nested, not reused by reference: the
				// outer $$type must become html-v3 while the inner one stays
				// `string`, which is exactly what Html_V3_Prop_Type's shape
				// declares.
				'content'  => [
					'$$type' => 'string',
					'value'  => $html,
				],
				// `children` is the parsed inline-node tree the editor keeps
				// alongside the HTML. An empty array is valid and correct here
				// — the control re-derives it (parseHtmlChildren) on the first
				// edit, and nothing on the render path reads it.
				'children' => [],
			],
		];

		return $element;
	}
}
