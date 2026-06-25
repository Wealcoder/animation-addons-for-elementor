<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Slider_Counter extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public function should_show_in_panel() {
		return false;
	}

	public static function get_type() {
		return 'e-aae-a-slider-counter';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-counter';
	}

	public function get_title() {
		return esc_html__( 'Slide Counter', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_keywords() {
		return [ 'slider', 'counter', 'current', 'total', 'atomic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$styles = [
			'display'         => String_Prop_Type::generate( 'flex' ),
			'align-items'     => String_Prop_Type::generate( 'center' ),
			'gap'             => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Slider_Current::generate()
				->editor_settings( [ 'title' => 'Current Slide' ] )
				->build(),
			AAE_A_Slider_Divider::generate()
				->editor_settings( [ 'title' => 'Divider' ] )
				->build(),
			AAE_A_Slider_Total::generate()
				->editor_settings( [ 'title' => 'Total Slides' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [
			'e-aae-a-slider-current',
			'e-aae-a-slider-divider',
			'e-aae-a-slider-total',
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-counter' => __DIR__ . '/aae-a-slider-counter.html.twig',
		];
	}
}
