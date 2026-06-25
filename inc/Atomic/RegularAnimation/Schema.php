<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regular (preset-based) animation: fade / 3D-move / custom. Applied to every
 * atomic widget in Bootstrap::target_element_types(). Mirrors the v3
 * wcf-animation-effects.php Animation + Parallax sections.
 *
 * Atomic widgets validate that every control's bind_to() points at a TOP-LEVEL
 * key in the props schema. So we register one top-level prop per control;
 * show/hide for responsive props is JS-driven via the responsive-section
 * config table (see src/modules/atomic/extensions/regular-animation/config.js).
 */
final class Schema {

	/* ---------- section anchors ---------- */

	// The section anchor is a sentinel prop whose only role is to give the
	// JS-side <ResponsiveSection> component a $$type to hook on via
	// registerControlReplacement. When the placeholder Text_Control bound to
	// this prop renders, our dispatcher swaps it for the full responsive
	// section (label + input + dot + active-bp visibility for every field).
	const ANIM_SECTION_ANCHOR = 'aae_anim_section_anchor';

	/* ---------- repeater: full interactions list ----------
	 * The whole section is a repeater. Stored as Responsive_Json_Prop_Type:
	 * per-breakpoint value is an array of flat row objects, each a complete
	 * interaction { effect, method, trigger, delay, duration, easing, wrapper,
	 * start_position, end_position, trigger_selector, start_trigger, end_trigger,
	 * markers, custom_props[], custom_props_to[] }. JS owns the row shape;
	 * PHP round-trips and re-emits to the InteractionsMap as rows[].
	 */
	const ANIM_INTERACTIONS = 'aae_anim_interactions';

	/* ---------- editor toggle (shared across all interactions) ---------- */
	const ANIM_ENABLE_EDITOR = 'aae_anim_enable_editor';  // v3 wcf_enable_animation_editor

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_animation_props' ] );
	}

	public function add_animation_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		/* ---------- section anchor ---------- */

		// One placeholder prop per section. Controls.php binds a Text_Control
		// to it inside Section::make(); the JS-side registerResponsiveSection
		// dispatcher matches the unique $$type and renders the full
		// <ResponsiveSection> tree (label + input + dot + per-bp visibility
		// for every field, driven by the config table in
		// src/modules/atomic/extensions/regular-animation/config.js).
		$schema[ self::ANIM_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		/* ---------- repeater + shared toggle ----------
		 *
		 * ANIM_INTERACTIONS: Responsive_Json_Prop_Type — per-breakpoint array
		 * of flat interaction row objects. JS owns the row shape; PHP only
		 * validates the outer envelope and re-emits to the InteractionsMap.
		 *
		 * ANIM_ENABLE_EDITOR: non-responsive 'switch' row inside the section
		 * config; Play is a 'play-button' control with no underlying prop.
		 */
		$schema[ self::ANIM_INTERACTIONS ]  = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => [] ] );
		$schema[ self::ANIM_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
