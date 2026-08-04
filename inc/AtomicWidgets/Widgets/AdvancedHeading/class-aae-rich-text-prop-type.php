<?php
/**
 * AAE_Rich_Text_Prop_Type — html-v3 that keeps inline COLOUR.
 *
 * Elementor's Html_V3_Prop_Type sanitises through
 * `Html_Prop_Type::get_base_allowed_tags()`, which permits **no attributes at
 * all** except `a[href|target]`. So `<span style="color:#c00">` is stripped on
 * save, and per-word colour is impossible no matter what the editor emits.
 *
 * This subclass swaps ONLY the sanitiser. It deliberately does NOT override
 * `get_key()`, so the stored `$$type` stays `html-v3` — which means:
 *
 *   • `htmlV3PropTypeUtil` on the client reads and writes it unchanged,
 *   • `Html_V3_Transformer` renders it unchanged,
 *   • no migration is needed, and swapping back to the stock prop type later
 *     costs nothing but the colours.
 *
 * Same trick the old AAE_Html_Rich_Prop_Type used against String_Prop_Type —
 * reuse the key, replace the whitelist.
 *
 * `class` is deliberately NOT allowed. It was the thing that leaked into the
 * Content box and cluttered the markup, and nothing in this widget needs it
 * now that colour is a real toolbar action.
 *
 * SECURITY — `style` is not a hole here. wp_kses runs every style attribute
 * through `safecss_filter_attr()`, which whitelists CSS properties (colour is
 * on that list) and rejects `url()`, `expression()`, escapes and anything else
 * it does not recognise. Tags stay inline-only: no script, no iframe, no
 * event-handler attributes, and href goes through kses' own protocol check.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;

class AAE_Rich_Text_Prop_Type extends Html_V3_Prop_Type {

	/**
	 * Re-implemented rather than delegated: the parent's own helpers
	 * (`sanitize_html_content`, `sanitize_children`) are PRIVATE, so a subclass
	 * cannot call them and there is nothing to extend. Keep the parent's
	 * structure — content first, then children — so behaviour only differs in
	 * the whitelist.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( isset( $value['content'] ) && is_array( $value['content'] ) && is_string( $value['content']['value'] ?? null ) ) {
			$value['content']['value'] = self::sanitize_html( $value['content']['value'] );
		}

		if ( isset( $value['children'] ) && is_array( $value['children'] ) ) {
			$value['children'] = self::sanitize_children( $value['children'] );
		}

		return $value;
	}

	/**
	 * Preserves surrounding whitespace exactly the way the parent does — a
	 * heading that reads "Build your " relies on that trailing space, and
	 * wp_kses would otherwise be free to trim it.
	 */
	private static function sanitize_html( string $content ): string {
		return preg_replace_callback(
			'/^(\s*)(.*?)(\s*)$/',
			function ( $matches ) {
				[ , $leading, $value, $trailing ] = $matches;

				return $leading . wp_kses( $value, self::allowed_tags() ) . $trailing;
			},
			$content
		);
	}

	/**
	 * Inline formatting only — this is one heading line, never a document.
	 * Must stay in sync with:
	 *   • ALLOWED_TAGS / ALLOWED_ATTRS in RichTextControl.jsx (client-side clean)
	 *   • the striptags whitelist in aae-a-advanced-heading.html.twig
	 * A tag the editor can produce but this list omits is silently deleted on
	 * save, which reads to the user as "my formatting disappeared".
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_tags(): array {
		$attrs = [
			'style' => true,
			'title' => true,
			'dir'   => true,
		];

		$tags = [];

		foreach ( [ 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'del', 'sub', 'sup', 'mark', 'small' ] as $tag ) {
			$tags[ $tag ] = $attrs;
		}

		$tags['br'] = [];
		$tags['a']  = array_merge(
			$attrs,
			[
				'href'   => true,
				'target' => true,
				'rel'    => true,
			]
		);

		return $tags;
	}

	/**
	 * Straight copy of the parent's private children sanitiser. `children` is
	 * metadata the editor keeps beside the HTML; this widget writes an empty
	 * array, but a value arriving from an import or a component override still
	 * has to be cleaned rather than trusted.
	 *
	 * @param array $children
	 * @return array
	 */
	private static function sanitize_children( array $children ): array {
		$sanitized = [];

		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$clean = [];

			foreach ( [ 'id', 'type', 'content' ] as $key ) {
				if ( isset( $child[ $key ] ) && is_string( $child[ $key ] ) ) {
					$clean[ $key ] = sanitize_text_field( $child[ $key ] );
				}
			}

			if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
				$clean['children'] = self::sanitize_children( $child['children'] );
			}

			$sanitized[] = $clean;
		}

		return $sanitized;
	}
}
