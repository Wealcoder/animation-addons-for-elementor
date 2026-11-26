<?php
namespace Wealcoder\AnimationAddons\Atomic\LiquidGlass;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Wealcoder\AnimationAddons\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liquid Glass — a frosted, refracting glass surface for containers.
 *
 * A settings-prop extension (own panel section, like Image Overlay), not a
 * style-schema one: Elementor core already ships `backdrop-filter`, but its
 * prop type cannot carry the `url(#filter)` SVG displacement that makes the
 * glass "liquid". The effect is a plain style application (no GSAP), applied
 * by effects/liquid-glass/index.js on the frontend and in the editor.
 *
 * The preset list below is the PHP whitelist; the filter definitions
 * themselves live next to the runtime in effects/liquid-glass/index.js.
 * Keep the two key lists in sync.
 */
final class Schema {

	/* ---- section anchor ---- */
	const SECTION_ANCHOR = 'aae_lg_section_anchor';

	/* ---- props (all responsive except the editor flag) ---- */
	const ENABLE        = 'aae_lg_enable';
	const PRESET        = 'aae_lg_preset';
	const BLUR          = 'aae_lg_blur';
	const DISTORTION    = 'aae_lg_distortion';
	const SHADOW        = 'aae_lg_shadow';
	const BORDER_WIDTH  = 'aae_lg_border_width';
	const BORDER_COLOR  = 'aae_lg_border_color';
	const RADIUS        = 'aae_lg_radius';
	const ENABLE_EDITOR = 'aae_lg_enable_editor';

	/** Containers only. */
	public static function target_element_types(): array {
		return [ 'e-flexbox', 'e-div-block', 'e-grid' ];
	}

	/** @return string[] */
	public static function presets(): array {
		return [ 'frosted', 'liquid-lens', 'ripple', 'crystal', 'wave', 'prism', 'soft-blur', 'jelly' ];
	}

	/** @return string[] */
	public static function shadows(): array {
		return [ 'none', 'soft-glow', 'bright-glow', 'wide-bloom', 'top-light', 'rim-light', 'inner-shadow' ];
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		$schema[ self::SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		// Responsive on purpose: a site can switch the (GPU-heavy) distortion
		// off on mobile just by turning Enable off at that breakpoint.
		$schema[ self::ENABLE ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => false ] );
		$schema[ self::PRESET ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'frosted' ] );
		$schema[ self::BLUR ]         = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );
		$schema[ self::DISTORTION ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 100 ] );
		$schema[ self::SHADOW ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'soft-glow' ] );
		$schema[ self::BORDER_WIDTH ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 1 ] );
		$schema[ self::BORDER_COLOR ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'rgba(255,255,255,0.35)' ] );
		$schema[ self::RADIUS ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );

		$schema[ self::ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
