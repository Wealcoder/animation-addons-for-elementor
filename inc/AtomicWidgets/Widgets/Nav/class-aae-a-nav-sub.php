<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Nav_Sub extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-nav-sub';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-nav-sub';
	}

	public function get_title() {
		return esc_html__( 'Nav Dropdown', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
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
		return [];
	}

	protected function define_default_children() {
		// Use Atomic_Paragraph (leaf node) — NOT AAE_A_Nav_Item.
		// Nav_Item → Nav_Sub → Nav_Item → … would cause infinite recursion.
		$make_item = fn( $text ) => Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $text ),
					'children' => [],
				] ),
				'tag' => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [
			$make_item( 'Dropdown Item' ),
			$make_item( 'Dropdown Item' ),
			$make_item( 'Dropdown Item' ),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-aae-a-nav-item' ];
	}

	protected function define_default_html_tag() {
		return 'ul';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-nav-sub' => __DIR__ . '/aae-a-nav-sub.html.twig',
		];
	}
}
