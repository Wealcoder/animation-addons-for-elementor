<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Slider extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-slider';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider';
	}

	public function get_title() {
		return esc_html__( 'AAE Nested Slider', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return [ 'slider', 'nested', 'atomic', 'gsap' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'slides_per_view' => Number_Prop_Type::make()->default( 3 ),
			'gap' => Number_Prop_Type::make()->default( 20 ),
			'speed' => Number_Prop_Type::make()->default( 1000 ),
			'center_mode' => Boolean_Prop_Type::make()->default( false ),
			'enable_3d' => Boolean_Prop_Type::make()->default( false ),
			'perspective' => Number_Prop_Type::make()->default( 1000 ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Slider Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'slides_per_view' )
						->set_label( __( 'Slides Per View', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 10 ),
					Number_Control::bind_to( 'gap' )
						->set_label( __( 'Slide Gap (px)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 100 ),
					Number_Control::bind_to( 'speed' )
						->set_label( __( 'Speed (ms)', 'animation-addons-for-elementor' ) )
						->set_min( 100 ),
					Switch_Control::bind_to( 'center_mode' )
						->set_label( __( 'Center Mode', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'enable_3d' )
						->set_label( __( 'Enable 3D Effect', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'perspective' )
						->set_label( __( '3D Perspective', 'animation-addons-for-elementor' ) )
						->set_min( 100 )
						->set_max( 3000 ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		// We apply base flex styles. Notice we don't bind dynamic gap here, we do it in twig variables.
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'block' ),
			'overflow' => String_Prop_Type::generate( 'hidden' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			'width' => String_Prop_Type::generate( '100%' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Slide::generate()
				->editor_settings( [ 'title' => 'Slide 1' ] )
				->build(),
			AAE_A_Slide::generate()
				->editor_settings( [ 'title' => 'Slide 2' ] )
				->build(),
			AAE_A_Slide::generate()
				->editor_settings( [ 'title' => 'Slide 3' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-slide' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider' => __DIR__ . '/aae-a-slider.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'Draggable', 'aae-a-slider-js' ];
	}
}
