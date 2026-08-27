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

class AAE_A_Slider_Nav_Next extends Atomic_Element_Base {
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'The next navigation arrow for the nested slider.';

	public function should_show_in_panel() {
		return false; 
	}

	protected function define_default_children() {
		return [
			\Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg::generate()
				->settings( [
					// aae-a-svg (StyleManager utility) sets a sane 20px default so the
					// arrow isn't the core Atomic_Svg 65px default inside the nav badge.
					// It's a plain utility class, so the user's own Size (SVG Style tab)
					// still overrides it — icon-size changes now take effect.
					'classes' => \Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type::generate( [ 'aae-a-svg' ] ),
					'svg' => \Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type::generate( [
						'id' => null,
						'url' => \Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/NestedSlider/assets/icon/next.svg' ),
					] ),
				] )
				->build()
		];
	}

	public static function get_type() {
		return 'e-aae-a-slider-nav-next';
	}

	public function get_title() {
		return esc_html__( 'Slider Next Nav', 'animation-addons-for-elementor' );
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-nav-next';
	}

	public function get_keywords() {
		return [ 'slider', 'navigator', 'next', 'atomic' ];
	}

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public function get_icon() {
		return 'eicon-chevron-right';
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
			'z-index' => Number_Prop_Type::generate( 10 ),
			// FIXED round badge — NOT icon-driven. The nav's SVG child renders at
			// different sizes depending on the element's age: a fresh drop carries the
			// `aae-a-svg` utility (20px) while an element saved before that class existed
			// falls back to the core Atomic_Svg 65px default. A `fit-content` button
			// therefore came out 40px on fresh drops but ~80px on old-saved ones, so the
			// same slider looked one size in the editor and another on the frontend (the
			// reported editor↔frontend mismatch). Pinning width/height to a constant 44px
			// makes the badge identical everywhere regardless of the icon size; the SVG
			// is contained to fit inside via each slider stylesheet's `.aae-a-navigator-*
			// svg { max-width/height }` rule. A fixed width also can't stretch into an
			// ellipse when the slider is a single 100%-wide slide (the older spv=1 bug).
			'width' => Size_Prop_Type::generate([ 'size' => 44, 'unit' => 'px' ]),
			'height' => Size_Prop_Type::generate([ 'size' => 44, 'unit' => 'px' ]),
			'padding' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => Size_Prop_Type::generate([ 'size' => 8, 'unit' => 'px' ]),
				'block-end' => Size_Prop_Type::generate([ 'size' => 8, 'unit' => 'px' ]),
				'inline-start' => Size_Prop_Type::generate([ 'size' => 8, 'unit' => 'px' ]),
				'inline-end' => Size_Prop_Type::generate([ 'size' => 8, 'unit' => 'px' ]),
			]),
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
			'elementor/elements/aae-a-slider-nav-next' => __DIR__ . '/aae-a-slider-nav-next.html.twig',
		];
	}
}
