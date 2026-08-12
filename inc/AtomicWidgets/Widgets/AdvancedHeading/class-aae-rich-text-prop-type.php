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
 * SECURITY — `style` is filtered, not trusted. wp_kses runs every style
 * attribute through `safecss_filter_attr()`, which whitelists CSS properties
 * and strips `expression()`, escapes and anything it does not recognise.
 * Event-handler attributes never survive (they are not on the list below), and
 * `href` / `src` go through kses' own protocol check, so `javascript:` is
 * neutralised.
 *
 * MEASURED, and NOT what an earlier revision of this comment claimed: WordPress
 * DOES allow `url()` for the background properties, so
 * `style="background:url(https://evil.example/x)"` survives. That is core's own
 * posture — identical to what it permits in post content — but it means a value
 * written here can reference an external URL. It is not script execution. If
 * that matters for a given site, filter `safe_style_css` / `safecss_filter_attr`
 * rather than assuming this layer stops it.
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
	 * Hand-written HTML is a supported way to author this widget, so this covers
	 * block-level structure as well as inline formatting.
	 *
	 * Must stay in sync with:
	 *   • ALLOWED_TAGS / ALLOWED_ATTRS in InlineTextControl.jsx (client-side clean;
	 *     TYPED_TAG_RE there is derived from that set, so it follows automatically)
	 *   • the striptags whitelist in aae-a-advanced-heading.html.twig
	 * A tag the editor can produce but this list omits is silently deleted on
	 * save, which reads to the user as "my formatting disappeared".
	 *
	 * SECURITY — widening the tag list does not widen the attack surface in the
	 * way it looks like it might. Everything here is inert markup: no script,
	 * iframe, object, embed, form, input, style or link, and no `on*` handler can
	 * survive because wp_kses drops every attribute not named below. `style` is
	 * still filtered per-property by safecss_filter_attr(), and `href`/`src` go
	 * through kses' own protocol check, so `javascript:` is refused.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_tags(): array {
		// Applies to every tag. `class` and `id` are allowed now that typed markup
		// is a first-class input — the earlier objection was that `class` leaked in
		// from execCommand noise, which the client clean no longer produces.
		$attrs = [
			'style' => true,
			'title' => true,
			'dir'   => true,
			'lang'  => true,
			'id'    => true,
			'class' => true,
		];

		$tags = [];

		$simple = [
			// inline
			'span', 'b', 'strong', 'i', 'em', 'u', 's', 'del', 'ins', 'sub', 'sup',
			'mark', 'small', 'code', 'abbr', 'cite', 'q',
			// block
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'ul', 'ol', 'li',
			'blockquote', 'pre', 'figure', 'figcaption',
		];

		foreach ( $simple as $tag ) {
			$tags[ $tag ] = $attrs;
		}

		// Void elements: attributes still apply, they just have no children.
		$tags['br'] = [];
		$tags['hr'] = $attrs;

		$tags['a'] = array_merge(
			$attrs,
			[
				'href'   => true,
				'target' => true,
				'rel'    => true,
			]
		);

		$tags['img'] = array_merge(
			$attrs,
			[
				'src'     => true,
				'alt'     => true,
				'width'   => true,
				'height'  => true,
				'loading' => true,
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
