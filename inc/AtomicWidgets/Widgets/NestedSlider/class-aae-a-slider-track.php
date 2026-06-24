<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Slider_Track extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
		$this->meta( 'permanently_locked', true );
	}
	public static function generate() {
		return parent::generate()->is_locked( true );
	}

	public static function get_type() {
		return 'e-aae-a-slider-track';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-track';
	}

	public function get_title() {
		return esc_html__( 'Slider Track', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return [ 'slider', 'track', 'atomic', 'gsap' ];
	}
    
	public function should_show_in_panel() {
		return false; 
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-slide' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [] ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-track' => __DIR__ . '/aae-a-slider-track.html.twig',
		];
	}
}
