<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageCompare;

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
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Image_Compare extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A draggable before/after image comparison slider. Each image, caption and button is an independent atomic child you can style from its own Style panel.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-image-compare';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-image-compare';
	}

	public function get_title() {
		return esc_html__( 'AAE Image Compare', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'image', 'compare', 'before', 'after', 'slider' ];
	}

	public function get_icon() {
		return 'eicon-image-before-after';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'           => Classes_Prop_Type::make()->default( [] ),
			'attributes'        => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'default_position'  => Number_Prop_Type::make()->default( 50 ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Image Compare', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Number_Control::bind_to( 'default_position' )
						->set_label( __( 'Default Handle Position (%)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 0, 'max' => 100, 'step' => 1 ] ),
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
						->add_prop( 'position',         String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'overflow',         String_Prop_Type::generate( 'hidden' ) )
						->add_prop( 'width',            String_Prop_Type::generate( '100%' ) )
						->add_prop( 'user-select',      String_Prop_Type::generate( 'none' ) )
						->add_prop( 'min-height',       Size_Prop_Type::generate( [ 'size' => 200, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function define_default_children() {
		return [
		// 1. BEFORE image (clipped left side).
		// Image prop omitted on purpose — Atomic_Image supplies its own
		// placeholder by default; the user replaces it from the Style panel.
		Atomic_Image::generate()
			->editor_settings( [ 'title' => 'After Image' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-after' ] ),
			] )
			->build(),

		Atomic_Image::generate()
			->editor_settings( [ 'title' => 'Before Image' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-before' ] ),
			] )
			->build(),

			// 3. BEFORE caption.
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Caption Before' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-before' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Before' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

		// 4. AFTER caption.
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Caption After' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-after' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'After' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-image', 'e-paragraph', 'e-button' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-image-compare' => __DIR__ . '/aae-a-image-compare.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-image-compare-js' ];
	}
}
