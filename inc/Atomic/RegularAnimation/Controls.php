<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

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

		if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
			$controls[] = $this->build_animation_section();
		}

		return $controls;
	}

	private function build_animation_section(): Section {
		// Every responsive field (Animation, Method, Trigger, Wrapper, Delay,
		// Duration, Ease, Fade From, Fade Offset, Scale, Rotation, etc.) is
		// rendered by the JS-side <ResponsiveSection> component — see
		// src/modules/atomic/extensions/regular-animation/config.js. This PHP
		// Section places ONE placeholder Text_Control bound to the
		// ANIM_SECTION_ANCHOR sentinel prop; the prop's unique $$type
		// (Section_Anchor_Prop_Type::get_key()) is what the JS dispatcher
		// matches via registerControlReplacement. The anchor row never
		// renders as a real Text_Control — the React swap intercepts it.
		//
		// Custom Properties is a JS-side row inside the responsive section
		// (control: 'repeater'). Markers stays as a native Switch — it never
		// needed responsive treatment.
		return Section::make()
			->set_label( __( 'Animation', self::TD ) )
			->set_items( [

				// Anchor — React replacement renders the full responsive section
				// here, including the Custom Properties repeater row.
				Text_Control::bind_to( Schema::ANIM_SECTION_ANCHOR ),

				/* ---------- non-responsive controls ---------- */

				Switch_Control::bind_to( Schema::ANIM_MARKERS )
					->set_label( __( 'Markers', self::TD ) ),
			] );
	}
}
