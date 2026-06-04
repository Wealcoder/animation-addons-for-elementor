<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Atomic widgets validate that every control's bind_to() points at a TOP-LEVEL
 * key in the props schema (no dot-paths into nested Object_Prop_Type shapes).
 * So we register one top-level prop per control.
 *
 * Conditional show/hide is expressed via Dependency_Manager attached to the
 * dependent prop (the one that should hide). The path[] points at the SOURCE
 * prop being inspected. effect = 'hide' removes the control from the panel.
 */
final class Schema {

	/* ---- regular animation prop names ---- */
	const ANIM_EFFECT        = 'aae_anim_effect';
	const ANIM_TRIGGER       = 'aae_anim_trigger';
	const ANIM_DURATION      = 'aae_anim_duration';
	const ANIM_DELAY         = 'aae_anim_delay';
	const ANIM_EASING        = 'aae_anim_easing';
	const ANIM_REPEAT        = 'aae_anim_repeat';
	const ANIM_ENABLE_EDITOR = 'aae_anim_enable_editor';
	const ANIM_PLAY_TOKEN    = 'aae_anim_play_token';

	/* ---- text animation prop names ---- */
	const TEXT_EFFECT           = 'aae_text_effect';
	const TEXT_TRIGGER          = 'aae_text_trigger';
	const TEXT_TRIGGER_SELECTOR = 'aae_text_trigger_selector';
	const TEXT_WRAPPER          = 'aae_text_wrapper';
	const TEXT_WRAPPER_SELECTOR = 'aae_text_wrapper_selector';
	const TEXT_DELAY            = 'aae_text_delay';
	const TEXT_DURATION         = 'aae_text_duration';
	const TEXT_STAGGER          = 'aae_text_stagger';
	const TEXT_TRANSLATE_X      = 'aae_text_translate_x';
	const TEXT_TRANSLATE_Y      = 'aae_text_translate_y';
	const TEXT_ROTATION_DIR     = 'aae_text_rotation_dir';
	const TEXT_ROTATION         = 'aae_text_rotation';
	const TEXT_TRANSFORM_ORIGIN = 'aae_text_transform_origin';
	const TEXT_ENABLE_EDITOR    = 'aae_text_enable_editor';
	const TEXT_PLAY_TOKEN       = 'aae_text_play_token';

	/**
	 * Per-breakpoint prop names are derived dynamically as `<base>_<bp>`
	 * (e.g. aae_text_delay_tablet, aae_text_delay_mobile_extra). Use
	 * Schema::breakpoint_prop( $base, $bp ) wherever you need them.
	 */
	public static function breakpoint_prop( string $base, string $bp ): string {
		return $base . '_' . $bp;
	}

	/**
	 * Human-readable labels for every Elementor breakpoint we might generate
	 * responsive controls for. Order matters: largest → smallest viewport.
	 */
	const BREAKPOINT_LABELS = [
		'widescreen'   => 'Widescreen',
		'laptop'       => 'Laptop',
		'tablet_extra' => 'Tablet Extra',
		'tablet'       => 'Tablet',
		'mobile_extra' => 'Mobile Extra',
		'mobile'       => 'Mobile',
	];

	/** Settings that get a per-breakpoint variant. */
	const RESPONSIVE_NUMBER_SETTINGS = [
		self::TEXT_DELAY       => 0.15,
		self::TEXT_DURATION    => 1,
		self::TEXT_STAGGER     => 0.02,
		self::TEXT_TRANSLATE_X => 20,
		self::TEXT_TRANSLATE_Y => 0,
		self::TEXT_ROTATION    => -80,
	];

	/** Effects that count as "animated" for general show/hide. */
	const TEXT_ANIMATED_EFFECTS = [ 'char', 'word', 'text_move', 'text_reveal', 'text_scale', 'text_invert', 'text_spin' ];

	/** Effects that expose Duration / Stagger (V3 excludes spin/invert). */
	const TEXT_DURATION_EFFECTS = [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ];

	/** Effects that expose Transform-X / Transform-Y. */
	const TEXT_TRANSLATE_EFFECTS = [ 'char', 'word' ];

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_animation_props' ] );
	}

	public function add_animation_props( array $schema ): array {
		if ( ! class_exists( Responsive_JSON_Prop_Type::class ) ) {
			return $schema;
		}

		/* ---------- regular animation ---------- */

		$schema[ self::ANIM_EFFECT ]        = Responsive_JSON_Prop_Type::make()->default( 'none' );
		$schema[ self::ANIM_TRIGGER ]       = Responsive_JSON_Prop_Type::make()->default( 'on_page_load' );
		$schema[ self::ANIM_DURATION ]      = Responsive_JSON_Prop_Type::make()->default( 1.5 );
		$schema[ self::ANIM_DELAY ]         = Responsive_JSON_Prop_Type::make()->default( 0.15 );
		$schema[ self::ANIM_EASING ]        = Responsive_JSON_Prop_Type::make()->default( 'power2.out' );
		$schema[ self::ANIM_REPEAT ]        = Responsive_JSON_Prop_Type::make()->default( 0 );
		$schema[ 'aae_anim_method' ]        = Responsive_JSON_Prop_Type::make()->default( 'from' );
		$schema[ 'aae_anim_trigger_selector' ] = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_anim_wrapper' ]       = Responsive_JSON_Prop_Type::make()->default( 'default' );
		$schema[ 'aae_anim_start_trigger' ] = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_anim_end_trigger' ]   = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_anim_start_position' ]= Responsive_JSON_Prop_Type::make()->default( 'top top' );
		$schema[ 'aae_anim_start_custom' ]  = Responsive_JSON_Prop_Type::make()->default( 'top top' );
		$schema[ 'aae_anim_end_position' ]  = Responsive_JSON_Prop_Type::make()->default( 'bottom top' );
		$schema[ 'aae_anim_end_custom' ]    = Responsive_JSON_Prop_Type::make()->default( 'bottom top' );
		$schema[ 'aae_anim_fade_from' ]     = Responsive_JSON_Prop_Type::make()->default( 'bottom' );
		$schema[ 'aae_anim_fade_offset' ]   = Responsive_JSON_Prop_Type::make()->default( 50 );
		$schema[ 'aae_anim_scale' ]         = Responsive_JSON_Prop_Type::make()->default( 0.7 );
		$schema[ 'aae_anim_rotation_dir' ]  = Responsive_JSON_Prop_Type::make()->default( 'x' );
		$schema[ 'aae_anim_rotation' ]      = Responsive_JSON_Prop_Type::make()->default( -80 );
		$schema[ 'aae_anim_transform_origin' ] = Responsive_JSON_Prop_Type::make()->default( 'top center -50' );
		$schema[ 'aae_anim_custom_props' ]  = Responsive_JSON_Prop_Type::make()->default( [] );

		$schema[ self::ANIM_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );
		$schema[ self::ANIM_PLAY_TOKEN ]    = Boolean_Prop_Type::make()->default( false );

		/* ---------- text animation ---------- */

		$schema[ self::TEXT_EFFECT ]        = Responsive_JSON_Prop_Type::make()->default( 'none' );
		$schema[ self::TEXT_TRIGGER ]       = Responsive_JSON_Prop_Type::make()->default( 'in-view' );
		$schema[ self::TEXT_TRIGGER_SELECTOR ] = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ self::TEXT_WRAPPER ]       = Responsive_JSON_Prop_Type::make()->default( 'default' );
		$schema[ 'aae_text_start_trigger' ] = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_text_end_trigger' ]   = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_text_start_position' ]= Responsive_JSON_Prop_Type::make()->default( 'top top' );
		$schema[ 'aae_text_end_position' ]  = Responsive_JSON_Prop_Type::make()->default( 'bottom top' );
		$schema[ 'aae_text_invert_start' ]  = Responsive_JSON_Prop_Type::make()->default( 'top 85%' );
		$schema[ 'aae_text_invert_end' ]    = Responsive_JSON_Prop_Type::make()->default( 'bottom center' );
		$schema[ 'aae_text_spin_start' ]    = Responsive_JSON_Prop_Type::make()->default( 'top 85%' );
		$schema[ 'aae_text_spin_end' ]      = Responsive_JSON_Prop_Type::make()->default( 'bottom 30%' );
		$schema[ 'aae_text_spin_toggle' ]   = Responsive_JSON_Prop_Type::make()->default( 'play none none reverse' );
		$schema[ self::TEXT_DELAY ]         = Responsive_JSON_Prop_Type::make()->default( 0.15 );
		$schema[ self::TEXT_DURATION ]      = Responsive_JSON_Prop_Type::make()->default( 1 );
		$schema[ self::TEXT_STAGGER ]       = Responsive_JSON_Prop_Type::make()->default( 0.02 );
		$schema[ self::TEXT_TRANSLATE_X ]   = Responsive_JSON_Prop_Type::make()->default( 20 );
		$schema[ self::TEXT_TRANSLATE_Y ]   = Responsive_JSON_Prop_Type::make()->default( 0 );
		$schema[ self::TEXT_ROTATION_DIR ]  = Responsive_JSON_Prop_Type::make()->default( 'x' );
		$schema[ self::TEXT_ROTATION ]      = Responsive_JSON_Prop_Type::make()->default( -80 );
		$schema[ self::TEXT_TRANSFORM_ORIGIN ] = Responsive_JSON_Prop_Type::make()->default( 'top center -50' );
		$schema[ 'aae_text_scale_ease' ]    = Responsive_JSON_Prop_Type::make()->default( 'back' );
		$schema[ 'aae_text_scale_num' ]     = Responsive_JSON_Prop_Type::make()->default( 1.5 );
		$schema[ 'aae_text_scale_break' ]   = Responsive_JSON_Prop_Type::make()->default( 'lines' );
		$schema[ 'aae_text_spin_color' ]    = Responsive_JSON_Prop_Type::make()->default( '' );
		$schema[ 'aae_text_ease' ]          = Responsive_JSON_Prop_Type::make()->default( '' );

		$schema[ self::TEXT_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );
		$schema[ self::TEXT_PLAY_TOKEN ]    = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}

	public static function text_animation_widgets(): array {
		return [ 'e-heading','e-paragraph' ];
	}
}
