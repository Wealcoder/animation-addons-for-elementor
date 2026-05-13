<?php
namespace WCF_ADDONS\Atomic\ImageAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Animation panel — one Section containing one anchor Text_Control.
 * The JS-side <ResponsiveSection> dispatcher matches the anchor's $$type
 * and renders the full responsive tree (Animation / Start From / Scale / …)
 * in place.
 */
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

		$type = $element->get_element_type();

		if ( in_array( $type, Schema::image_animation_widgets(), true ) ) {
			$controls[] = $this->build_section();
		}

		return $controls;
	}

	private function build_section(): Section {
		return Section::make()
			->set_label( __( 'Image Animation', self::TD ) )
			->set_items( [
				Text_Control::bind_to( Schema::IMG_SECTION_ANCHOR ),
			] );
	}
}
