<?php

namespace WCF_ADDONS\Atomic\BackgroundVideo;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background Video — panel section.
 *
 * One placeholder row bound to the section anchor. Everything the user actually
 * sees is rendered by the editor bridge, which matches the anchor's $$type and
 * replaces this row with the <ResponsiveSection> built from
 * extensions/background-video/config.js. Adding a field is a config.js + Schema
 * job; this file does not change.
 */
final class Controls {

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
	}

	public function inject_controls( array $controls, $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $controls;
		}

		if ( ! in_array( $element->get_element_type(), Schema::TARGET_TYPES, true ) ) {
			return $controls;
		}

		$controls[] = $this->build_section();

		return $controls;
	}

	private function build_section(): Section {
		return Section::make()
			->set_label( Bootstrap::get_label( __( 'Background Video', 'animation-addons-for-elementor' ) ) )
			->set_items( [
				Text_Control::bind_to( Schema::SECTION_ANCHOR ),
			] );
	}
}
