<?php
/**
 * Converts pre-2026-08-27 Button link settings to the `link` prop shape.
 *
 * WHY THIS EXISTS — deleting it is data loss, not cleanup.
 *
 * The widget used to carry the URL as two flat props:
 *
 *     "btn_url":    { "$$type": "string", "value": "https://example.com" }
 *     "btn_target": { "$$type": "string", "value": "_blank" }
 *
 * Those two are now a single Link_Prop_Type (`link`) — the same object shape
 * Elementor's own Flexbox/Div_Block use for their Link control — expecting:
 *
 *     "link": { "$$type": "link", "value": {
 *         "destination":    { "$$type": "url",     "value": "https://example.com" },
 *         "isTargetBlank":  { "$$type": "boolean",  "value": true },
 *         "tag":            { "$$type": "string",   "value": "a" }
 *     } }
 *
 * This one is NOT optional, for two independent reasons:
 *
 *   1. Render_Props_Resolver::transform() (core) rejects a value whose
 *      `$$type` does not match the schema prop's own key before it ever
 *      looks at a transformer, and the OLD props (`btn_url`/`btn_target`)
 *      no longer exist in the schema at all — Props_Parser::validate() and
 *      Render_Props_Resolver::resolve() both iterate the SCHEMA's keys, so
 *      an unmigrated element's href/target settings are simply invisible
 *      from here on, string data or not.
 *   2. The panel. A flat Url_Prop_Type behind a plain Text_Control was tried
 *      first and does not work at all, migrated or not: Elementor's
 *      dynamic-tag schema extension wraps every dynamic-eligible scalar in a
 *      Union keyed by the prop's own type, and Text_Control's React
 *      component always resolves through a hardcoded `stringPropTypeUtil`
 *      (key 'string') — a lookup that throws once the union's member key is
 *      'url' instead, and the control's ErrorBoundary swallows it into a
 *      blank field. Link_Control is the control actually built for this
 *      object shape.
 *
 * Measured on this site before shipping: 238 saved pages, 669+ non-
 * placeholder URLs (share links, etc.) using the old shape.
 *
 * Running on `elementor/document/load/data` catches both the editor and the
 * frontend renderer, exactly like AAE_Advanced_Heading_Migration. It rewrites
 * the in-memory copy only; the DB row is upgraded lazily, the next time that
 * page is saved through the editor. That is deliberate — a read-path filter
 * must never write.
 *
 * `btn_nofollow` is untouched — it was never part of this shape change and
 * keeps its own Boolean_Prop_Type.
 *
 * SAFE TO DELETE once no site can still hold the old shape. There is no way
 * to detect that from here, so treat it as permanent.
 *
 * NOTE: AAE_A_Btn_Pro (`animation-addons-for-elementor-pro`) still declares
 * its own separate `btn_url`/`btn_target` String_Prop_Type pair and was
 * deliberately left untouched — it needs its own equivalent migration if it
 * is ever switched to Link_Prop_Type too.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Btn;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Btn_Migration {

	const ELEMENT_TYPE = 'e-aae-a-btn';

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
	 * @return mixed The same tree, with legacy link settings upgraded.
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
	 * Recurses unconditionally rather than only into containers: a Button can
	 * sit at any depth, inside a flexbox, a grid, a nested slider or a loop
	 * item, and a whitelist of "types that can have children" is one more
	 * list to keep in sync.
	 *
	 * @param array $elements
	 * @return array
	 */
	private static function walk( array $elements ): array {
		foreach ( $elements as $index => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( self::is_btn( $element ) ) {
				$elements[ $index ] = self::migrate_element( $element );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = self::walk( $element['elements'] );
			}
		}

		return $elements;
	}

	/**
	 * The Button saves as elType `e-aae-a-btn` (a container, per
	 * Atomic_Element_Base + is_container), but check widgetType too — a
	 * future refactor of this widget must not silently skip the migration.
	 *
	 * @param array $element
	 */
	private static function is_btn( array $element ): bool {
		return self::ELEMENT_TYPE === ( $element['elType'] ?? null )
			|| self::ELEMENT_TYPE === ( $element['widgetType'] ?? null );
	}

	/**
	 * @param array $element
	 * @return array
	 */
	private static function migrate_element( array $element ): array {
		$settings = $element['settings'] ?? null;

		if ( ! is_array( $settings ) ) {
			return $element;
		}

		// Already migrated (or something else we do not own) — leave it alone.
		$has_legacy_url = is_array( $settings['btn_url'] ?? null )
			&& 'string' === ( $settings['btn_url']['$$type'] ?? null );

		if ( ! $has_legacy_url ) {
			return $element;
		}

		$url = $settings['btn_url']['value'] ?? '';

		if ( ! is_string( $url ) ) {
			$url = '';
		}

		$target = $settings['btn_target']['value'] ?? '_self';
		$is_target_blank = '_blank' === $target;

		$element['settings']['link'] = [
			'$$type' => 'link',
			'value'  => [
				// Same raw string, just relabelled into the shape Url_Prop_Type
				// (nested inside Link_Prop_Type's `destination` union) expects.
				'destination'   => [
					'$$type' => 'url',
					'value'  => $url,
				],
				'isTargetBlank' => [
					'$$type' => 'boolean',
					'value'  => $is_target_blank,
				],
				// Btn always renders `<a>` (define_default_html_tag()) — this
				// field only exists because Link_Prop_Type's shape is fixed.
				'tag'           => [
					'$$type' => 'string',
					'value'  => 'a',
				],
			],
		];

		// The old props are no longer in the schema and would just be dead
		// weight in the saved JSON from here on.
		unset( $element['settings']['btn_url'], $element['settings']['btn_target'] );

		return $element;
	}
}
