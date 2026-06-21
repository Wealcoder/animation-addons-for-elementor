<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (! class_exists('\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base')) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
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
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Slider_Pagination extends Atomic_Widget_Base
{
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'The pagination dots for the nested slider.';

	public function get_title()
	{
		return esc_html__('Pagination', 'animation-addons-for-elementor');
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-pagination';
	}

	public function get_keywords()
	{
		return ['atomic', 'slider', 'pagination', 'dots'];
	}

	public function get_icon()
	{
		return 'eicon-ellipsis-h';
	}

	protected static function define_props_schema(): array
	{
		return [
			'classes' => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),
		];
	}

	protected function define_atomic_controls(): array
	{
		return [
			Section::make()
				->set_label(__('Settings', 'animation-addons-for-elementor'))
				->set_id('settings')
				->set_items([
					Text_Control::bind_to('_cssid')
						->set_label(__('ID', 'animation-addons-for-elementor'))
						->set_meta($this->get_css_id_control_meta()),
				]),
		];
	}

	protected function define_base_styles(): array
	{
		$wrapper_styles = [
			'position' => String_Prop_Type::generate( 'absolute' ),
			'bottom' => Size_Prop_Type::generate([ 'size' => -40, 'unit' => 'px' ]),
			'left' => Size_Prop_Type::generate([ 'size' => 0, 'unit' => 'px' ]),
			'width' => String_Prop_Type::generate( '100%' ),
			'display' => String_Prop_Type::generate( 'flex' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'gap' => Size_Prop_Type::generate([ 'size' => 8, 'unit' => 'px' ]),
			'pointer-events' => String_Prop_Type::generate( 'none' ),
			'z-index' => String_Prop_Type::generate( '10' ),
		];

		$dot_styles = [
			'width' => Size_Prop_Type::generate([ 'size' => 12, 'unit' => 'px' ]),
			'height' => Size_Prop_Type::generate([ 'size' => 12, 'unit' => 'px' ]),
			'background' => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate( '#cccccc' )
			]),
			'border-radius' => Size_Prop_Type::generate([ 'size' => 50, 'unit' => '%' ]),
			'pointer-events' => String_Prop_Type::generate( 'auto' ),
			'cursor' => String_Prop_Type::generate( 'pointer' ),
			'transition' => String_Prop_Type::generate( 'background-color 0.3s ease' ),
		];

		$dot_active_styles = [
			'background' => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate( '#375EFB' )
			]),
		];

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props($wrapper_styles) ),
			self::BASE_STYLE_KEY . ' .aae-a-pagination-dot' => Style_Definition::make()
				->set_label( __( 'Dots', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props($dot_styles) )
				->add_variant( Style_Variant::make()
					->set_state('active')
					->add_props($dot_active_styles)
				),
		];
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-slider-pagination' => __DIR__ . '/aae-a-slider-pagination.html.twig',
		];
	}
}
