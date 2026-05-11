<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

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
		$effect_opts        = $this->options_from_map( Schema::text_effects() );
		$trigger_opts       = $this->options_from_map( Schema::text_triggers() );
		$wrapper_opts       = [
			[ 'value' => 'default', 'label' => __( 'Default', self::TD ) ],
			[ 'value' => 'custom',  'label' => __( 'Custom',  self::TD ) ],
		];
		$rot_dir_opts       = [
			[ 'value' => 'x', 'label' => 'X' ],
			[ 'value' => 'y', 'label' => 'Y' ],
		];
		$scroll_pos_opts    = array_map(
			static fn( $v ) => [ 'value' => $v, 'label' => ucwords( str_replace( '_', ' ', $v ) ) ],
			Schema::scroll_positions()
		);
		$scale_ease_opts    = $this->options_from_map( Schema::scale_eases() );
		$scale_break_opts   = $this->options_from_map( Schema::scale_break_modes() );

		return Section::make()
			->set_label( __( 'Text Animation', self::TD ) )
			->set_items( [

				Select_Control::bind_to( Schema::TEXT_EFFECT )
					->set_label( __( 'Animation', self::TD ) )
					->set_options( $effect_opts ),

				Select_Control::bind_to( Schema::TEXT_TRIGGER )
					->set_label( __( 'Trigger', self::TD ) )
					->set_options( $trigger_opts ),

				Text_Control::bind_to( Schema::TEXT_TRIGGER_SELECTOR )
					->set_label( __( 'Trigger Selector', self::TD ) )
					->set_placeholder( '.my-class' ),

				Select_Control::bind_to( Schema::TEXT_WRAPPER )
					->set_label( __( 'Text Wrapper', self::TD ) )
					->set_options( $wrapper_opts ),

				Text_Control::bind_to( Schema::TEXT_WRAPPER_SELECTOR )
					->set_label( __( 'Custom Wrapper Selector', self::TD ) )
					->set_placeholder( '.my-wrapper' ),

				/* Scroll trigger settings — wrapper=custom + scroll trigger */
				Text_Control::bind_to( Schema::TEXT_START_TRIGGER )
					->set_label( __( 'Start Trigger', self::TD ) )
					->set_placeholder( '.start_area' ),

				Text_Control::bind_to( Schema::TEXT_END_TRIGGER )
					->set_label( __( 'End Trigger', self::TD ) )
					->set_placeholder( '.end_area' ),

				Select_Control::bind_to( Schema::TEXT_START_POSITION )
					->set_label( __( 'Start', self::TD ) )
					->set_options( $scroll_pos_opts ),

				Text_Control::bind_to( Schema::TEXT_START_CUSTOM )
					->set_label( __( 'Custom Start', self::TD ) )
					->set_placeholder( 'top top+=100' ),

				Select_Control::bind_to( Schema::TEXT_END_POSITION )
					->set_label( __( 'End', self::TD ) )
					->set_options( $scroll_pos_opts ),

				Text_Control::bind_to( Schema::TEXT_END_CUSTOM )
					->set_label( __( 'Custom End', self::TD ) )
					->set_placeholder( 'bottom top+=100' ),

				Switch_Control::bind_to( Schema::TEXT_MARKERS )
					->set_label( __( 'Markers', self::TD ) ),

				/* Text-invert specific */
				Text_Control::bind_to( Schema::TEXT_INVERT_START )
					->set_label( __( 'Invert Start', self::TD ) )
					->set_placeholder( 'top 85%' ),

				Text_Control::bind_to( Schema::TEXT_INVERT_END )
					->set_label( __( 'Invert End', self::TD ) )
					->set_placeholder( 'bottom center' ),

				/* Text-spin specific */
				Text_Control::bind_to( Schema::TEXT_SPIN_COLOR )
					->set_label( __( 'Spin Text Color', self::TD ) )
					->set_placeholder( '#ff0000' ),

				Text_Control::bind_to( Schema::TEXT_SPIN_START )
					->set_label( __( 'Spin Start', self::TD ) )
					->set_placeholder( 'top 50%' ),

				Text_Control::bind_to( Schema::TEXT_SPIN_END )
					->set_label( __( 'Spin End', self::TD ) )
					->set_placeholder( 'bottom 30%' ),

				Text_Control::bind_to( Schema::TEXT_SPIN_TOGGLE )
					->set_label( __( 'Toggle Actions', self::TD ) )
					->set_placeholder( 'play none none reverse' ),

				/* Scrub — animated (not text_invert) + scroll trigger */
				Switch_Control::bind_to( Schema::TEXT_SCRUB )
					->set_label( __( 'Scrub', self::TD ) ),

				/* Numeric settings */
				Number_Control::bind_to( Schema::TEXT_DELAY )
					->set_label( __( 'Delay', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::TEXT_DURATION )
					->set_label( __( 'Duration', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::TEXT_STAGGER )
					->set_label( __( 'Stagger', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::TEXT_TRANSLATE_X )
					->set_label( __( 'Transform-X', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::TEXT_TRANSLATE_Y )
					->set_label( __( 'Transform-Y', self::TD ) )
					->set_should_force_int( false ),

				Select_Control::bind_to( Schema::TEXT_ROTATION_DIR )
					->set_label( __( 'Rotation Direction', self::TD ) )
					->set_options( $rot_dir_opts ),

				Number_Control::bind_to( Schema::TEXT_ROTATION )
					->set_label( __( 'Rotation Value', self::TD ) )
					->set_should_force_int( false ),

				Text_Control::bind_to( Schema::TEXT_TRANSFORM_ORIGIN )
					->set_label( __( 'Transform Origin', self::TD ) )
					->set_placeholder( 'top center -50' ),

				/* Text-scale specific */
				Select_Control::bind_to( Schema::TEXT_SCALE_EASE )
					->set_label( __( 'Scale Ease', self::TD ) )
					->set_options( $scale_ease_opts ),

				Number_Control::bind_to( Schema::TEXT_SCALE_NUM )
					->set_label( __( 'Scale', self::TD ) )
					->set_should_force_int( false ),

				Select_Control::bind_to( Schema::TEXT_SCALE_BREAK )
					->set_label( __( 'Text Break By', self::TD ) )
					->set_options( $scale_break_opts ),

				Switch_Control::bind_to( Schema::TEXT_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) )
					->set_description( __( 'For better performance in editor mode, keep the setting turned off.', self::TD ) ),

				// Marker control — replaced in the panel by a "Play Now" button via editor-bridge.js.
				Switch_Control::bind_to( Schema::TEXT_PLAY_TOKEN )
					->set_label( __( 'Play Animation', self::TD ) )
					->set_meta( [ 'aaePlayButton' => true ] ),
			] );
	}

	private function options_from_map( array $map ): array {
		$out = [];
		foreach ( $map as $value => $label ) {
			$out[] = [ 'value' => $value, 'label' => $label ];
		}
		return $out;
	}
}
	