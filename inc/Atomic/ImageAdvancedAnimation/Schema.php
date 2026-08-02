<?php
namespace WCF_ADDONS\Atomic\ImageAdvancedAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Advanced Animation schema. Applies to e-image / e-svg /
 * e-aae-a-post-image, same targets as the sibling ImageAnimation extension.
 *
 * Ported from the "Advanced Single Image GSAP Widget" prototype
 * (z_temp/Image Animation/script.js): 8 cinematic presets (cinematicMask,
 * scaleAnimation, sliceShutter, mosaicDepth, liquidClip, orbitTilt,
 * zoomTunnel, scrollParallax), each with its own tunable fields. Distinct
 * from ImageAnimation (reveal/scale/stretch + shared regular-animation
 * presets + free-form custom props) — this extension is only the 8 richer
 * built-in cinematic effects, each with named fields (no custom-props
 * repeater).
 *
 * REPEATER architecture, same shape as ImageAnimation/RegularAnimation:
 * one Responsive_Json_Prop_Type holds the full per-breakpoint rows[] array;
 * JS owns the row shape, PHP round-trips and re-emits to InteractionsMap.
 */
final class Schema {

	/* ---- section anchor ---- */
	const IMGADV_SECTION_ANCHOR = 'aae_imgadv_section_anchor';

	/* ---- repeater: full interactions list ---- */
	const IMGADV_INTERACTIONS = 'aae_imgadv_interactions';

	/* ---- editor toggle (shared across all interactions) ---- */
	const IMGADV_ENABLE_EDITOR = 'aae_imgadv_enable_editor';

	/** Atomic widget types this section appears on. */
	public static function image_advanced_animation_widgets(): array {
		return [ 'e-image', 'e-svg', 'e-aae-a-post-image' ];
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::IMGADV_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );
		$schema[ self::IMGADV_INTERACTIONS ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => [] ] );
		$schema[ self::IMGADV_ENABLE_EDITOR ]  = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
