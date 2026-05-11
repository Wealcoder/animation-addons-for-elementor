<?php
namespace WCF_ADDONS\Atomic;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Controls {

	const TD = 'animation-addons-for-elementor';

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
	}

	public function inject_controls( array $controls, $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $controls;
		}

		if ( ! class_exists( Section::class ) || ! class_exists( Select_Control::class ) ) {
			return $controls;
		}

		$type = $element->get_element_type();

		// Text-animation widgets (e.g. e-heading) get the Text Animation section
		// AND the regular Animation section. All other supported widgets get
		// only the regular Animation section.
		if ( in_array( $type, Schema::text_animation_widgets(), true ) ) {
			$controls[] = $this->build_text_animation_section();
		}

		if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
			$controls[] = $this->build_animation_section();
		}

		return $controls;
	}

	private function build_animation_section(): Section {
		return Section::make()
			->set_label( __( 'Animation', self::TD ) )
			->set_items( [

				Select_Control::bind_to( Schema::ANIM_EFFECT )
					->set_label( __( 'Effect', self::TD ) )
					->set_options( array_map(
						static fn( $name ) => [ 'value' => $name, 'label' => $name ],
						Schema::effects()
					) ),

				Select_Control::bind_to( Schema::ANIM_TRIGGER )
					->set_label( __( 'Trigger', self::TD ) )
					->set_options( [
						[ 'value' => 'in-view',         'label' => __( 'On scroll into view', self::TD ) ],
						[ 'value' => 'page-load',       'label' => __( 'On page load',         self::TD ) ],
						[ 'value' => 'scroll-progress', 'label' => __( 'Scroll progress',      self::TD ) ],
					] ),

				Number_Control::bind_to( Schema::ANIM_DURATION )
					->set_label( __( 'Duration (ms)', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::ANIM_DELAY )
					->set_label( __( 'Delay (ms)', self::TD ) )
					->set_should_force_int( false ),

				Select_Control::bind_to( Schema::ANIM_EASING )
					->set_label( __( 'Easing', self::TD ) )
					->set_options( [
						[ 'value' => 'none',       'label' => 'Linear' ],
						[ 'value' => 'power1.out', 'label' => 'Ease Out (soft)' ],
						[ 'value' => 'power2.out', 'label' => 'Ease Out' ],
						[ 'value' => 'power3.out', 'label' => 'Ease Out (strong)' ],
						[ 'value' => 'back.out',   'label' => 'Back Out' ],
						[ 'value' => 'expo.out',   'label' => 'Expo Out' ],
					] ),

				Number_Control::bind_to( Schema::ANIM_REPEAT )
					->set_label( __( 'Repeat (-1 = infinite)', self::TD ) ),

				Switch_Control::bind_to( Schema::ANIM_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) )
					->set_description( __( 'For better performance in editor mode, keep the setting turned off.', self::TD ) ),

				// Marker control — replaced in the panel by a "Play Now" button via editor-bridge.js.
				Switch_Control::bind_to( Schema::ANIM_PLAY_TOKEN )
					->set_label( __( 'Play Animation', self::TD ) )
					->set_meta( [ 'aaePlayButton' => true ] ),
			] );
	}

	private function build_text_animation_section(): Section {
		$effect_opts   = $this->options_from_map( Schema::text_effects() );
		$trigger_opts  = $this->options_from_map( Schema::text_triggers() );
		$wrapper_opts  = [
			[ 'value' => 'default', 'label' => __( 'Default', self::TD ) ],
			[ 'value' => 'custom',  'label' => __( 'Custom',  self::TD ) ],
		];
		$rot_dir_opts  = [
			[ 'value' => 'x', 'label' => 'X' ],
			[ 'value' => 'y', 'label' => 'Y' ],
		];

		return Section::make()
			->set_label( __( 'Text Animation', self::TD ) )
			->set_items( [

				/* Animation effect — responsive */
				...$this->responsive_rows( Schema::TEXT_EFFECT, __( 'Animation', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $effect_opts ) ),

				/* Trigger — responsive */
				...$this->responsive_rows( Schema::TEXT_TRIGGER, __( 'Trigger', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $trigger_opts ) ),

				/* Trigger Selector — responsive */
				...$this->responsive_rows( Schema::TEXT_TRIGGER_SELECTOR, __( 'Trigger Selector', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.my-class' ) ),

				/* Text Wrapper — responsive */
				...$this->responsive_rows( Schema::TEXT_WRAPPER, __( 'Text Wrapper', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $wrapper_opts ) ),

				/* Custom Wrapper Selector — responsive */
				...$this->responsive_rows( Schema::TEXT_WRAPPER_SELECTOR, __( 'Custom Wrapper Selector', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.my-wrapper' ) ),

				/* Numeric responsive settings */
				...$this->responsive_number( Schema::TEXT_DELAY,       __( 'Delay',       self::TD ) ),
				...$this->responsive_number( Schema::TEXT_DURATION,    __( 'Duration',    self::TD ) ),
				...$this->responsive_number( Schema::TEXT_STAGGER,     __( 'Stagger',     self::TD ) ),
				...$this->responsive_number( Schema::TEXT_TRANSLATE_X, __( 'Transform-X', self::TD ) ),
				...$this->responsive_number( Schema::TEXT_TRANSLATE_Y, __( 'Transform-Y', self::TD ) ),

				/* Rotation Direction — responsive */
				...$this->responsive_rows( Schema::TEXT_ROTATION_DIR, __( 'Rotation Direction', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $rot_dir_opts ) ),

				/* Rotation Value — responsive */
				...$this->responsive_number( Schema::TEXT_ROTATION, __( 'Rotation Value', self::TD ) ),

				/* Transform Origin — responsive */
				...$this->responsive_rows( Schema::TEXT_TRANSFORM_ORIGIN, __( 'Transform Origin', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top center -50' ) ),

				Switch_Control::bind_to( Schema::TEXT_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) )
					->set_description( __( 'For better performance in editor mode, keep the setting turned off.', self::TD ) ),

				// Marker control — replaced in the panel by a "Play Now" button via editor-bridge.js.
				Switch_Control::bind_to( Schema::TEXT_PLAY_TOKEN )
					->set_label( __( 'Play Animation', self::TD ) )
					->set_meta( [ 'aaePlayButton' => true ] ),
			] );
	}

	/**
	 * Builds an array of Number_Controls for one responsive setting:
	 * one row for desktop, plus one row per active extra breakpoint.
	 */
	private function responsive_number( string $base, string $label ): array {
		return $this->responsive_rows( $base, $label, function ( $prop, $row_label ) {
			return Number_Control::bind_to( $prop )
				->set_label( $row_label )
				->set_should_force_int( false );
		} );
	}

	/**
	 * Generic responsive-rows builder. Given a control factory $make($prop, $label),
	 * returns rows for desktop + each active extra breakpoint. The factory is
	 * called once per breakpoint with the per-breakpoint prop name and label.
	 */
	private function responsive_rows( string $base, string $label, callable $make ): array {
		$rows = [ $make( $base, $label ) ];

		foreach ( Schema::get_extra_breakpoints() as $bp ) {
			$bp_label = Schema::BREAKPOINT_LABELS[ $bp ] ?? ucwords( str_replace( '_', ' ', $bp ) );
			$prop     = Schema::breakpoint_prop( $base, $bp );
			$rows[]   = $make( $prop, $label . ' (' . $bp_label . ')' );
		}

		return $rows;
	}

	private function options_from_map( array $map ): array {
		$out = [];
		foreach ( $map as $value => $label ) {
			$out[] = [ 'value' => $value, 'label' => $label ];
		}
		return $out;
	}
}
