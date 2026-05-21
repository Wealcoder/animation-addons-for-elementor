<?php
namespace WCF_ADDONS\Atomic\ImageHover;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Hover (Reveal-on-Hover) schema. Applies to every atomic widget in
 * Bootstrap::target_element_types().
 *
 * Image is now managed by the JS MediaInput control (aae_ih_image) stored
 * as a Responsive_Json_Prop_Type — the PHP Image_Prop_Type / Image_Control
 * are no longer used.
 */
final class Schema {

	/* ---- section anchor ---- */
	const IH_SECTION_ANCHOR = 'aae_ih_section_anchor';

	/* ---- JS-managed media (non-responsive cell, object) ---- */
	const IH_IMAGE = 'aae_ih_image';

	/* ---- responsive switch ---- */
	const IH_ENABLE = 'aae_ih_enable';

	/* ---- responsive numerics ---- */
	const IH_ZINDEX = 'aae_ih_zindex';
	const IH_WIDTH  = 'aae_ih_width';
	const IH_HEIGHT = 'aae_ih_height';
	const IH_TOP    = 'aae_ih_top';
	const IH_LEFT   = 'aae_ih_left';

	const IH_ENABLE_EDITOR = 'aae_ih_enable_editor';

	/** Atomic widget types this section appears on. */
	public static function image_hover_widgets(): array {
		return Bootstrap::target_element_types();
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( Responsive_Json_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::IH_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		// JS-managed image — { id, url, size, sizes } stored as JSON.
		// No default needed; null means "no image picked".
		$schema[ self::IH_IMAGE ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => null ] );

		// Responsive enable switch — per breakpoint.
		$schema[ self::IH_ENABLE ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => false ] );

		// Responsive numerics.
		$schema[ self::IH_ZINDEX  ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 1 ] );
		$schema[ self::IH_WIDTH   ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 300 ] );
		$schema[ self::IH_HEIGHT  ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 300 ] );
		$schema[ self::IH_TOP     ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );
		$schema[ self::IH_LEFT    ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );
		$schema[ self::IH_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
