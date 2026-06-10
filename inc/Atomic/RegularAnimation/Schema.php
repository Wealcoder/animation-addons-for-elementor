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

	/* ---------- main animation prop names ---------- */
	const ANIM_EFFECT           = 'aae_anim_effect';            // v3 wcf-animation
	const ANIM_METHOD           = 'aae_anim_method';            // v3 aae_method
	const ANIM_TRIGGER          = 'aae_anim_trigger';           // v3 aae_trigger
	const ANIM_TRIGGER_SELECTOR = 'aae_anim_trigger_selector';  // v3 aae_trigger_selector

	/* ---------- scroll trigger settings (wrapper=custom + scroll/play_with_scroll) ---------- */
	const ANIM_WRAPPER        = 'aae_anim_wrapper';         // v3 aae_anim_wrapper
	const ANIM_START_TRIGGER  = 'aae_anim_start_trigger';   // v3 aae_anim_s_t
	const ANIM_END_TRIGGER    = 'aae_anim_end_trigger';     // v3 aae_anim_e_t
	const ANIM_START_POSITION = 'aae_anim_start_position';  // v3 aae_anim_s
	const ANIM_END_POSITION   = 'aae_anim_end_position';    // v3 aae_anim_e
	const ANIM_MARKERS        = 'aae_anim_markers';         // v3 aae_anim_markers

	/* ---------- shared numeric/easing settings ---------- */
	const ANIM_DELAY    = 'aae_anim_delay';     // v3 delay
	const ANIM_DURATION = 'aae_anim_duration';  // v3 data-duration
	const ANIM_EASING   = 'aae_anim_easing';    // v3 ease



	/* ---------- custom effect specific ---------- */
	// v3 had a 2-field REPEATER (property SELECT + value TEXT). v4 stores
	// this as a Responsive_Json_Prop_Type — a permissive transformable whose
	// per-breakpoint value is a plain array of { enabled, property, value }
	// row objects. JS owns the row contract entirely (RepeaterInput inside
	// the responsive-section); PHP just round-trips the payload.
	const ANIM_CUSTOM_PROPS         = 'aae_anim_custom_props';
	const ANIM_CUSTOM_PROPS_TO      = 'aae_anim_custom_props_to';

	/* ---------- editor toggle ---------- */
	const ANIM_ENABLE_EDITOR = 'aae_anim_enable_editor';  // v3 wcf_enable_animation_editor

	/* ---------- responsive numeric defaults ---------- */

	const RESPONSIVE_NUMBER_SETTINGS = [
		self::ANIM_DELAY       => 0.15,
		self::ANIM_DURATION    => 1.5,
	];

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

		/* ---------- responsive props (visibility is JS-driven via the section config; no PHP set_dependencies) ----------
		 *
		 * Every responsive field stores under a single permissive type —
		 * Responsive_Json_Prop_Type — whose value is a plain breakpoint map
		 * { desktop: <scalar>, tablet: <scalar>|null, … }. PHP only validates
		 * the outer envelope shape; the JS section owns per-cell semantics.
		 *
		 * Defaults wrap a desktop seed; non-desktop breakpoints are absent so
		 * the cascade inherits from desktop.
		 */
		$schema[ self::ANIM_EFFECT ]            = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'none' ] );
		$schema[ self::ANIM_METHOD ]            = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'from' ] );
		$schema[ self::ANIM_TRIGGER ]           = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'on_scroll' ] );
		$schema[ self::ANIM_TRIGGER_SELECTOR ]  = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '' ] );

		$schema[ self::ANIM_WRAPPER ]           = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'default' ] );
		$schema[ self::ANIM_START_TRIGGER ]     = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '' ] );
		$schema[ self::ANIM_END_TRIGGER ]       = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => '' ] );
		$schema[ self::ANIM_START_POSITION ]    = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'top top' ] );
		$schema[ self::ANIM_END_POSITION ]      = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'bottom top' ] );

		$schema[ self::ANIM_DELAY ]    = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_DELAY ] ] );
		$schema[ self::ANIM_DURATION ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_DURATION ] ] );
		$schema[ self::ANIM_EASING ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 'power2.out' ] );


		/* ---------- non-responsive props ----------
		 *
		 * These stay outside the responsive section:
		 *   - ANIM_MARKERS: previously gated on ANIM_EFFECT/TRIGGER/WRAPPER
		 *     (now responsive). Visibility moves up into the JS — rendered as
		 *     a plain Section item with its deps dropped. Always-visible
		 *     for now.
		 *   - ANIM_CUSTOM_PROPS: Responsive_Json_Prop_Type — per-bp array of
		 *     row objects. The JS-side <RepeaterInput> inside the section
		 *     owns row shape semantics; PHP just round-trips the payload.
		 *     Visibility (effect=custom) is JS-driven.
		 *   - ANIM_ENABLE_EDITOR: rendered as a non-responsive 'switch' row
		 *     inside the section config; Play Animation is rendered as a
		 *     'play-button' control with no underlying prop.
		 */

		$schema[ self::ANIM_MARKERS ]        = Boolean_Prop_Type::make()->default( false );
		$schema[ self::ANIM_CUSTOM_PROPS ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => [] ] );
		$schema[ self::ANIM_CUSTOM_PROPS_TO ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => [] ] );
		$schema[ self::ANIM_ENABLE_EDITOR ]  = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
