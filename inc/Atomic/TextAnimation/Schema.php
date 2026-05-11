<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

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

	/* ---- scroll trigger settings (wrapper=custom + scroll/play_with_scroll) ---- */
	const TEXT_START_TRIGGER  = 'aae_text_start_trigger';   // v3 aae_anim_txt_s_t
	const TEXT_END_TRIGGER    = 'aae_text_end_trigger';     // v3 aae_anim_txt_e_t
	const TEXT_START_POSITION = 'aae_text_start_position';  // v3 aae_anim_txt_s
	const TEXT_START_CUSTOM   = 'aae_text_start_custom';    // v3 aae_anim_txt_s_cus
	const TEXT_END_POSITION   = 'aae_text_end_position';    // v3 aae_anim_txt_e
	const TEXT_END_CUSTOM     = 'aae_text_end_custom';      // v3 aae_anim_txt_e_cus
	const TEXT_MARKERS        = 'aae_text_markers';         // v3 aae_anim_txt_markers (boolean)

	/* ---- text-invert specific ---- */
	const TEXT_INVERT_START = 'aae_text_invert_start';  // v3 aae_anim_invert_s
	const TEXT_INVERT_END   = 'aae_text_invert_end';    // v3 aae_anim_invert_e

	/* ---- text-spin specific ---- */
	const TEXT_SPIN_COLOR  = 'aae_text_spin_color';   // v3 spin_text_color (hex string)
	const TEXT_SPIN_START  = 'aae_text_spin_start';   // v3 spin_text_start
	const TEXT_SPIN_END    = 'aae_text_spin_end';     // v3 spin_text_end
	const TEXT_SPIN_TOGGLE = 'aae_text_spin_toggle';  // v3 spin_text_toggle_action

	/* ---- cross-cutting (animated except text_invert + scroll trigger) ---- */
	const TEXT_SCRUB = 'aae_text_scrub';  // v3 spin_text_scrub (boolean)

	/* ---- text-scale specific ---- */
	const TEXT_SCALE_EASE  = 'aae_text_scale_ease';   // v3 scale_text_ease
	const TEXT_SCALE_NUM   = 'aae_text_scale_num';    // v3 text_scale_num
	const TEXT_SCALE_BREAK = 'aae_text_scale_break';  // v3 text_scale_break

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
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		/* ---------- regular animation ---------- */

		$schema[ self::ANIM_EFFECT ] = String_Prop_Type::make()
			->enum( self::effects() )
			->default( 'none' );

		$anim_active = $this->dep_ne( self::ANIM_EFFECT, 'none' );

		$schema[ self::ANIM_TRIGGER ] = String_Prop_Type::make()
			->enum( [ 'in-view', 'page-load', 'scroll-progress' ] )
			->default( 'in-view' )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_DURATION ] = Number_Prop_Type::make()->float()
			->default( 600 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_DELAY ] = Number_Prop_Type::make()->float()
			->default( 0 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_EASING ] = String_Prop_Type::make()
			->enum( [ 'none', 'power1.out', 'power2.out', 'power3.out', 'back.out', 'expo.out' ] )
			->default( 'power2.out' )
			->set_dependencies( $anim_active );

		// Repeat count is integer — no .float() needed.
		$schema[ self::ANIM_REPEAT ] = Number_Prop_Type::make()
			->default( 0 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_ENABLE_EDITOR ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $anim_active );

		// Play Animation — only when an effect is selected AND Enable On Editor is ON.
		// Switch_Control expects a Boolean bind; the JS shim in editor-bridge.js
		// replaces its UI row with a "Play Now" button.
		$schema[ self::ANIM_PLAY_TOKEN ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies(
				Dependency_Manager::make( Dependency_Manager::RELATION_AND )
					->where( [
						'operator' => 'ne',
						'path'     => [ self::ANIM_EFFECT ],
						'value'    => 'none',
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

		/* ---------- text animation ---------- */

		// EFFECT: top-level driver of all gating. Cannot itself depend on anything.
		// Non-responsive — the chosen effect type is the same across all devices.
		$schema[ self::TEXT_EFFECT ] = String_Prop_Type::make()
			->enum( array_keys( self::text_effects() ) )
			->default( 'none' );

		$text_active = $this->dep_in( self::TEXT_EFFECT, self::TEXT_ANIMATED_EFFECTS );

		$schema[ self::TEXT_TRIGGER ] = String_Prop_Type::make()
			->enum( array_keys( self::text_triggers() ) )
			->default( 'on_scroll' )
			->set_dependencies( $text_active );

		// Show only when trigger is hover/click AND an animation is selected.
		$trigger_selector_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_EFFECT ],  'value' => self::TEXT_ANIMATED_EFFECTS,    'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_TRIGGER ], 'value' => [ 'mouseover', 'click' ],       'effect' => 'hide' ] )
			->get();
		$schema[ self::TEXT_TRIGGER_SELECTOR ] = String_Prop_Type::make()
			->default( '' )
			->set_dependencies( $trigger_selector_deps );

		// Wrapper picker shows when trigger is on_scroll / play_with_scroll AND animation is selected.
		$wrapper_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_EFFECT ],  'value' => self::TEXT_ANIMATED_EFFECTS,           'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_TRIGGER ], 'value' => [ 'on_scroll', 'play_with_scroll' ], 'effect' => 'hide' ] )
			->get();
		$schema[ self::TEXT_WRAPPER ] = String_Prop_Type::make()
			->enum( [ 'default', 'custom' ] )
			->default( 'default' )
			->set_dependencies( $wrapper_deps );

		// Custom wrapper selector only when wrapper = custom.
		$schema[ self::TEXT_WRAPPER_SELECTOR ] = String_Prop_Type::make()
			->default( '' )
			->set_dependencies( $this->dep_eq( self::TEXT_WRAPPER, 'custom' ) );

		$duration_deps  = $this->dep_in( self::TEXT_EFFECT, self::TEXT_DURATION_EFFECTS );
		$translate_deps = $this->dep_in( self::TEXT_EFFECT, self::TEXT_TRANSLATE_EFFECTS );
		$text_move_deps = $this->dep_eq( self::TEXT_EFFECT, 'text_move' );

		$schema[ self::TEXT_DELAY ]       = Number_Prop_Type::make()->float()->default( 0.15 )->set_dependencies( $text_active );
		$schema[ self::TEXT_DURATION ]    = Number_Prop_Type::make()->float()->default( 1 )->set_dependencies( $duration_deps );
		$schema[ self::TEXT_STAGGER ]     = Number_Prop_Type::make()->float()->default( 0.02 )->set_dependencies( $duration_deps );
		$schema[ self::TEXT_TRANSLATE_X ] = Number_Prop_Type::make()->float()->default( 20 )->set_dependencies( $translate_deps );
		$schema[ self::TEXT_TRANSLATE_Y ] = Number_Prop_Type::make()->float()->default( 0 )->set_dependencies( $translate_deps );

		$schema[ self::TEXT_ROTATION_DIR ] = String_Prop_Type::make()
			->enum( [ 'x', 'y' ] )
			->default( 'x' )
			->set_dependencies( $text_move_deps );

		$schema[ self::TEXT_ROTATION ] = Number_Prop_Type::make()->float()
			->default( -80 )
			->set_dependencies( $text_move_deps );

		$schema[ self::TEXT_TRANSFORM_ORIGIN ] = String_Prop_Type::make()
			->default( 'top center -50' )
			->set_dependencies( $text_move_deps );

		$schema[ self::TEXT_ENABLE_EDITOR ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $text_active );

		// Play Animation — only when a text effect is animated AND Enable On Editor is ON.
		// Switch_Control expects a Boolean bind; the JS shim in editor-bridge.js
		// replaces its UI row with a "Play Now" button.
		$schema[ self::TEXT_PLAY_TOKEN ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies(
				Dependency_Manager::make( Dependency_Manager::RELATION_AND )
					->where( [
						'operator' => 'in',
						'path'     => [ self::TEXT_EFFECT ],
						'value'    => self::TEXT_ANIMATED_EFFECTS,
						'effect'   => 'hide',
					] )
					->where( [
						'operator' => 'eq',
						'path'     => [ self::TEXT_ENABLE_EDITOR ],
						'value'    => true,
						'effect'   => 'hide',
					] )
					->get()
			);

		/* ---------- scroll trigger settings (wrapper=custom + scroll/play_with_scroll) ---------- */

		// Show only when: effect is animated AND trigger in [on_scroll, play_with_scroll] AND wrapper = custom.
		$scroll_custom_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'in',
				'path'     => [ self::TEXT_EFFECT ],
				'value'    => self::TEXT_ANIMATED_EFFECTS,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'in',
				'path'     => [ self::TEXT_TRIGGER ],
				'value'    => [ 'on_scroll', 'play_with_scroll' ],
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::TEXT_WRAPPER ],
				'value'    => 'custom',
				'effect'   => 'hide',
			] )
			->get();

		$schema[ self::TEXT_START_TRIGGER ]  = String_Prop_Type::make()->default( '' )->set_dependencies( $scroll_custom_deps );
		$schema[ self::TEXT_END_TRIGGER ]    = String_Prop_Type::make()->default( '' )->set_dependencies( $scroll_custom_deps );
		$schema[ self::TEXT_START_POSITION ] = String_Prop_Type::make()->enum( self::scroll_positions() )->default( 'top top' )->set_dependencies( $scroll_custom_deps );
		$schema[ self::TEXT_END_POSITION ]   = String_Prop_Type::make()->enum( self::scroll_positions() )->default( 'bottom top' )->set_dependencies( $scroll_custom_deps );

		// Custom Start/End text inputs — show ONLY when corresponding position = 'custom'.
		$custom_start_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_EFFECT ],          'value' => self::TEXT_ANIMATED_EFFECTS,           'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_TRIGGER ],         'value' => [ 'on_scroll', 'play_with_scroll' ],   'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_WRAPPER ],         'value' => 'custom',                              'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_START_POSITION ],  'value' => 'custom',                              'effect' => 'hide' ] )
			->get();
		$schema[ self::TEXT_START_CUSTOM ] = String_Prop_Type::make()->default( 'top top' )->set_dependencies( $custom_start_deps );

		$custom_end_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_EFFECT ],         'value' => self::TEXT_ANIMATED_EFFECTS,           'effect' => 'hide' ] )
			->where( [ 'operator' => 'in', 'path' => [ self::TEXT_TRIGGER ],        'value' => [ 'on_scroll', 'play_with_scroll' ],   'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_WRAPPER ],        'value' => 'custom',                              'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_END_POSITION ],   'value' => 'custom',                              'effect' => 'hide' ] )
			->get();
		$schema[ self::TEXT_END_CUSTOM ] = String_Prop_Type::make()->default( 'bottom top' )->set_dependencies( $custom_end_deps );

		// Markers boolean — non-responsive in v3, same gating as scroll-custom block.
		$schema[ self::TEXT_MARKERS ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $scroll_custom_deps );

		/* ---------- text-invert specific (effect = text_invert) ---------- */

		$invert_deps = $this->dep_eq( self::TEXT_EFFECT, 'text_invert' );
		$schema[ self::TEXT_INVERT_START ] = String_Prop_Type::make()->default( 'top 85%' )->set_dependencies( $invert_deps );
		$schema[ self::TEXT_INVERT_END ]   = String_Prop_Type::make()->default( 'bottom center' )->set_dependencies( $invert_deps );

		/* ---------- text-spin specific (effect = text_spin) ---------- */

		$spin_deps = $this->dep_eq( self::TEXT_EFFECT, 'text_spin' );

		// Spin color — single hex string, not responsive (v3 had no responsive on it).
		$schema[ self::TEXT_SPIN_COLOR ] = String_Prop_Type::make()
			->default( '' )
			->set_dependencies( $spin_deps );

		// Spin Start / End / Toggle Action — only on spin + on_scroll trigger.
		$spin_scroll_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_EFFECT ],  'value' => 'text_spin', 'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ self::TEXT_TRIGGER ], 'value' => 'on_scroll', 'effect' => 'hide' ] )
			->get();
		$schema[ self::TEXT_SPIN_START ] = String_Prop_Type::make()->default( 'top 50%' )->set_dependencies( $spin_scroll_deps );
		$schema[ self::TEXT_SPIN_END ]   = String_Prop_Type::make()->default( 'bottom 30%' )->set_dependencies( $spin_scroll_deps );

		// Toggle action is non-responsive in v3.
		$schema[ self::TEXT_SPIN_TOGGLE ] = String_Prop_Type::make()
			->default( 'play none none reverse' )
			->set_dependencies( $spin_scroll_deps );

		/* ---------- cross-cutting: Scrub (animated except text_invert + trigger=on_scroll) ---------- */

		$scrub_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'in',
				'path'     => [ self::TEXT_EFFECT ],
				'value'    => array_values( array_diff( self::TEXT_ANIMATED_EFFECTS, [ 'text_invert' ] ) ),
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'eq',
				'path'     => [ self::TEXT_TRIGGER ],
				'value'    => 'on_scroll',
				'effect'   => 'hide',
			] )
			->get();

		$schema[ self::TEXT_SCRUB ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $scrub_deps );

		/* ---------- text-scale specific (effect = text_scale) ---------- */

		$scale_deps = $this->dep_eq( self::TEXT_EFFECT, 'text_scale' );

		$schema[ self::TEXT_SCALE_EASE ]  = String_Prop_Type::make()->enum( array_keys( self::scale_eases() ) )->default( 'back' )->set_dependencies( $scale_deps );
		$schema[ self::TEXT_SCALE_NUM ]   = Number_Prop_Type::make()->float()->default( 1.5 )->set_dependencies( $scale_deps );
		$schema[ self::TEXT_SCALE_BREAK ] = String_Prop_Type::make()->enum( array_keys( self::scale_break_modes() ) )->default( 'lines' )->set_dependencies( $scale_deps );

		return $schema;
	}

	/* ---------- dependency helpers ---------- */

	private function dep_eq( string $source, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ $source ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	private function dep_ne( string $source, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'ne',
				'path'     => [ $source ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	private function dep_in( string $source, array $values ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ $source ],
				'value'    => $values,
				'effect'   => 'hide',
			] )
			->get();
	}

	/* ---------- option lists ---------- */

	public static function effects(): array {
		return [
			'none', 'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
			'slideUp', 'slideDown', 'zoomIn', 'zoomOut', 'rotateIn', 'flipInX', 'flipInY',
		];
	}

	public static function text_effects(): array {
		return [
			'none'        => 'None',
			'char'        => 'Character',
			'word'        => 'Word',
			'text_move'   => 'Text Move',
			'text_reveal' => 'Text Reveal',
			'text_scale'  => 'Text Scale',
			'text_invert' => 'Text Invert',
			'text_spin'   => '3D Spin',
		];
	}

	public static function text_triggers(): array {
		return [
			'on_scroll'        => 'On Scroll',
			'on_page_load'     => 'On Page Load',
			'play_with_scroll' => 'Play With Scroll',
			'mouseover'        => 'On Hover',
			'click'            => 'On Click',
		];
	}

	public static function text_animation_widgets(): array {
		return [ 'e-heading' ];
	}

	/** Scroll position enum used by Start / End position SELECTs. */
	public static function scroll_positions(): array {
		return [
			'top top', 'top center', 'top bottom',
			'center top', 'center center', 'center bottom',
			'bottom top', 'bottom center', 'bottom bottom',
			'custom',
		];
	}

	/** Easing options for text_scale animation. */
	public static function scale_eases(): array {
		return [
			'power2.out' => 'Power2.out',
			'bounce'     => 'Bounce',
			'back'       => 'Back',
			'elastic'    => 'Elastic',
			'slowmo'     => 'Slowmo',
			'stepped'    => 'Stepped',
			'sine'       => 'Sine',
			'expo'       => 'Expo',
		];
	}

	/** Text-break modes for text_scale animation. */
	public static function scale_break_modes(): array {
		return [
			'lines' => 'Lines',
			'words' => 'Words',
			'chars' => 'Chars',
		];
	}
}
