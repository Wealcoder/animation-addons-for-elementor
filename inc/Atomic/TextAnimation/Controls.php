<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TextAnimation panel controls.
 *
 * Every responsive field is rendered by the JS-side <ResponsiveSection>
 * component — see src/modules/atomic/extensions/text-animation/config.js.
 * To wire it in, this Section places ONE placeholder Text_Control bound
 * to the TEXT_SECTION_ANCHOR sentinel prop; the prop's unique $$type
 * (Section_Anchor_Prop_Type::get_key()) is what the JS dispatcher matches
 * via registerControlReplacement. The anchor row never renders as a real
 * Text_Control — the React swap intercepts and the full responsive section
 * is rendered in its place.
 *
 * Non-responsive items (Markers, Spin Color, Spin Toggle, Enable On Editor,
 * Play Animation) stay as native controls. Their visibility is always-on
 * for now — the dep chains that gated them targeted responsive props and
 * had to be dropped (the JS section is the source of truth for
 * conditional visibility).
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

		if ( ! class_exists( Section::class ) || ! class_exists( Select_Control::class ) ) {
			return $controls;
		}

		$type = $element->get_element_type();

		if ( in_array( $type, Schema::text_animation_widgets(), true ) ) {
			$controls[] = $this->build_text_animation_section();
		}

		return $controls;
	}

	private function build_text_animation_section(): Section {
		return Section::make()
			->set_label( __( 'Text Animation', self::TD ) )
			->set_items( [
				// Anchor — React replacement renders the full responsive section here.
				Text_Control::bind_to( Schema::TEXT_SECTION_ANCHOR ),		

				
			] );
	}
}
