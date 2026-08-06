<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\IconList;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Icon_List_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true ); // Container for nested SVG and Paragraph
	}

	public static function get_type() {
		return 'e-aae-a-icon-list-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-icon-list-item';
	}

	public function get_title() {
		return esc_html__( 'Icon List Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_keywords() {
		return [ 'list', 'item', 'icon', 'bullet', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Only added via parent Icon List
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
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display'     => String_Prop_Type::generate( 'inline-flex' ),
			'align-items' => String_Prop_Type::generate( 'center' ),
			'padding'     => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			] ),
			'margin'      => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function define_default_children() {
		$svg = Atomic_Svg::generate()
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'e-aae-icon-list-item-icon' ] ),
			] )
			->build();

		$paragraph = Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'List Item Text' ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [ $svg, $paragraph ];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading','e-flexbox' ];
	}

	protected function define_default_html_tag() {
		return 'li';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-icon-list-item' => __DIR__ . '/aae-a-icon-list-item.html.twig',
		];
	}
}
