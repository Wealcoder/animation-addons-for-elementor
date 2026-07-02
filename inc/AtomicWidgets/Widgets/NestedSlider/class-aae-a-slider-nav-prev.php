<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (! class_exists('\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base')) {
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
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Box_Shadow_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Slider_Nav_Prev extends Atomic_Element_Base {
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'The prev navigation arrow for the nested slider.';

	public function should_show_in_panel() {
		return false; 
	}

	protected function define_default_children() {
		return [
			\Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg::generate()
				->settings( [
					'svg' => \Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type::generate( [
						'id' => null,
						'url' => \Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/NestedSlider/assets/icon/prev.svg' ),
					] ),
				] )
				->build()
		];
	}

	public static function get_type() {
		return 'e-aae-a-slider-nav-prev';
	}

	public function get_title() {
		return esc_html__( 'Slider Prev Nav', 'animation-addons-for-elementor' );
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-nav-prev';
	}

	public function get_keywords() {
		return [ 'slider', 'navigator', 'prev', 'atomic' ];
	}

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public function get_icon() {
		return 'eicon-chevron-left';
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
				->set_items( [
					Text_Control::bind_to('_cssid')
						->set_label(__('ID', 'animation-addons-for-elementor'))
						->set_meta($this->get_css_id_control_meta()),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$styles = [
			'position' => String_Prop_Type::generate( 'absolute' ),
			'inset-block-start' => Size_Prop_Type::generate([ 'size' => 50, 'unit' => '%' ]),
			'inset-inline-start' => Size_Prop_Type::generate([ 'size' => 20, 'unit' => 'px' ]),
			'z-index' => Number_Prop_Type::generate( 10 ),
			'width' => Size_Prop_Type::generate([ 'size' => 40, 'unit' => 'px' ]),
			'height' => Size_Prop_Type::generate([ 'size' => 40, 'unit' => 'px' ]),
			'background' => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate( '#ffffff' )
			]),
			'color' => Color_Prop_Type::generate( '#333333' ),
			'border-radius' => Size_Prop_Type::generate([ 'size' => 50, 'unit' => '%' ]),
			'display' => String_Prop_Type::generate( 'flex' ),
			'align-items' => String_Prop_Type::generate( 'center' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'cursor' => String_Prop_Type::generate( 'pointer' ),
		];

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props($styles) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-nav-prev' => __DIR__ . '/aae-a-slider-nav-prev.html.twig',
		];
	}
}
