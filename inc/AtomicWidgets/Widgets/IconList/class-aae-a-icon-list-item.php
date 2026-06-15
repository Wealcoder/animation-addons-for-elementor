<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\IconList;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

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
		return [];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'inline-flex' ),
			'align-items' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'center' ),
			'padding' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 6, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 6, 'unit' => 'px']),
				'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
			'margin' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			\Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg::generate()
				->build(),
			\Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph::generate()
				->settings( [
					'paragraph' => \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::generate( [
						'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'List Item Text' ),
						'children' => [],
					] ),
					'tag' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading' ];
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
