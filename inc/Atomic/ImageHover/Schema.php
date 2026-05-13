<?php
namespace WCF_ADDONS\Atomic\ImageHover;

use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Utils\Image\Placeholder_Image;
use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Hover (Reveal-on-Hover) schema. Applies to every atomic widget in
 * Bootstrap::target_element_types() — the cursor-following hover image
 * effect works on containers AND widgets.
 *
 * Image picker + z-index are non-responsive primitives (Image_Prop_Type +
 * Number_Prop_Type). Width / height / top / left are responsive numbers
 * stored as Responsive_Json_Prop_Type — px is implicit.
 */
final class Schema {

	/* ---- section anchor ---- */
	const IH_SECTION_ANCHOR = 'aae_ih_section_anchor';

	/* ---- non-responsive primitives ---- */
	const IH_ENABLE_EDITOR = 'aae_ih_enable_editor';
	const IH_IMAGE         = 'aae_ih_image';
	const IH_ZINDEX        = 'aae_ih_zindex';

	/* ---- responsive numerics (px implicit) ---- */
	const IH_WIDTH    = 'aae_ih_width';
	const IH_HEIGHT   = 'aae_ih_height';
	const IH_TOP      = 'aae_ih_top';
	const IH_LEFT     = 'aae_ih_left';

	/** Atomic widget types this section appears on. */
	public static function image_hover_widgets(): array {
		return Bootstrap::target_element_types();
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( Boolean_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::IH_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		// Non-responsive primitives. No separate "Enable" — the hover effect
		// activates when the user picks a real image (i.e. the image URL no
		// longer equals Elementor's default placeholder).
		$schema[ self::IH_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );
		// `default_url` is mandatory — Image_Src_Prop_Type's validator requires
		// EXACTLY ONE truthy key in the {id, url} shape. Without a default URL
		// the validator gets `{id:null, url:null}` (zero truthy) and rejects
		// the save with "aae_ih_image: invalid_value". Mirrors how core's
		// Atomic_Image widget seeds its own image prop.
		$schema[ self::IH_IMAGE         ] = Image_Prop_Type::make()
			->default_url( Placeholder_Image::get_placeholder_image() )
			->default_size( 'full' );
		$schema[ self::IH_ZINDEX        ] = Number_Prop_Type::make()->default( 1 );

		// Responsive numerics — px implicit, all default to sensible v3-ish values.
		$schema[ self::IH_WIDTH  ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 300 ] );
		$schema[ self::IH_HEIGHT ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 300 ] );
		$schema[ self::IH_TOP    ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );
		$schema[ self::IH_LEFT   ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );

		return $schema;
	}
}
