<?php
namespace WCF_ADDONS\Atomic\NestedSlider;

use Elementor\Modules\AtomicWidgets\Controls\Section;
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

		if ( ! class_exists( Section::class ) ) {
			return $controls;
		}

		// Apply the slider settings section specifically to the nested slider widget.
		if ( 'e-aae-a-slider' === $element->get_element_type() ) {
			$controls[] = $this->build_slider_section();
		}

		return $controls;
	}

	private function build_slider_section(): Section {
		return Section::make()
			->set_label( __( 'Slider Settings', self::TD ) )
			->set_items( [
				Text_Control::bind_to( Schema::SLIDER_SECTION_ANCHOR ),
			] );
	}
}
