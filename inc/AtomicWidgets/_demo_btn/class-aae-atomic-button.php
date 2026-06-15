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
		return [
			'classes'           => Classes_Prop_Type::make()->default([]),
			'attributes'        => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'btn_text'          => String_Prop_Type::make()->default('Click here'),
			'btn_url'           => String_Prop_Type::make()->default('#'),
			'btn_target'        => String_Prop_Type::make()->default('_self'),
			'btn_nofollow'      => Boolean_Prop_Type::make()->default(false),

			'btn_style'         => String_Prop_Type::make()->default('default'),
			'btn_hover_style'   => String_Prop_Type::make()->default('hover-none'),

			// SVG icon: user picks an SVG file from the media library
			'btn_icon_svg'      => Svg_Src_Prop_Type::make(),

			// CSS class icon: user types e.g. "eicon-arrow-right" or "fas fa-arrow"
			'btn_icon_css'      => String_Prop_Type::make()->default(''),

			'btn_icon_position' => String_Prop_Type::make()->default('left'),
			'btn_icon_size'     => Number_Prop_Type::make()->default(20),
			'btn_icon_color'    => String_Prop_Type::make()->default(''),

			'btn_alignment'     => String_Prop_Type::make()->default('center'),
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
							['value' => 'default',   'label' => __('Default',         'animation-addons-for-elementor')],
							['value' => 'square',    'label' => __('Square',           'animation-addons-for-elementor')],
							['value' => 'underline', 'label' => __('Underline',        'animation-addons-for-elementor')],
							['value' => 'mask',      'label' => __('Mask',             'animation-addons-for-elementor')],
							['value' => 'oval',      'label' => __('Oval',             'animation-addons-for-elementor')],
							['value' => 'circle',    'label' => __('Circle',           'animation-addons-for-elementor')],
							['value' => 'ellipse',   'label' => __('Ellipse',          'animation-addons-for-elementor')],
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

					Text_Control::bind_to('btn_icon_color')
						->set_label(__('Icon Color', 'animation-addons-for-elementor')),

					Select_Control::bind_to('btn_alignment')
						->set_label(__('Alignment', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'left',   'label' => __('Left',   'animation-addons-for-elementor')],
							['value' => 'center', 'label' => __('Center', 'animation-addons-for-elementor')],
							['value' => 'right',  'label' => __('Right',  'animation-addons-for-elementor')],
						]),

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
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop('display', 'inline-flex')
						->add_prop('align-items', 'center')
						->add_prop('justify-content', 'center')
				),
		];
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-atomic-button' => __DIR__ . '/aae-atomic-button.html.twig',
		];
	}

	public function get_style_depends(): array
	{
		return ['wcf--button'];
		return ['aae-a-button-css'];
	}

	/**
	 * Resolve the SVG attachment to inline markup so CSS color/fill can target it.
	 * Falls back to an empty string when the attachment is missing or invalid.
	 */
	public function get_atomic_settings(): array
	{
		$settings = parent::get_atomic_settings();

		$settings['btn_icon_svg_inline'] = '';

		$id = $settings['btn_icon_svg']['id'] ?? null;
		if ($id && class_exists('\Elementor\Core\Files\File_Types\Svg')) {
			$inline = \Elementor\Core\Files\File_Types\Svg::get_inline_svg((int) $id);
			if ($inline) {
				$settings['btn_icon_svg_inline'] = $inline;
			}
		}

		return $settings;
	}

	public function render_markdown(): string
	{
		$settings = $this->get_atomic_settings();
		return esc_html($settings['btn_text'] ?? 'Click here');
	}
}


/*
	I'm creating custom widget for elementor version 4 (automic widget).
	C:\laragon\www\elementor-v4-animation-dev\wp-content\plugins\animation-addons-for-elementor\inc\AtomicWidgets\Widgets\Button

	That was mainly is the clone of the Button previous version widget - 
	C:\laragon\www\elementor-v4-animation-dev\wp-content\plugins\animation-addons-for-elementor\widgets\button.php

	The automic widget Button will get it's own css and js. It won't use the previous widget styles, js.

	for the widget convention you can follow that - 
	C:\laragon\www\elementor-v4-animation-dev\wp-content\plugins\animation-addons-for-elementor\inc\AtomicWidgets\Widgets\Menu


	I've already start to create that, but i'm facing couple of problem.
	- The automic button widget, is using the previous widget's css, js.
	- Builder has option for icon upload, but that icon is not showing on the editor & frontend


















	
	- The style of icons (btn_icon_position, btn_icon_size, btn_icon_color) should be moved on the style tab of the elementor editor (if possible) and 
		use/apply those style through define_base_styles sub-element, twig file (if possible)


	I've created that elementor automic Button.
	Now i've a elementor version 3 widget on a button
	C:\laragon\www\elementor-v4-animation-dev\wp-content\plugins\animation-addons-for-elementor\widgets\button-pro.php

	Those two button (automic and v3 widget) are almost same but v3 widget has some extra property/ style (btn_style).
	Can you copy/apply those extra properety on my automic widget?
	note: please copy the css/ js code also on my automic widget assets (css, js).



*/