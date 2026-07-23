<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\BtnPro;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * AAE Button Pro — an open atomic container styled like a button.
 */
class AAE_A_Btn_Pro extends Atomic_Element_Base
{
	use Has_Element_Template;

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type()
	{
		return 'e-aae-a-btn-pro';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-btn-pro';
	}

	public function get_title()
	{
		return esc_html__('AAE Button Pro', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'eicon-button';
	}

	public function get_keywords()
	{
		return ['button', 'aae', 'cta', 'call to action', 'atomic', 'link', 'container', 'pro'];
	}

	protected static function define_props_schema(): array
	{
		return [
			'classes'    => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'btn_url'      => String_Prop_Type::make()->default(''),
			'btn_target'   => String_Prop_Type::make()->default('_self'),
			'btn_nofollow' => Boolean_Prop_Type::make()->default(false),
		];
	}

	protected function define_atomic_controls(): array
	{
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label(__('Presets', 'animation-addons-for-elementor'))
				->set_id('aae_presets')
				->set_items([
					AAE_A_Preset_Picker_Control::make()
						->set_label(__('Apply Preset', 'animation-addons-for-elementor'))
						->set_meta(['layout' => 'custom']),
				]),

			Section::make()
				->set_label(__('Button', 'animation-addons-for-elementor'))
				->set_id('content')
				->set_items([
					Text_Control::bind_to('btn_url')
						->set_label(__('URL', 'animation-addons-for-elementor')),

					Select_Control::bind_to('btn_target')
						->set_label(__('Open In', 'animation-addons-for-elementor'))
						->set_options([
							['value' => '_self',  'label' => __('Same Window', 'animation-addons-for-elementor')],
							['value' => '_blank', 'label' => __('New Window',  'animation-addons-for-elementor')],
						]),

					Switch_Control::bind_to('btn_nofollow')
						->set_label(__('Add Nofollow', 'animation-addons-for-elementor')),
				]),

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
		$button_styles = [
			'width'         => Size_Prop_Type::generate(['size' => null, 'unit' => 'auto']),
			'padding'       => Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']),
			'overflow'      => String_Prop_Type::generate('hidden'),
			'position'      => String_Prop_Type::generate('relative'),
			'z-index'       => Number_Prop_Type::generate(10),
			'color'         => Color_Prop_Type::generate('#000000'),

			'border-radius' => Size_Prop_Type::generate(['size' => 4, 'unit' => 'px']),
			'border-width'  => Size_Prop_Type::generate(['size' => 1, 'unit' => 'px']),
			'border-color'  => Color_Prop_Type::generate('#000000'),
			'border-style'  => String_Prop_Type::generate('solid'),

			'transition'    => Transition_Prop_Type::generate([
				Selection_Size_Prop_Type::generate([
					'selection' => Key_Value_Prop_Type::generate([
						'key'   => String_Prop_Type::generate('All properties'),
						'value' => String_Prop_Type::generate('all'),
					]),
					'size' => Size_Prop_Type::generate([
						'size' => 700,
						'unit' => 'ms',
					]),
				]),
			]),

			'display'         => String_Prop_Type::generate('inline-flex'),
			'flex-direction'  => String_Prop_Type::generate('row'),
			'gap'             => Size_Prop_Type::generate(['size' => 8, 'unit' => 'px']),
			'align-items'     => String_Prop_Type::generate('center'),
		];

		$button_hover_styles = [
			'color' => Color_Prop_Type::generate('#ffffff'),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($button_styles))
				// ->add_variant(Style_Variant::make()->set_state(Style_States::HOVER)->add_props($button_hover_styles)),
		];
	}

	protected function define_default_children()
	{
		// Paragraph first so the label sits before the icon in flex order.
		return [
			Atomic_Paragraph::generate()
				->settings([
					'paragraph' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate('Click here'),
						'children' => [],
					]),
					'tag' => String_Prop_Type::generate('span'),
				])
				->build(),
			Atomic_Svg::generate()->build(),
		];
	}

	protected function define_allowed_child_types()
	{
		return [
			'widget',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-button',
			'e-image',
			'e-divider',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag()
	{
		return 'a';
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-btn-pro' => __DIR__ . '/aae-a-btn-pro.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-btn-pro-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-btn-pro-css'];
	}
}
