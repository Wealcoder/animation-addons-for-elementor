<?php
namespace WCF_ADDONS\Atomic\ImageOverlay;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Overlay — a settings-prop extension (Section in the panel, like
 * Tilt / Parallax), NOT a style-schema extension like Mask. Deliberately so:
 * the user wants a dedicated "Image Overlay" section that behaves like every
 * other AAE extension, not another entry under the native Style tab.
 *
 * WHY IT NEEDS ITS OWN RENDER CSS RATHER THAN A REAL STYLE PROP: e-image
 * renders as a bare <img> with no wrapper (unless it has a Link, which wraps
 * it in <a>), and e-svg's wrapper paints its background BEHIND the already-
 * opaque content it wraps. Either way, a translucent color/gradient layered
 * on top needs `mix-blend-mode` to composite against that opaque content —
 * plain `background-color`/`background-image` alone would render invisibly
 * behind it. Render.php applies both background + mix-blend-mode directly to
 * the element itself (see effects/image-overlay/index.js).
 */
final class Schema {

	/* ---- section anchor ---- */
	const SECTION_ANCHOR = 'aae_img_ovl_section_anchor';

	/* ---- props ---- */
	const ENABLE           = 'aae_img_ovl_enable';
	const TYPE             = 'aae_img_ovl_type';             // 'color' | 'gradient'
	const COLOR            = 'aae_img_ovl_color';
	const GRADIENT_COLOR_1 = 'aae_img_ovl_gradient_color_1';
	const GRADIENT_COLOR_2 = 'aae_img_ovl_gradient_color_2';
	const GRADIENT_ANGLE   = 'aae_img_ovl_gradient_angle';
	const OPACITY          = 'aae_img_ovl_opacity';
	const BLEND_MODE       = 'aae_img_ovl_blend_mode';
	const ENABLE_EDITOR    = 'aae_img_ovl_enable_editor';

	/** Images only — matches ImageAnimation / ImageHover's target set. */
	public static function target_element_types(): array {
		return [ 'e-image', 'e-svg' ];
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		$schema[ self::SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		// All responsive: an overlay a site wants only above a certain
		// breakpoint (e.g. stronger tint on mobile for text contrast) is a
		// real, common case — same reasoning as every other shared extension.
		$schema[ self::ENABLE ]           = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => false ] );
		$schema[ self::TYPE ]             = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'color' ] );
		$schema[ self::COLOR ]            = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '#000000' ] );
		$schema[ self::GRADIENT_COLOR_1 ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '#000000' ] );
		$schema[ self::GRADIENT_COLOR_2 ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '#ffffff' ] );
		$schema[ self::GRADIENT_ANGLE ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 180 ] );
		$schema[ self::OPACITY ]          = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 50 ] );
		$schema[ self::BLEND_MODE ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'multiply' ] );

		$schema[ self::ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
