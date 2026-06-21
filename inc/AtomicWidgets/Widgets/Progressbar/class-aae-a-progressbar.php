<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Progressbar;

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base')) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

class AAE_A_Progressbar extends Atomic_Element_Base
{

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type(): string
	{
		return 'e-aae-a-progressbar';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-progressbar';
	}

	public function get_title(): string
	{
		return esc_html__('AAE Progress Bar', 'animation-addons-for-elementor');
	}

	public function get_icon(): string
	{
		return 'eicon-skill-bar';
	}

	public function get_keywords(): array
	{
		return ['progressbar', 'progress', 'bar', 'circle', 'skill', 'atomic'];
	}

	protected static function define_props_schema(): array
	{
		// Shown when style is NOT dot ('3')
		$not_dot = Dependency_Manager::make()
			->where([
				'operator' => 'nin',
				'path'     => ['pb_style'],
				'value'    => ['3'],
				'effect'   => 'hide',
			])
			->get();

		// Shown ONLY when style IS circle ('2')
		$only_circle = Dependency_Manager::make()
			->where([
				'operator' => 'in',
				'path'     => ['pb_style'],
				'value'    => ['2'],
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			// Content
			'pb_style'              => String_Prop_Type::make()->default('1'),
			'pb_percentage'         => Number_Prop_Type::make()->default(50),
			'pb_display_percentage' => Boolean_Prop_Type::make()->default(true)
				->set_dependencies($not_dot),

			// Progressbar appearance
			'pb_color'              => String_Prop_Type::make()->default('#7DDED8'),
			'pb_bg_color'           => String_Prop_Type::make()->default(''),

			// Line/Circle only
			'pb_stroke_width'       => Number_Prop_Type::make()->default(2)
				->set_dependencies($not_dot),
			'pb_trail_width'        => Number_Prop_Type::make()->default(1)
				->set_dependencies($not_dot),

			// Circle size only
			'pb_size'               => Number_Prop_Type::make()->default(150)
				->set_dependencies($only_circle),
		];
	}

	protected function define_atomic_controls(): array
	{
		return [
			Section::make()
				->set_label(__('Layout', 'animation-addons-for-elementor'))
				->set_id('layout')
				->set_items([
					Select_Control::bind_to('pb_style')
						->set_label(__('Style', 'animation-addons-for-elementor'))
						->set_options([
							['value' => '1', 'label' => __('Line',   'animation-addons-for-elementor')],
							['value' => '2', 'label' => __('Circle', 'animation-addons-for-elementor')],
							['value' => '3', 'label' => __('Dot',    'animation-addons-for-elementor')],
						]),
				]),

			Section::make()
				->set_label(__('Progress Bar', 'animation-addons-for-elementor'))
				->set_id('progressbar')
				->set_items([
					Number_Control::bind_to('pb_percentage')
						->set_label(__('Percentage', 'animation-addons-for-elementor'))
						->set_meta(['min' => 0, 'max' => 100, 'step' => 1]),

					Switch_Control::bind_to('pb_display_percentage')
						->set_label(__('Display Percentage', 'animation-addons-for-elementor')),
				]),

			Section::make()
				->set_label(__('Progress Bar Style', 'animation-addons-for-elementor'))
				->set_id('progressbar_style')
				->set_items([
					Text_Control::bind_to('pb_color')
						->set_label(__('Color', 'animation-addons-for-elementor')),

					Text_Control::bind_to('pb_bg_color')
						->set_label(__('Trail / Background Color', 'animation-addons-for-elementor')),

					Number_Control::bind_to('pb_stroke_width')
						->set_label(__('Stroke Width (em)', 'animation-addons-for-elementor'))
						->set_meta(['min' => 0, 'max' => 20, 'step' => 1]),

					Number_Control::bind_to('pb_trail_width')
						->set_label(__('Trail Width (em)', 'animation-addons-for-elementor'))
						->set_meta(['min' => 0, 'max' => 20, 'step' => 1]),

					Number_Control::bind_to('pb_size')
						->set_label(__('Size (px)', 'animation-addons-for-elementor'))
						->set_meta(['min' => 50, 'max' => 500, 'step' => 1]),
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
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop('display', String_Prop_Type::generate('block'))
						->add_prop('width', String_Prop_Type::generate('100%'))
						->add_prop('position', String_Prop_Type::generate('relative'))
				),
		];
	}

	protected function define_default_children(): array
	{
		return [
			// Editable label — user can change this text in the panel.
			Atomic_Paragraph::generate()
				->settings([
					'paragraph' => \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::generate([
						'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate(''),
						'children' => [],
					]),
					'tag' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate('span'),
				])
				->build(),

			// Percentage counter — JS writes the animated value into this element.
			Atomic_Paragraph::generate()
				->settings([
					'paragraph' => \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::generate([
						'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate('0%'),
						'children' => [],
					]),
					'tag'     => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate('span'),
					'classes' => \Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type::generate(['aae-pb-pct']),
				])
				->build(),
		];
	}

	protected function define_allowed_child_types(): array
	{
		return ['widget', 'e-paragraph', 'e-heading'];
	}

	protected function define_default_html_tag(): string
	{
		return 'div';
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-progressbar' => __DIR__ . '/aae-a-progressbar.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-progressbar-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-progressbar-css'];
	}
}
