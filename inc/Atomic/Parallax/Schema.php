<?php
namespace WCF_ADDONS\Atomic\Parallax;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parallax (ScrollSmoother) schema. Applies to every atomic widget in
 * Bootstrap::target_element_types() — same target set as RegularAnimation
 * so a widget can layer parallax on top of an effect.
 *
 * Visibility for every responsive field is JS-driven via the config table
 * at src/modules/atomic/extensions/parallax/config.js — Schema only
 * registers the props. No PHP set_dependencies on responsive props.
 */
final class Schema {

	/* ---- section anchor ---- */
	const PARALLAX_SECTION_ANCHOR = 'aae_plx_section_anchor';

	/* ---- parallax prop names ---- */
	const PARALLAX_ENABLE = 'aae_plx_enable';  // v3 wcf_enable_scroll_smoother
	const PARALLAX_SPEED  = 'aae_plx_speed';   // v3 data-speed
	const PARALLAX_LAG    = 'aae_plx_lag';     // v3 data-lag

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_parallax_props' ] );
	}

	public function add_parallax_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::PARALLAX_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		// All three fields are responsive. Visibility of Speed / Lag is gated
		// on `aae_plx_enable` via the JS predicate isParallaxEnabled.
		$schema[ self::PARALLAX_ENABLE ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => false ] );
		$schema[ self::PARALLAX_SPEED ]  = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0.9 ] );
		$schema[ self::PARALLAX_LAG ]    = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 0 ] );

		return $schema;
	}
}
