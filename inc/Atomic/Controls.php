<?php
namespace WCF_ADDONS\Atomic;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Controls {

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
	}

	public function inject_controls( array $controls, $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $controls;
		}

		if ( ! in_array( $element->get_element_type(), Bootstrap::target_element_types(), true ) ) {
			return $controls;
		}

		if ( ! class_exists( Section::class ) || ! class_exists( Select_Control::class ) ) {
			return $controls;
		}

		$prop = Schema::PROP_KEY;

		$controls[] = Section::make()
			->set_label( __( 'Animation (AAE)', 'animation-addons-for-elementor' ) )
			->set_items( [

				Select_Control::bind_to( $prop . '.effect' )
					->set_label( __( 'Effect', 'animation-addons-for-elementor' ) )
					->set_options( array_map(
						static fn( $name ) => [ 'value' => $name, 'label' => $name ],
						Schema::effects()
					) ),

				Select_Control::bind_to( $prop . '.trigger' )
					->set_label( __( 'Trigger', 'animation-addons-for-elementor' ) )
					->set_options( [
						[ 'value' => 'in-view',         'label' => __( 'On scroll into view', 'animation-addons-for-elementor' ) ],
						[ 'value' => 'page-load',       'label' => __( 'On page load',         'animation-addons-for-elementor' ) ],
						[ 'value' => 'scroll-progress', 'label' => __( 'Scroll progress',      'animation-addons-for-elementor' ) ],
					] ),

				Number_Control::bind_to( $prop . '.duration' )
					->set_label( __( 'Duration (ms)', 'animation-addons-for-elementor' ) ),

				Number_Control::bind_to( $prop . '.delay' )
					->set_label( __( 'Delay (ms)', 'animation-addons-for-elementor' ) ),

				Select_Control::bind_to( $prop . '.easing' )
					->set_label( __( 'Easing', 'animation-addons-for-elementor' ) )
					->set_options( [
						[ 'value' => 'none',       'label' => 'Linear' ],
						[ 'value' => 'power1.out', 'label' => 'Ease Out (soft)' ],
						[ 'value' => 'power2.out', 'label' => 'Ease Out' ],
						[ 'value' => 'power3.out', 'label' => 'Ease Out (strong)' ],
						[ 'value' => 'back.out',   'label' => 'Back Out' ],
						[ 'value' => 'expo.out',   'label' => 'Expo Out' ],
					] ),

				Number_Control::bind_to( $prop . '.repeat' )
					->set_label( __( 'Repeat (-1 = infinite)', 'animation-addons-for-elementor' ) ),
			] );

		return $controls;
	}
}
