<?php
namespace WCF_ADDONS\Atomic\ImageAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Animation schema. Applies to e-image and e-svg widgets only.
 *
 * Effect values mirror v3 (reveal / scale / stretch). Each has its own
 * gated fields; visibility is enforced JS-side via predicates.js, the
 * schema just declares the props.
 */
final class Schema {

	/* ---- section anchor ---- */
	const IMG_SECTION_ANCHOR = 'aae_img_section_anchor';

	/* ---- responsive props ---- */
	const IMG_EFFECT       = 'aae_img_effect';        // none | reveal | scale | stretch
	const IMG_START_FROM   = 'aae_img_start_from';    // left | right | top | bottom  (reveal only)
	const IMG_SCALE_START  = 'aae_img_scale_start';   // float (scale only)
	const IMG_SCALE_END    = 'aae_img_scale_end';     // float (scale only)
	const IMG_START_POS    = 'aae_img_start_pos';     // top top | ... | custom (scale + reveal)
	const IMG_CUSTOM_START = 'aae_img_custom_start';  // text (when start_pos === custom)
	const IMG_END_POS      = 'aae_img_end_pos';       // top top | ... | custom (scale + reveal)
	const IMG_EASE         = 'aae_img_ease';          // power2.out | bounce | ...   (reveal only)

	/* ---- non-responsive ---- */
	const IMG_ENABLE_MARKER = 'aae_img_enable_marker';
	const IMG_ENABLE_EDITOR = 'aae_img_enable_editor';

	/** Atomic widget types this section appears on. */
	public static function image_animation_widgets(): array {
		return [ 'e-image', 'e-svg', 'e-aae-a-post-image' ];
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::IMG_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		$schema[ self::IMG_EFFECT       ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'none' ] );
		$schema[ self::IMG_START_FROM   ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'right' ] );
		$schema[ self::IMG_SCALE_START  ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0.5 ] );
		$schema[ self::IMG_SCALE_END    ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 1 ] );
		$schema[ self::IMG_START_POS    ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'top center' ] );
		//$schema[ self::IMG_CUSTOM_START ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'top 90%' ] );
		$schema[ self::IMG_END_POS      ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'top center' ] );
		$schema[ self::IMG_EASE         ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'power2.out' ] );

		$schema[ self::IMG_ENABLE_MARKER ] = Boolean_Prop_Type::make()->default( false );
		$schema[ self::IMG_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
