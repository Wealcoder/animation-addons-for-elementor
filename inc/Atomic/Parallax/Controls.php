<?php
namespace WCF_ADDONS\Atomic\Parallax;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parallax panel — a single Section containing one anchor placeholder
 * Text_Control. The JS-side <ResponsiveSection> dispatcher matches the
 * anchor prop's $$type (Section_Anchor_Prop_Type::get_key()) and renders
 * the full responsive tree (Enable / Speed / Lag) in place.
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

		if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
			$controls[] = $this->build_parallax_section();
		}

		return $controls;
	}

	private function build_parallax_section(): Section {
		return Section::make()
			->set_label( Bootstrap::get_label( __( 'Parallax Effect', self::TD ) ) )
			->set_items( [
				// Anchor — React replacement renders Enable / Speed / Lag rows.
				Text_Control::bind_to( Schema::PARALLAX_SECTION_ANCHOR ),
			] );
	}
}
