<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\PostMeta;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-post-meta-item.php';
use WCF_ADDONS\AtomicWidgets\Widgets\PostMeta\AAE_A_Post_Meta_Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Meta extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-meta';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-meta';
	}

	public function get_title() {
		return esc_html__( 'AAE Post Meta', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-info';
	}

	public function get_keywords() {
		return [ 'post', 'meta', 'info', 'author', 'date', 'comments', 'atomic', 'nested' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'list_tag' => String_Prop_Type::make()->enum( [ 'div', 'ul', 'ol' ] )->default( 'ul' ),
			'layout' => String_Prop_Type::make()->enum( [ 'inline', 'stacked' ] )->default( 'inline' ),
			'gap' => String_Prop_Type::make()->default( '12px' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'layout_settings' )
				->set_label( __( 'Meta Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'list_tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'div', 'label' => __( 'Div', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ul', 'label' => __( 'Unordered List (ul)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ol', 'label' => __( 'Ordered List (ol)', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'inline', 'label' => __( 'Inline', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'stacked', 'label' => __( 'Stacked', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'gap' )
						->set_label( __( 'Gap', 'animation-addons-for-elementor' ) )
						->set_placeholder( '12px' ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display' => String_Prop_Type::generate( 'flex' ),
					'flex-wrap' => String_Prop_Type::generate( 'wrap' ),
					'list-style' => String_Prop_Type::generate( 'none' ),
					'margin' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
						'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
					]),
					'padding' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
						'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
						'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
					]),
				] ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Post_Meta_Item::generate()
				->settings( [
					'meta_type' => String_Prop_Type::generate( 'date' ),
				] )
				->build(),
			AAE_A_Post_Meta_Item::generate()
				->settings( [
					'meta_type' => String_Prop_Type::generate( 'author' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-post-meta-item' ];
	}

	protected function define_default_html_tag() {
		return 'ul';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-meta' => __DIR__ . '/aae-a-post-meta.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
