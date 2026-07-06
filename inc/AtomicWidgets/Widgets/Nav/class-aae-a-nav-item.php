<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-nav-sub-items-control.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Structural note: the item's visible label is a PROP on this element
 * (`text` + optional `link`), not a child paragraph widget. Dropdown
 * sub-items are the item's direct children. This flattens the tree from
 * Nav → item → nav-sub → sub-item (4 levels of Atomic_Element_Base with
 * content — hangs the editor on device switch) down to Nav → item →
 * sub-item (3 levels).
 */
class AAE_A_Nav_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-nav-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-nav-item';
	}

	public function get_title() {
		return esc_html__( 'Nav Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'            => Classes_Prop_Type::make()->default( [] ),
			'attributes'         => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'text'               => Html_V3_Prop_Type::make(),
			'link'               => Link_Prop_Type::make(),
			'has_dropdown'       => Boolean_Prop_Type::make()->default( false ),
			'trigger'            => String_Prop_Type::make()->default( 'click' ),
			'dropdown_animation' => String_Prop_Type::make()->default( 'gsap' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Switch_Control::bind_to( 'has_dropdown' )
						->set_label( __( 'Enable Dropdown', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'trigger' )
						->set_label( __( 'Trigger', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'click', 'label' => __( 'Click', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'hover', 'label' => __( 'Hover', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'dropdown_animation' )
						->set_label( __( 'Dropdown Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'gsap',         'label' => __( 'Default (GSAP)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'grow-down',    'label' => __( 'Grow Down', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rotate-3d',    'label' => __( 'Rotate 3D', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'grow-out',     'label' => __( 'Grow Out', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-items',  'label' => __( 'Slide Items', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rotate-items', 'label' => __( 'Rotate Items', 'animation-addons-for-elementor' ) ],
						] ),
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
			Section::make()
				->set_label( __( 'Sub-menu Items', 'animation-addons-for-elementor' ) )
				->set_id( 'sub_menu_items' )
				->set_items( [
					AAE_A_Nav_Sub_Items_Control::make()
						->set_label( __( 'Sub-items', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [];
	}

	protected function define_default_children() {
		return [];
	}

	protected function define_allowed_child_types() {
		/* 'e-flexbox' is the mega-menu-capable dropdown container: a core
		 * Elementor Flexbox at level 3 (Nav → item → flexbox → widgets) which
		 * the user styles via the Style tab. 'e-aae-a-nav-sub-item' is the
		 * legacy leaf, kept for back-compat. 'widget' allows arbitrary content.
		 * 'e-aae-a-nav-item' enables MULTI-LEVEL menus: a nested item is added
		 * inside this item's dropdown flexbox (freeze-safe interleave — the core
		 * flexbox breaks the AAE-element chain). See AAE_A_Nav_Sub_Items_Control. */
		return [ 'widget', 'e-aae-a-nav-sub-item', 'e-flexbox', 'e-aae-a-nav-item' ];
	}

	protected function define_default_html_tag() {
		/* <div>, not <li>: nav-items can nest inside a dropdown flexbox (<div>)
		 * for multi-level menus, and a nested <li> would auto-close its ancestor
		 * <li> in the browser parser, mangling the tree. See aae-a-nav-item.html.twig. */
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-nav-item' => __DIR__ . '/aae-a-nav-item.html.twig',
		];
	}
}
