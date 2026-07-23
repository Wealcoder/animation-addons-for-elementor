<?php
/**
 * AAE_Html_Rich_Prop_Type — a string prop that keeps inline HTML *with* classes.
 *
 * Why this exists: Elementor's own rich-text prop (Html_V3_Prop_Type) runs
 * wp_kses on save with a whitelist that allows a handful of inline tags but
 * NO `class` attribute (only `id`), and its Twig render re-strips attributes
 * via `striptags`. That is exactly why the stock Heading cannot carry a
 * highlight `<span class="…">`.
 *
 * This prop reuses the plain-'string' key (so the already-registered string
 * transformer renders it verbatim — no new transformer to register) and only
 * swaps the sanitiser for a permissive wp_kses whitelist that keeps `class`,
 * `id`, `style` on a curated set of inline tags. The widget's own Twig then
 * prints the value with `| raw` (no striptags), so the classes survive both
 * save AND render.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

class AAE_Html_Rich_Prop_Type extends String_Prop_Type {

	/**
	 * Sanitise on save. Keeps inline formatting tags together with class / id /
	 * style, so a user's highlight span (e.g. <span class="my-highlight">) is
	 * preserved. wp_kses still strips <script>, event handlers and any tag not
	 * on the whitelist, so raw-HTML input stays safe.
	 */
	protected function sanitize_value( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return wp_kses( $value, self::allowed_tags() );
	}

	/**
	 * The inline tags a heading may contain, each allowed to carry class/id/style
	 * (and the extra link attributes on <a>). Deliberately inline-only — no block
	 * tags — because this is a single heading line.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_tags(): array {
		$attrs = [
			'class' => true,
			'id'    => true,
			'style' => true,
			'title' => true,
			'dir'   => true,
		];

		$inline = [ 'span', 'mark', 'b', 'strong', 'i', 'em', 'u', 's', 'del', 'ins', 'sub', 'sup', 'small', 'abbr', 'code', 'kbd', 'q', 'bdi', 'wbr' ];

		$tags = [];
		foreach ( $inline as $tag ) {
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
}
