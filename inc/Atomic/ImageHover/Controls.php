<?php
namespace WCF_ADDONS\Atomic\ImageHover;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Image_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Hover panel — mixed Section with native primitives + a responsive
 * anchor.
 *
 *   - Enable + Enable On Editor + Image picker + Z-index render NATIVELY
 *     (non-responsive primitives — Elementor's native controls).
 *   - The anchor Text_Control triggers the React replacement which renders
 *     a <ResponsiveSection> for Width / Height / Top / Left.
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

		if ( in_array( $type, Schema::image_hover_widgets(), true ) ) {
			$controls[] = $this->build_section();
		}

		return $controls;
	}

	private function build_section(): Section {
		return Section::make()
			->set_label( __( 'Image Reveal on Hover', self::TD ) )
			->set_items( [
				// No explicit Enable toggle — picking an image activates the
				// effect. Render / features.js compare the saved URL against
				// the placeholder default to detect "not picked".
				Image_Control::bind_to( Schema::IH_IMAGE )
					->set_label( __( 'Hover Image', self::TD ) ),

				Switch_Control::bind_to( Schema::IH_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) ),

				Number_Control::bind_to( Schema::IH_ZINDEX )
					->set_label( __( 'Z-Index', self::TD ) ),

				// Anchor — React replacement renders Width / Height / Top / Left
				// responsive rows in place of this placeholder.
				Text_Control::bind_to( Schema::IH_SECTION_ANCHOR ),
			] );
	}
}
