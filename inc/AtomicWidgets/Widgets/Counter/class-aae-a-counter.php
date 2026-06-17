<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Counter;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type as Style_String;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Base\Widget_Builder;

class AAE_A_Counter extends Atomic_Element_Base
{
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Display an animated counter using GSAP. Contains Prefix, Number, and Suffix child elements.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-counter';
	}

	public function get_title()
	{
		return esc_html__('AAE Atomic Counter', 'animation-addons-for-elementor');
	}

	public static function get_element_type(): string {
		return 'e-aae-a-counter';
	}

	public function get_keywords()
	{
		return ['atomic', 'counter', 'number', 'animation', 'gsap'];
	}

	public function get_icon()
	{
		return 'eicon-counter';
	}

	protected static function define_props_schema(): array
	{
		return [
			'classes' => Classes_Prop_Type::make()
				->default([]),

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
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',          Style_String::generate( 'inline-flex' ) )
						->add_prop( 'align-items',      Style_String::generate( 'center' ) )
						->add_prop( 'justify-content',  Style_String::generate( 'center' ) )
						->add_prop( 'gap',              Style_String::generate( '5px' ) )
						->add_prop( 'padding',          Style_String::generate( '0 8px' ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings([
					'title' => 'Prefix',
				])
				->settings([
					'paragraph' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate('Prefix '),
						'children' => [],
					]),
					'tag' => String_Prop_Type::generate('span'),
				])
				->build(),
				
			Widget_Builder::make( 'e-aae-a-counter-number' )
				->editor_settings([
					'title' => 'Animated Number',
				])
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings([
					'title' => 'Suffix',
				])
				->settings([
					'paragraph' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate(' +'),
						'children' => [],
					]),
					'tag' => String_Prop_Type::generate('span'),
				])
				->build(),
		];
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-counter' => __DIR__ . '/aae-a-counter.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-counter-js'];
	}
}
