<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialShare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

/**
 * AAE Social Share Item — Icon. A plain e-svg with its own element type,
 * existing ONLY so its fixed square size can be a real declared base style
 * instead of a hook class stuffed into `classes`.
 *
 * The earlier version reused native e-svg directly and sized it by seeding
 * `aae-a-social-share-item-icon` into the icon's OWN `classes` prop, then
 * had AAE_A_Social_Share::get_frontend_css_override() print
 * `.e-aae-a-social-share-item-icon{width:...}` globally to win the
 * specificity tie against e-svg-base's native 65px default. That class was
 * never a real registered style FOR THIS ELEMENT, so Elementor's panel
 * flagged it "missing" and its own ✕ would strip it — see "Never put a
 * functional hook class in the classes prop" in this plugin's CLAUDE.md.
 * Extending Atomic_Svg (see get_templates()'s __DIR__ note below) and
 * declaring the size as a normal base style — exactly like every other
 * widget's icon part — needs no hook class and no external override at all.
 */
class AAE_A_Social_Share_Item_Icon extends Atomic_Svg {

	/**
	 * Single source of truth for the icon's fixed square size. Was
	 * AAE_A_Social_Share_Item::ICON_SIZE_PX; moved here now that this class
	 * owns its own sizing outright.
	 */
	const ICON_SIZE_PX = 30;

	public static $widget_description = 'Internal share icon used by the AAE Social Share Item.';

	public static function get_element_type(): string {
		return 'e-aae-a-social-share-item-icon';
	}

	public function get_title() {
		return esc_html__( 'Icon', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'social', 'share', 'icon', 'svg', 'atomic' ];
	}

	public function show_in_panel() {
		// Internal sub-element — managed only via its parent Social Share
		// Item, never dragged in independently from the widget panel.
		return false;
	}

	// define_props_schema(), define_atomic_controls() and get_templates() are
	// all inherited from Atomic_Svg unchanged — the SVG picker, link control
	// and rendering markup stay identical. get_templates() resolves its twig
	// path via __DIR__, a compile-time constant fixed to WHERE that method's
	// code is written, not to the calling subclass — so it keeps pointing at
	// Elementor's own atomic-svg.html.twig even though nothing here
	// re-declares it, and that generic template already renders correctly
	// from any settings/base_styles context, subclass or not.

	protected function define_base_styles(): array {
		$size = Size_Prop_Type::generate( [ 'size' => self::ICON_SIZE_PX, 'unit' => 'px' ] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'inline-block' ) )
						->add_prop( 'width', $size )
						->add_prop( 'height', $size )
				),
		];
	}
}
