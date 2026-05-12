<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Array_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\Schema_Helpers;

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
 * show/hide is expressed via Dependency_Manager attached to the dependent prop.
 */
final class Schema {

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
	const ANIM_START_CUSTOM   = 'aae_anim_start_custom';    // v3 aae_anim_s_cus
	const ANIM_END_POSITION   = 'aae_anim_end_position';    // v3 aae_anim_e
	const ANIM_END_CUSTOM     = 'aae_anim_end_custom';      // v3 aae_anim_e_cus
	const ANIM_MARKERS        = 'aae_anim_markers';         // v3 aae_anim_markers

	/* ---------- shared numeric/easing settings ---------- */
	const ANIM_DELAY    = 'aae_anim_delay';     // v3 delay
	const ANIM_DURATION = 'aae_anim_duration';  // v3 data-duration
	const ANIM_EASING   = 'aae_anim_easing';    // v3 ease

	/* ---------- fade effect specific ---------- */
	const ANIM_FADE_FROM   = 'aae_anim_fade_from';    // v3 fade-from
	const ANIM_FADE_OFFSET = 'aae_anim_fade_offset';  // v3 fade-offset
	const ANIM_SCALE       = 'aae_anim_scale';        // v3 wcf-a-scale (start scale)

	/* ---------- 3D move effect specific ---------- */
	const ANIM_ROTATION_DIR     = 'aae_anim_rotation_dir';     // v3 wcf_a_rotation_di
	const ANIM_ROTATION         = 'aae_anim_rotation';         // v3 wcf_a_rotation
	const ANIM_TRANSFORM_ORIGIN = 'aae_anim_transform_origin'; // v3 wcf_a_transform_origin

	/* ---------- custom effect specific ---------- */
	// v3 had a 2-field REPEATER (property SELECT + value TEXT). Atomic's
	// Repeatable_Control supports only ONE child control type per row, so we
	// store data in TWO parallel String_Array props (index-aligned) but render
	// ONE combined UI via a React morph (editor-bridge/custom-props.jsx).
	//
	//   - ANIM_CUSTOM_PROP_KEYS    — array of property names  (data only)
	//   - ANIM_CUSTOM_PROP_VALUES  — array of values          (data only)
	//   - ANIM_CUSTOM_PROPS_TRIGGER — boolean placeholder; its panel row is
	//                                what the React UI morphs into a full
	//                                property+value repeater.
	const ANIM_CUSTOM_PROP_KEYS     = 'aae_anim_custom_prop_keys';    // v3 repeater.property column
	const ANIM_CUSTOM_PROP_VALUES   = 'aae_anim_custom_prop_values';  // v3 repeater.value column
	const ANIM_CUSTOM_PROPS_TRIGGER = 'aae_anim_custom_props_trigger'; // React-morph placeholder

	/* ---------- editor + play button ---------- */
	const ANIM_ENABLE_EDITOR = 'aae_anim_enable_editor';  // v3 wcf_enable_animation_editor
	const ANIM_PLAY_TOKEN    = 'aae_anim_play_token';     // v3 play_animation_content (button)

	/* ---------- parallax (Scroll Smoother) ---------- */
	const PARALLAX_ENABLE = 'aae_parallax_enable';  // v3 wcf_enable_scroll_smoother
	const PARALLAX_SPEED  = 'aae_parallax_speed';   // v3 data-speed
	const PARALLAX_LAG    = 'aae_parallax_lag';     // v3 data-lag

	// TODO(repeater): v3 aae_ani_custom_props REPEATER not yet ported.
	// v4 Repeatable_Control requires Object_Prop_Type composition — separate task.

	/* ---------- effect families ---------- */

	/** All non-"none" effect values. */
	const ANIMATED_EFFECTS = [ 'fade', 'move', 'custom' ];

	/** Effects that expose Duration / Delay (v3 excludes none + custom — custom has its own repeater). */
	const DURATION_EFFECTS = [ 'fade', 'move' ];

	/** Effects that expose Ease (v3: any effect except none). */
	const EASE_EFFECTS = [ 'fade', 'move', 'custom' ];

	/* ---------- responsive defaults ---------- */

	const BREAKPOINT_LABELS = [
		'widescreen'   => 'Widescreen',
		'laptop'       => 'Laptop',
		'tablet_extra' => 'Tablet Extra',
		'tablet'       => 'Tablet',
		'mobile_extra' => 'Mobile Extra',
		'mobile'       => 'Mobile',
	];

	const RESPONSIVE_NUMBER_SETTINGS = [
		self::ANIM_DELAY       => 0.15,
		self::ANIM_DURATION    => 1.5,
		self::ANIM_FADE_OFFSET => 50,
		self::ANIM_SCALE       => 0.7,
		self::ANIM_ROTATION    => -80,
	];

	public static function breakpoint_prop( string $base, string $bp ): string {
		return $base . '_' . $bp;
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_animation_props' ] );
	}

	public function add_animation_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		/* ---------- main animation ---------- */

		// EFFECT + TRIGGER: deliberately registered WITHOUT enum at the schema
		// level. An earlier stub of this Schema shipped a different option set
		// ('fadeIn'/'slideUp' for effect, 'in-view'/'page-load' for trigger);
		// pages saved against that stub would now fail "invalid_value" on
		// publish if we hardened the enum here. The Select_Control still
		// constrains the panel UI to the current option list, so accepting
		// any string at the prop layer is the safe, forward-compatible choice.
		$this->register_responsive_string( $schema, self::ANIM_EFFECT, 'none', null );

		$anim_active = Schema_Helpers::dep_in( self::ANIM_EFFECT, self::ANIMATED_EFFECTS );

		// Method (From/To) — shown for fade/move/custom.
		$this->register_responsive_string( $schema, self::ANIM_METHOD, 'from', $anim_active, array_keys( self::methods() ) );

		// Trigger — shown for fade/move/custom. No enum (see ANIM_EFFECT comment above).
		$this->register_responsive_string( $schema, self::ANIM_TRIGGER, 'on_scroll', $anim_active );

		// Trigger Selector — only when trigger is hover/click AND animation is selected.
		$trigger_selector_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_EFFECT ],
				'value'    => self::ANIMATED_EFFECTS,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_TRIGGER ],
				'value'    => [ 'mouseover', 'click' ],
				'effect'   => 'hide',
			] )
			->get();
		$this->register_responsive_string( $schema, self::ANIM_TRIGGER_SELECTOR, '', $trigger_selector_deps );

		/* ---------- scroll trigger block (wrapper=custom + scroll/play_with_scroll) ---------- */

		// Wrapper picker shows when trigger is on_scroll / play_with_scroll AND animation is selected.
		$wrapper_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_EFFECT ],
				'value'    => self::ANIMATED_EFFECTS,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_TRIGGER ],
				'value'    => [ 'on_scroll', 'play_with_scroll' ],
				'effect'   => 'hide',
			] )
			->get();
		$this->register_responsive_string( $schema, self::ANIM_WRAPPER, 'default', $wrapper_deps, [ 'default', 'custom' ] );

		// All scroll-trigger block fields share this combined dependency.
		$scroll_custom_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_EFFECT ],
				'value'    => self::ANIMATED_EFFECTS,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_TRIGGER ],
				'value'    => [ 'on_scroll', 'play_with_scroll' ],
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::ANIM_WRAPPER ],
				'value'    => 'custom',
				'effect'   => 'hide',
			] )
			->get();

		$this->register_responsive_string( $schema, self::ANIM_START_TRIGGER,  '',           $scroll_custom_deps );
		$this->register_responsive_string( $schema, self::ANIM_END_TRIGGER,    '',           $scroll_custom_deps );
		$this->register_responsive_string( $schema, self::ANIM_START_POSITION, 'top top',    $scroll_custom_deps, self::scroll_positions() );
		$this->register_responsive_string( $schema, self::ANIM_END_POSITION,   'bottom top', $scroll_custom_deps, self::scroll_positions() );

		// Custom Start text — show only when start position = 'custom' (+ scroll-custom).
		$custom_start_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::ANIM_EFFECT ],         'value' => self::ANIMATED_EFFECTS,             'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::ANIM_TRIGGER ],        'value' => [ 'on_scroll', 'play_with_scroll' ], 'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::ANIM_WRAPPER ],        'value' => 'custom',                            'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::ANIM_START_POSITION ], 'value' => 'custom',                            'effect' => 'hide' ] )
			->get();
		$this->register_responsive_string( $schema, self::ANIM_START_CUSTOM, 'top top', $custom_start_deps );

		$custom_end_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::ANIM_EFFECT ],       'value' => self::ANIMATED_EFFECTS,             'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::ANIM_TRIGGER ],      'value' => [ 'on_scroll', 'play_with_scroll' ], 'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::ANIM_WRAPPER ],      'value' => 'custom',                            'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::ANIM_END_POSITION ], 'value' => 'custom',                            'effect' => 'hide' ] )
			->get();
		$this->register_responsive_string( $schema, self::ANIM_END_CUSTOM, 'bottom top', $custom_end_deps );

		// Markers — boolean (v3 had string 'true'/'false' SELECT, atomic uses Boolean).
		$schema[ self::ANIM_MARKERS ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $scroll_custom_deps );

		/* ---------- shared numeric (delay / duration) ---------- */

		$duration_deps = Schema_Helpers::dep_in( self::ANIM_EFFECT, self::DURATION_EFFECTS );

		$this->register_responsive_number( $schema, self::ANIM_DELAY,    self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_DELAY ],    $duration_deps );
		$this->register_responsive_number( $schema, self::ANIM_DURATION, self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_DURATION ], $duration_deps );

		/* ---------- easing (shown for any non-none effect) ---------- */

		$ease_deps = Schema_Helpers::dep_in( self::ANIM_EFFECT, self::EASE_EFFECTS );
		$this->register_responsive_string( $schema, self::ANIM_EASING, 'power2.out', $ease_deps, array_keys( self::eases() ) );

		/* ---------- fade-specific ---------- */

		$fade_deps = Schema_Helpers::dep_eq( self::ANIM_EFFECT, 'fade' );
		$this->register_responsive_string( $schema, self::ANIM_FADE_FROM, 'bottom', $fade_deps, array_keys( self::fade_directions() ) );

		// Fade offset — show only when fade-from is a directional value (not 'in' / 'scale').
		$fade_offset_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::ANIM_EFFECT ],
				'value'    => 'fade',
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'in',
				'path'     => [ self::ANIM_FADE_FROM ],
				'value'    => [ 'top', 'bottom', 'left', 'right' ],
				'effect'   => 'hide',
			] )
			->get();
		$this->register_responsive_number( $schema, self::ANIM_FADE_OFFSET, self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_FADE_OFFSET ], $fade_offset_deps );

		// Start Scale — show only when fade-from = scale.
		$scale_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::ANIM_EFFECT ],
				'value'    => 'fade',
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::ANIM_FADE_FROM ],
				'value'    => 'scale',
				'effect'   => 'hide',
			] )
			->get();
		$this->register_responsive_number( $schema, self::ANIM_SCALE, self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_SCALE ], $scale_deps );

		/* ---------- 3D move specific ---------- */

		$move_deps = Schema_Helpers::dep_eq( self::ANIM_EFFECT, 'move' );

		$this->register_responsive_string( $schema, self::ANIM_ROTATION_DIR, 'x', $move_deps, [ 'x', 'y' ] );
		$this->register_responsive_number( $schema, self::ANIM_ROTATION, self::RESPONSIVE_NUMBER_SETTINGS[ self::ANIM_ROTATION ], $move_deps );
		$this->register_responsive_string( $schema, self::ANIM_TRANSFORM_ORIGIN, 'top center -50', $move_deps );

		/* ---------- custom effect — properties repeater (React-driven) ---------- */

		$custom_deps = Schema_Helpers::dep_eq( self::ANIM_EFFECT, 'custom' );

		// Data props — read/written by the React UI; never bound to a control directly.
		$schema[ self::ANIM_CUSTOM_PROP_KEYS ] = String_Array_Prop_Type::make()
			->default( [] )
			->set_dependencies( $custom_deps );

		$schema[ self::ANIM_CUSTOM_PROP_VALUES ] = String_Array_Prop_Type::make()
			->default( [] )
			->set_dependencies( $custom_deps );

		// Placeholder boolean — exists only so a panel row is reserved with the
		// label "Custom Properties". editor-bridge/custom-props.jsx finds that
		// row and replaces its editable area with a real property+value repeater.
		$schema[ self::ANIM_CUSTOM_PROPS_TRIGGER ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $custom_deps );

		/* ---------- editor + play button ---------- */

		$schema[ self::ANIM_ENABLE_EDITOR ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $anim_active );

		// Play Animation — only when an effect is animated AND Enable On Editor is ON.
		// Switch_Control expects a Boolean bind; the JS shim in editor-bridge.js
		// replaces its UI row with a "Play Now" button.
		$schema[ self::ANIM_PLAY_TOKEN ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies(
				Dependency_Manager::make( Dependency_Manager::RELATION_AND )
					->where( [
						'operator' => 'in',
						'path'     => [ self::ANIM_EFFECT ],
						'value'    => self::ANIMATED_EFFECTS,
						'effect'   => 'hide',
					] )
					->where( [
						'operator' => 'eq',
						'path'     => [ self::ANIM_ENABLE_EDITOR ],
						'value'    => true,
						'effect'   => 'hide',
					] )
					->get()
			);

		/* ---------- parallax / scroll smoother ---------- */

		$schema[ self::PARALLAX_ENABLE ] = Boolean_Prop_Type::make()->default( false );

		$parallax_active = Schema_Helpers::dep_eq( self::PARALLAX_ENABLE, true );

		$schema[ self::PARALLAX_SPEED ] = Number_Prop_Type::make()->float()
			->default( 0.9 )
			->set_dependencies( $parallax_active );

		$schema[ self::PARALLAX_LAG ] = Number_Prop_Type::make()->float()
			->default( 0 )
			->set_dependencies( $parallax_active );

		return $schema;
	}

	/* ---------- responsive helpers (same pattern as TextAnimation\Schema) ---------- */

	/**
	 * Returns the active extra (non-desktop) breakpoint keys for the current site,
	 * in stable largest→smallest order. Falls back to [tablet, mobile] if the
	 * Breakpoints manager isn't available.
	 */
	public static function get_extra_breakpoints(): array {
		$active_keys = [];

		if ( class_exists( \Elementor\Plugin::class )
			&& isset( \Elementor\Plugin::$instance->breakpoints )
			&& method_exists( \Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints' ) ) {
			$active_keys = array_keys( \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints() );
		}

		if ( empty( $active_keys ) ) {
			$active_keys = [ 'tablet', 'mobile' ];
		}

		$ordered = [];
		foreach ( array_keys( self::BREAKPOINT_LABELS ) as $bp ) {
			if ( in_array( $bp, $active_keys, true ) && 'desktop' !== $bp ) {
				$ordered[] = $bp;
			}
		}
		return $ordered;
	}

	/**
	 * Register a number prop with per-breakpoint variants. Only desktop carries
	 * a default — extras have null so editor-bridge can inherit from parent BP.
	 */
	private function register_responsive_number( array &$schema, string $base, $default, ?array $deps ): void {
		$desktop = Number_Prop_Type::make()->float()->default( $default );
		if ( $deps ) {
			$desktop->set_dependencies( $deps );
		}
		$schema[ $base ] = $desktop;

		foreach ( self::get_extra_breakpoints() as $bp ) {
			$variant = Number_Prop_Type::make()->float();
			if ( $deps ) {
				$variant->set_dependencies( $deps );
			}
			$schema[ $base . '_' . $bp ] = $variant;
		}
	}

	/**
	 * Register a string prop with per-breakpoint variants. Extras intentionally
	 * have no default and no enum constraint so they can temporarily hold any
	 * string while the JS bridge inherits a parent value.
	 */
	private function register_responsive_string( array &$schema, string $base, string $default, ?array $deps, ?array $enum = null ): void {
		$desktop = String_Prop_Type::make()->default( $default );
		if ( $enum ) {
			$desktop->enum( $enum );
		}
		if ( $deps ) {
			$desktop->set_dependencies( $deps );
		}
		$schema[ $base ] = $desktop;

		foreach ( self::get_extra_breakpoints() as $bp ) {
			$variant = String_Prop_Type::make();
			if ( $deps ) {
				$variant->set_dependencies( $deps );
			}
			$schema[ $base . '_' . $bp ] = $variant;
		}
	}

	/* ---------- option lists ---------- */

	public static function effects(): array {
		return [
			'none'   => 'None',
			'fade'   => 'Fade animation',
			'move'   => '3D Move',
			'custom' => 'Custom',
		];
	}

	public static function methods(): array {
		return [
			'from' => 'From',
			'to'   => 'To',
		];
	}

	public static function triggers(): array {
		return [
			'on_scroll'        => 'On Scroll',
			'on_page_load'     => 'On Page Load',
			'play_with_scroll' => 'Play With Scroll',
			'mouseover'        => 'On Hover',
			'click'            => 'On Click',
		];
	}

	public static function fade_directions(): array {
		return [
			'top'    => 'Top',
			'bottom' => 'Bottom',
			'left'   => 'Left',
			'right'  => 'Right',
			'in'     => 'In',
			'scale'  => 'Zoom',
		];
	}

	public static function eases(): array {
		return [
			'power2.out' => 'Power2.out',
			'bounce'     => 'Bounce',
			'back'       => 'Back',
			'elastic'    => 'Elastic',
			'slowmo'     => 'Slowmo',
			'stepped'    => 'Stepped',
			'sine'       => 'Sine',
			'expo'       => 'Expo',
			'none'       => 'None',
		];
	}

	/**
	 * GSAP-compatible properties supported by v3's `aae_ani_custom_props` REPEATER,
	 * as a {value, label} map ready to feed into a Select control's options.
	 * Order matches the v3 dropdown.
	 */
	public static function custom_property_options(): array {
		return [
			[ 'value' => 'none',            'label' => 'None' ],
			[ 'value' => 'opacity',         'label' => 'Opacity' ],
			[ 'value' => 'x',               'label' => 'X' ],
			[ 'value' => 'y',               'label' => 'Y' ],
			[ 'value' => 'width',           'label' => 'Width' ],
			[ 'value' => 'height',          'label' => 'Height' ],
			[ 'value' => 'scale',           'label' => 'Scale' ],
			[ 'value' => 'repeat',          'label' => 'Repeat' ],
			[ 'value' => 'rotate',          'label' => 'Rotate' ],
			[ 'value' => 'rotateX',         'label' => 'RotateX' ],
			[ 'value' => 'rotateY',         'label' => 'RotateY' ],
			[ 'value' => 'transformOrigin', 'label' => 'TransformOrigin' ],
			[ 'value' => 'color',           'label' => 'Color' ],
			[ 'value' => 'background',      'label' => 'Background' ],
			[ 'value' => 'border',          'label' => 'Border' ],
			[ 'value' => 'boxShadow',       'label' => 'BoxShadow' ],
			[ 'value' => 'force3D',         'label' => 'Force3D' ],
			[ 'value' => 'delay',           'label' => 'Delay' ],
			[ 'value' => 'duration',        'label' => 'Duration' ],
			[ 'value' => 'maxWidth',        'label' => 'Max Width' ],
			[ 'value' => 'maxHeight',       'label' => 'Max Height' ],
			[ 'value' => 'minWidth',        'label' => 'Min Width' ],
			[ 'value' => 'minHeight',       'label' => 'Min Height' ],
			[ 'value' => 'mixBlendMode',    'label' => 'Mix Blend Mode' ],
			[ 'value' => 'padding',         'label' => 'Padding' ],
			[ 'value' => 'borderRadius',    'label' => 'Border Radius' ],
			[ 'value' => 'repeatDelay',     'label' => 'Repeat Delay' ],
			[ 'value' => 'scaleX',          'label' => 'ScaleX' ],
			[ 'value' => 'scaleY',          'label' => 'ScaleY' ],
			[ 'value' => 'xPercent',        'label' => 'XPercent' ],
			[ 'value' => 'yPercent',        'label' => 'YPercent' ],
			[ 'value' => 'autoAlpha',       'label' => 'Auto Alpha' ],
			[ 'value' => 'yoyo',            'label' => 'YoYo' ],
		];
	}

	/** Scroll position enum used by Start / End SELECTs. */
	public static function scroll_positions(): array {
		return [
			'top top', 'top center', 'top bottom',
			'center top', 'center center', 'center bottom',
			'bottom top', 'bottom center', 'bottom bottom',
			'custom',
		];
	}
}
