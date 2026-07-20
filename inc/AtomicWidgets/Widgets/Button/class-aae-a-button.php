<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Button;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

if (! defined('ABSPATH')) {
	exit;
}

class AAE_A_Button extends Atomic_Element_Base
{
	use Has_Element_Template;

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type()
	{
		return 'e-aae-a-button';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-button';
	}

	public function get_title()
	{
		return esc_html__('AAE Button New', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'wcf-icon-Button';
	}

	public function get_keywords()
	{
		return ['button', 'cta', 'call to action', 'atomic', 'link'];
	}

	protected static function define_props_schema(): array
	{
		$indpendent_style_values = ['pro-1', 'pro-2', 'pro-3', 'pro-7', 'pro-8', 'underline', 'mask'];
		$is_classic_style = Dependency_Manager::make()
			->where([
				'operator' => 'nin',
				'path'     => ['btn_style'],
				'value'    => $indpendent_style_values,
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'btn_url'      => String_Prop_Type::make()->default('#'),
			'btn_target'   => String_Prop_Type::make()->default('_self'),
			'btn_nofollow' => Boolean_Prop_Type::make()->default(false),

			'btn_style'       => String_Prop_Type::make()->default('default'),
			'btn_hover_style' => String_Prop_Type::make()->default('hover-none')
				->set_dependencies($is_classic_style),

			'btn_outline_gap' => Number_Prop_Type::make()->default(10),
			'btn_alignment'   => String_Prop_Type::make()->default('left'),

			'btn_hover_color'        => String_Prop_Type::make()->default(''),
			'btn_hover_bg_color'     => String_Prop_Type::make()->default(''),
			'btn_hover_border_color' => String_Prop_Type::make()->default(''),
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
					Select_Control::bind_to('btn_style')
						->set_label(__('Style', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'default',   'label' => __('Default',         'animation-addons-for-elementor')],
							['value' => 'square',    'label' => __('Square',           'animation-addons-for-elementor')],
							['value' => 'underline', 'label' => __('Underline',        'animation-addons-for-elementor')],
							['value' => 'mask',      'label' => __('Mask',             'animation-addons-for-elementor')],
							['value' => 'pro-1',     'label' => __('1-Border Divide',  'animation-addons-for-elementor')],
							['value' => 'pro-2',     'label' => __('2-Shadow',         'animation-addons-for-elementor')],
							['value' => 'pro-3',     'label' => __('3-Text Flip',      'animation-addons-for-elementor')],
							['value' => 'pro-7',     'label' => __('7-Outline Pill',   'animation-addons-for-elementor')],
							['value' => 'pro-8',     'label' => __('8-Slide Fill',     'animation-addons-for-elementor')],
						]),

					Select_Control::bind_to('btn_hover_style')
						->set_label(__('Hover Style', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'hover-none',      'label' => __('None',            'animation-addons-for-elementor')],
							['value' => 'hover-divide',    'label' => __('Divided',         'animation-addons-for-elementor')],
							['value' => 'hover-cross',     'label' => __('Cross',           'animation-addons-for-elementor')],
							['value' => 'hover-cropping',  'label' => __('Cropping',        'animation-addons-for-elementor')],
							['value' => 'rollover-top',    'label' => __('Rollover Top',    'animation-addons-for-elementor')],
							['value' => 'rollover-left',   'label' => __('Rollover Left',   'animation-addons-for-elementor')],
							['value' => 'parallal-border', 'label' => __('Parallel Border', 'animation-addons-for-elementor')],
							['value' => 'rollover-cross',  'label' => __('Rollover Cross',  'animation-addons-for-elementor')],
						]),

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

					Number_Control::bind_to('btn_outline_gap')
						->set_label(__('Outline Gap (px)', 'animation-addons-for-elementor'))
						->set_meta(['min' => 0, 'max' => 20, 'step' => 1]),

					Select_Control::bind_to('btn_alignment')
						->set_label(__('Alignment', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'left',   'label' => __('Left',   'animation-addons-for-elementor')],
							['value' => 'center', 'label' => __('Center', 'animation-addons-for-elementor')],
							['value' => 'right',  'label' => __('Right',  'animation-addons-for-elementor')],
						]),
				]),

			Section::make()
				->set_label(__('Hover Colors', 'animation-addons-for-elementor'))
				->set_id('btn_hv_colors_tab')
				->set_items([
					Text_Control::bind_to('btn_hover_color')
						->set_label(__('Hover Text Color', 'animation-addons-for-elementor')),
					Text_Control::bind_to('btn_hover_bg_color')
						->set_label(__('Hover Background', 'animation-addons-for-elementor')),
					Text_Control::bind_to('btn_hover_border_color')
						->set_label(__('Hover Border Color', 'animation-addons-for-elementor')),
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
			// Layout
			'display'         => String_Prop_Type::generate('inline-flex'),
			'align-items'     => String_Prop_Type::generate('center'),
			'justify-content' => String_Prop_Type::generate('center'),
			'column-gap'      => Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']),
			'width'           => Size_Prop_Type::generate(['size' => null, 'unit' => 'auto']),

			'padding' => Dimensions_Prop_Type::generate([
				'block-start'  => Size_Prop_Type::generate(['size' => 17, 'unit' => 'px']),
				'block-end'    => Size_Prop_Type::generate(['size' => 17, 'unit' => 'px']),
				'inline-start' => Size_Prop_Type::generate(['size' => 35, 'unit' => 'px']),
				'inline-end'   => Size_Prop_Type::generate(['size' => 35, 'unit' => 'px']),
			]),

			// Typography
			'font-size'       => String_Prop_Type::generate('16px'),
			'font-weight'     => String_Prop_Type::generate('500'),
			'line-height'     => String_Prop_Type::generate('1'),
			'text-decoration' => String_Prop_Type::generate('none'),

			// Interaction
			'cursor'          => String_Prop_Type::generate('pointer'),
			'transition'      => String_Prop_Type::generate('all 0.3s'),
			'outline'         => String_Prop_Type::generate('none'),
			'color' => Color_Prop_Type::generate('#000'),

			// 			Background_Prop_Type::generate([
			// ])

		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($button_styles)),
		];
	}

	protected function define_default_children()
	{
		// Paragraph first so text appears before the icon in flex order —
		// matches every pro style where text is left and icon/arrow is right.
		return [
			Atomic_Paragraph::generate()
				->settings([
					'paragraph' => \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::generate([
						'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate('Click here'),
						'children' => [],
					]),
					'tag' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate('span'),
				])
				->build(),
			Atomic_Svg::generate()->build(),
		];
	}

	protected function define_allowed_child_types()
	{
		return ['widget', 'e-svg', 'e-paragraph', 'e-heading', 'e-image'];
	}

	protected function define_default_html_tag()
	{
		return 'a';
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-button' => __DIR__ . '/aae-a-button.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-button-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-button-css'];
	}
}
