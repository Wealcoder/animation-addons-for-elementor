<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Counter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Counter extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Display an animated counter using GSAP. Prefix, animated number, and suffix are independently styleable.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-counter';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-counter';
	}

	public function get_title() {
		return esc_html__( 'Counter', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'counter', 'number', 'animation', 'gsap' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'start_number' => Number_Prop_Type::make()->default( 0 ),
			'duration'     => Number_Prop_Type::make()->default( 2000 ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Counter', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Number_Control::bind_to( 'start_number' )
						->set_label( __( 'Start Number', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'duration' )
						->set_label( __( 'Duration (ms)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 100, 'max' => 10000, 'step' => 100 ] ),
				] ),
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',         String_Prop_Type::generate( 'inline-flex' ) )
						->add_prop( 'align-items',     String_Prop_Type::generate( 'baseline' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',             Size_Prop_Type::generate( [ 'size' => 5, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Prefix' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-prefix' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Number' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-number' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '0' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Suffix' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-suffix' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '+' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-paragraph', 'e-heading' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-counter' => __DIR__ . '/aae-a-counter.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-counter-js' ];
	}
}
