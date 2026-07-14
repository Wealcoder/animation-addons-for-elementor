<?php

namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\Bootstrap;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightbox panel section.
 *
 * `build_section()` is the single definition of the section. It is used two ways:
 *   - auto-injected onto core elements (e-image) via inject_controls()
 *   - returned to custom AAE widgets by Lightbox_Manager::register_lightbox_controls()
 *
 * so both paths get an identical editing experience with one code path.
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

		if ( in_array( $type, Schema::lightbox_widgets(), true ) ) {
			$controls[] = $this->build_section();
		}

		return $controls;
	}

	/**
	 * The shared "Lightbox" section. Native controls are used for Phase 1 so
	 * the section works without a React replacement; the anchor is kept so a
	 * richer responsive panel can be swapped in later exactly like the other
	 * effects (ImageHover, Tilt, …).
	 *
	 * @param array $args Reserved for per-widget overrides (label, defaults).
	 */
	public function build_section( array $args = [] ): Section {
		$label = $args['label'] ?? __( 'Lightbox', self::TD );

		return Section::make()
			->set_id( 'aae_lightbox' )
			->set_label( Bootstrap::get_label( $label ) )
			->set_items( [
				// NOTE: no Section_Anchor here. The anchor pattern is only for
				// effects that swap in a React ResponsiveSection replacement; the
				// lightbox uses native controls, and an unpaired custom-layout
				// anchor makes the atomic panel fail save validation.
				Switch_Control::bind_to( Schema::LB_ENABLE )
					->set_label( __( 'Enable Lightbox', self::TD ) ),

				Text_Control::bind_to( Schema::LB_GROUP )
					->set_label( __( 'Gallery / Group ID', self::TD ) )
					->set_placeholder( __( 'e.g. my-gallery (leave blank for single)', self::TD ) ),

				Text_Control::bind_to( Schema::LB_TITLE )
					->set_label( __( 'Title', self::TD ) ),

				Text_Control::bind_to( Schema::LB_CAPTION )
					->set_label( __( 'Caption', self::TD ) ),

				Select_Control::bind_to( Schema::LB_ANIM )
					->set_label( __( 'Open Animation', self::TD ) )
					->set_options( [
						[ 'value' => 'zoom', 'label' => __( 'Zoom', self::TD ) ],
						[ 'value' => 'fade', 'label' => __( 'Fade', self::TD ) ],
						[ 'value' => 'slide', 'label' => __( 'Slide', self::TD ) ],
					] ),
			] );
	}
}
