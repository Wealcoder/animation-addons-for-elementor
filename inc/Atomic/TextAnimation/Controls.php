<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

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

		if ( in_array( $type, Schema::text_animation_widgets(), true ) ) {
			$controls[] = $this->build_text_animation_section();
		}

		return $controls;
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

				/* Scroll trigger settings — only when wrapper=custom + scroll trigger */
				...$this->responsive_rows( Schema::TEXT_START_TRIGGER, __( 'Start Trigger', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.start_area' ) ),

				...$this->responsive_rows( Schema::TEXT_END_TRIGGER, __( 'End Trigger', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.end_area' ) ),

				...$this->responsive_rows( Schema::TEXT_START_POSITION, __( 'Start', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scroll_pos_opts ) ),

				...$this->responsive_rows( Schema::TEXT_START_CUSTOM, __( 'Custom Start', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top top+=100' ) ),

				...$this->responsive_rows( Schema::TEXT_END_POSITION, __( 'End', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scroll_pos_opts ) ),

				...$this->responsive_rows( Schema::TEXT_END_CUSTOM, __( 'Custom End', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'bottom top+=100' ) ),

				Switch_Control::bind_to( Schema::TEXT_MARKERS )
					->set_label( __( 'Markers', self::TD ) ),

				/* Text-invert specific — only when effect=text_invert */
				...$this->responsive_rows( Schema::TEXT_INVERT_START, __( 'Invert Start', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top 85%' ) ),

				...$this->responsive_rows( Schema::TEXT_INVERT_END, __( 'Invert End', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'bottom center' ) ),

				/* Text-spin specific — only when effect=text_spin */
				Text_Control::bind_to( Schema::TEXT_SPIN_COLOR )
					->set_label( __( 'Spin Text Color', self::TD ) )
					->set_placeholder( '#ff0000' ),

				...$this->responsive_rows( Schema::TEXT_SPIN_START, __( 'Spin Start', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top 50%' ) ),

				...$this->responsive_rows( Schema::TEXT_SPIN_END, __( 'Spin End', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'bottom 30%' ) ),

				Text_Control::bind_to( Schema::TEXT_SPIN_TOGGLE )
					->set_label( __( 'Toggle Actions', self::TD ) )
					->set_placeholder( 'play none none reverse' ),

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

				/* Text-scale specific — only when effect=text_scale */
				...$this->responsive_rows( Schema::TEXT_SCALE_EASE, __( 'Scale Ease', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scale_ease_opts ) ),

				...$this->responsive_number( Schema::TEXT_SCALE_NUM, __( 'Scale', self::TD ) ),

				...$this->responsive_rows( Schema::TEXT_SCALE_BREAK, __( 'Text Break By', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scale_break_opts ) ),

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
