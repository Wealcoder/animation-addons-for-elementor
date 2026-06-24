<?php
namespace WCF_ADDONS\Atomic\NestedSlider;

use Elementor\Modules\AtomicWidgets\Controls\Section;

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

		// The "Slider Settings" section (anchor control + ID field) is now built
		// directly in AAE_A_Slider::define_atomic_controls() so the ID control can
		// use the widget's protected get_css_id_control_meta() and sit in the same
		// section. Nothing to inject here for the slider.

		return $controls;
	}
}
