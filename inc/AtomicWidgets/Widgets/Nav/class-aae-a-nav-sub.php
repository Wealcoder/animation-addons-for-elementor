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
require_once __DIR__ . '/class-aae-a-nav-sub-item.php';

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
		$make_item = fn( $text ) => AAE_A_Nav_Sub_Item::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $text ),
					'children' => [],
				] ),
			] )
			->editor_settings( [ 'title' => $text ] )
			->build();

		return [
			$make_item( 'Dropdown Item' ),
			$make_item( 'Dropdown Item' ),
			$make_item( 'Dropdown Item' ),
		];
	}

	protected function define_allowed_child_types() {
		/* Nav-items ARE allowed here so users can build nested sub-dropdowns
		 * (drag a Nav Item into a Nav Dropdown → toggle Enable Dropdown on it
		 * → a sub-dropdown opens to the right via the existing CSS rule).
		 * The original hang on device switch was caused by the bloated
		 * default-children tree (25 elements/widget), not by this cycle —
		 * default children stay lean (only the example item ships with a
		 * nav-sub), so the responsive re-render storm is gone.
		 * 'widget' is required so Elementor's create command accepts
		 * elType='widget' models (e.g. nav-sub-items added via the panel). */
		return [ 'widget', 'e-aae-a-nav-sub-item', 'e-aae-a-nav-item' ];
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
