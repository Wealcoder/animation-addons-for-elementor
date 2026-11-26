<?php
namespace Wealcoder\AnimationAddons\Atomic\LiquidGlass;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Wealcoder\AnimationAddons\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liquid Glass panel — one Section with one anchor placeholder Text_Control;
 * the JS <ResponsiveSection> renders the real fields (extensions/liquid-glass/config.js).
 */
final class Controls {

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

		if ( in_array( $element->get_element_type(), Schema::target_element_types(), true ) ) {
			$section = Section::make()->set_label( Bootstrap::get_label( __( 'Liquid Glass', 'animation-addons-for-elementor' ) ) );
			$section->set_items( [ Text_Control::bind_to( Schema::SECTION_ANCHOR ) ] );

			$controls[] = $section;
		}

		return $controls;
	}
}
