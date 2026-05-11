<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
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
			$controls[] = $this->build_section();
		}

		return $controls;
	}

	private function build_section(): Section {
		return Section::make()
			->set_label( __( 'Animation', self::TD ) )
			->set_items( [

				Select_Control::bind_to( Schema::ANIM_EFFECT )
					->set_label( __( 'Effect', self::TD ) )
					->set_options( array_map(
						static fn( $name ) => [ 'value' => $name, 'label' => $name ],
						Schema::effects()
					) ),

				Select_Control::bind_to( Schema::ANIM_TRIGGER )
					->set_label( __( 'Trigger', self::TD ) )
					->set_options( [
						[ 'value' => 'in-view',         'label' => __( 'On scroll into view', self::TD ) ],
						[ 'value' => 'page-load',       'label' => __( 'On page load',         self::TD ) ],
						[ 'value' => 'scroll-progress', 'label' => __( 'Scroll progress',      self::TD ) ],
					] ),

				Number_Control::bind_to( Schema::ANIM_DURATION )
					->set_label( __( 'Duration (ms)', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::ANIM_DELAY )
					->set_label( __( 'Delay (ms)', self::TD ) )
					->set_should_force_int( false ),

				Select_Control::bind_to( Schema::ANIM_EASING )
					->set_label( __( 'Easing', self::TD ) )
					->set_options( [
						[ 'value' => 'none',       'label' => 'Linear' ],
						[ 'value' => 'power1.out', 'label' => 'Ease Out (soft)' ],
						[ 'value' => 'power2.out', 'label' => 'Ease Out' ],
						[ 'value' => 'power3.out', 'label' => 'Ease Out (strong)' ],
						[ 'value' => 'back.out',   'label' => 'Back Out' ],
						[ 'value' => 'expo.out',   'label' => 'Expo Out' ],
					] ),

				Number_Control::bind_to( Schema::ANIM_REPEAT )
					->set_label( __( 'Repeat (-1 = infinite)', self::TD ) ),

				Switch_Control::bind_to( Schema::ANIM_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) )
					->set_description( __( 'For better performance in editor mode, keep the setting turned off.', self::TD ) ),

				// Marker control — replaced in the panel by a "Play Now" button via editor-bridge.js.
				Switch_Control::bind_to( Schema::ANIM_PLAY_TOKEN )
					->set_label( __( 'Play Animation', self::TD ) )
					->set_meta( [ 'aaePlayButton' => true ] ),
			] );
	}
}
