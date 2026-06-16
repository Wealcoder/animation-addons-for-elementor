<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Button;

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base')) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

class AAE_Atomic_Button extends Atomic_Widget_Base
{

	use Has_Template;

	public static function get_element_type(): string
	{
		return 'e-aae-atomic-button';
	}

	public function get_title()
	{
		return esc_html__('AAE Button', 'animation-addons-for-elementor');
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
		$icon_hidden = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => ['btn_show_icon'],
				'value'    => true,
				'effect'   => 'hide',
			])
			->get();

		$pro_style_values = ['pro-1', 'pro-2', 'pro-3', 'pro-4', 'pro-5', 'pro-6', 'pro-7', 'pro-8'];
		$is_classic_style = Dependency_Manager::make()
			->where([
				'operator' => 'nin',
				'path'     => ['btn_style'],
				'value'    => $pro_style_values,
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes'           => Classes_Prop_Type::make()->default([]),
			'attributes'        => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'btn_text'          => String_Prop_Type::make()->default('Click here'),
			'btn_url'           => String_Prop_Type::make()->default('#'),
			'btn_target'        => String_Prop_Type::make()->default('_self'),
			'btn_nofollow'      => Boolean_Prop_Type::make()->default(false),

			'btn_style'         => String_Prop_Type::make()->default('default'),
			'btn_hover_style'   => String_Prop_Type::make()->default('hover-none')
				->set_dependencies($is_classic_style),

			'btn_show_icon'     => Boolean_Prop_Type::make()->default(false),

			'btn_icon_svg'      => Svg_Src_Prop_Type::make()
				->set_dependencies($icon_hidden),

			'btn_icon_css'      => String_Prop_Type::make()->default('')
				->set_dependencies($icon_hidden),

			'btn_icon_position' => String_Prop_Type::make()->default('left')
				->set_dependencies($icon_hidden),

			'btn_icon_size'     => Number_Prop_Type::make()->default(20)
				->set_dependencies($icon_hidden),

			// 'btn_icon_color'    => String_Prop_Type::make()->default('')
			// 	->set_dependencies($icon_hidden),

			'btn_outline_gap'   => Number_Prop_Type::make()->default(10),

			'btn_alignment'     => String_Prop_Type::make()->default('left'),

			// Color props — output as CSS custom properties via Twig inline style
			'btn_color'              => String_Prop_Type::make()->default(''),
			'btn_bg_color'           => String_Prop_Type::make()->default(''),
			'btn_border_color'       => String_Prop_Type::make()->default(''),
			'btn_hover_color'        => String_Prop_Type::make()->default(''),
			'btn_hover_bg_color'     => String_Prop_Type::make()->default(''),
			// 'btn_hover_icon_color'     => String_Prop_Type::make()->default(''),
			'btn_hover_border_color' => String_Prop_Type::make()->default(''),
		];
	}

	protected function define_atomic_controls(): array
	{
		return [
			Section::make()
				->set_label(__('Button', 'animation-addons-for-elementor'))
				->set_id('content')
				->set_items([

					Select_Control::bind_to('btn_style')
						->set_label(__('Style', 'animation-addons-for-elementor'))
						->set_options([
							// Classic styles
							['value' => 'default',   'label' => __('Default',         'animation-addons-for-elementor')],
							['value' => 'square',    'label' => __('Square',           'animation-addons-for-elementor')],
							['value' => 'underline', 'label' => __('Underline',        'animation-addons-for-elementor')],
							['value' => 'mask',      'label' => __('Mask',             'animation-addons-for-elementor')],
							// ['value' => 'oval',      'label' => __('Oval',             'animation-addons-for-elementor')],
							// ['value' => 'circle',    'label' => __('Circle',           'animation-addons-for-elementor')],
							// ['value' => 'ellipse',   'label' => __('Ellipse',          'animation-addons-for-elementor')],
							// Pro styles (from Advanced Button)
							['value' => 'pro-1',     'label' => __('1-Border Divide', 'animation-addons-for-elementor')],
							['value' => 'pro-2',     'label' => __('2-Shadow',        'animation-addons-for-elementor')],
							['value' => 'pro-3',     'label' => __('3-Text Flip',     'animation-addons-for-elementor')],
							['value' => 'pro-4',     'label' => __('4-Ripple',        'animation-addons-for-elementor')],
							['value' => 'pro-5',     'label' => __('5-Group Swap L',  'animation-addons-for-elementor')],
							['value' => 'pro-6',     'label' => __('6-Group Swap R',  'animation-addons-for-elementor')],
							['value' => 'pro-7',     'label' => __('7-Outline Pill',  'animation-addons-for-elementor')],
							['value' => 'pro-8',     'label' => __('8-Slide Fill',    'animation-addons-for-elementor')],
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

					Text_Control::bind_to('btn_text')
						->set_label(__('Text', 'animation-addons-for-elementor')),

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
				->set_label(__('Icon', 'animation-addons-for-elementor'))
				->set_id('btn_icon_tab')
				->set_items([
					Switch_Control::bind_to('btn_show_icon')
						->set_label(__('Show Icon', 'animation-addons-for-elementor')),

					// SVG picker — opens the WP media library filtered to SVGs
					Svg_Control::bind_to('btn_icon_svg')
						->set_label(__('Icon (SVG)', 'animation-addons-for-elementor')),

					// Fallback: Font Awesome / Elementor icon CSS class
					Text_Control::bind_to('btn_icon_css')
						->set_label(__('Icon CSS Class', 'animation-addons-for-elementor')),

					Select_Control::bind_to('btn_icon_position')
						->set_label(__('Icon Position', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'left',  'label' => __('Before', 'animation-addons-for-elementor')],
							['value' => 'right', 'label' => __('After',  'animation-addons-for-elementor')],
						]),

					Number_Control::bind_to('btn_icon_size')
						->set_label(__('Icon Size (px)', 'animation-addons-for-elementor'))
						->set_meta(['min' => 8, 'max' => 200, 'step' => 1]),

					// Text_Control::bind_to('btn_icon_color')
					// 	->set_label(__('Icon Color', 'animation-addons-for-elementor')),
				]),

			Section::make()
				->set_label(__('Hover Colors', 'animation-addons-for-elementor'))
				->set_id('btn_hv_colors_tab')
				->set_items([
					// Text_Control::bind_to('btn_color')
					// 	->set_label(__('Text Color', 'animation-addons-for-elementor')),
					// Text_Control::bind_to('btn_bg_color')
					// 	->set_label(__('Background Color', 'animation-addons-for-elementor')),
					// Text_Control::bind_to('btn_border_color')
					// 	->set_label(__('Border Color', 'animation-addons-for-elementor')),
					Text_Control::bind_to('btn_hover_color')
						->set_label(__('Hover Text Color', 'animation-addons-for-elementor')),
					Text_Control::bind_to('btn_hover_bg_color')
						->set_label(__('Hover Background', 'animation-addons-for-elementor')),
					// Text_Control::bind_to('btn_hover_icon_color')
					// 	->set_label(__('Hover Background', 'animation-addons-for-elementor')),
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
			'display'         => String_Prop_Type::generate( 'inline-flex' ),
			'align-items'     => String_Prop_Type::generate( 'center' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'font-size'       => String_Prop_Type::generate( '16px' ),
			'font-weight'     => String_Prop_Type::generate( '500' ),
			'line-height'     => String_Prop_Type::generate( '1' ),
			'text-decoration' => String_Prop_Type::generate( 'none' ),
			'cursor'          => String_Prop_Type::generate( 'pointer' ),
			'transition'      => String_Prop_Type::generate( 'all 0.3s' ),
			'outline'         => String_Prop_Type::generate( 'none' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $button_styles ) ),
		];
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-atomic-button' => __DIR__ . '/aae-atomic-button.html.twig',
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

	public function render_markdown(): string
	{
		$settings = $this->get_atomic_settings();
		return esc_html($settings['btn_text'] ?? 'Click here');
	}
}
