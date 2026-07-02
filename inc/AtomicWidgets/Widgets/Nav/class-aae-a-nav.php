<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Flexbox\Flexbox;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

require_once __DIR__ . '/class-aae-a-nav-item.php';
require_once __DIR__ . '/class-aae-a-nav-sub-item.php';
require_once __DIR__ . '/class-aae-a-nav-items-control.php';
require_once __DIR__ . '/class-aae-a-mobile-nav.php';
require_once __DIR__ . '/class-aae-a-mobile-nav-lifecycle-control.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Nav extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-nav';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-nav';
	}

	public function get_title() {
		return esc_html__( 'AAE Nav', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_keywords() {
		return [ 'nav', 'menu', 'navbar', 'navigation', 'atomic', 'aae' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'mobile_enabled' => Boolean_Prop_Type::make()->default( false ),
			'mobile_breakpoint' => String_Prop_Type::make()->default( '767' ),
			'mobile_position' => String_Prop_Type::make()->default( 'right' ),
			'mobile_close_on_link' => Boolean_Prop_Type::make()->default( true ),
			'mobile_lock_scroll' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Mobile Menu', 'animation-addons-for-elementor' ) )
				->set_id( 'mobile_menu' )
				->set_items( [
					Switch_Control::bind_to( 'mobile_enabled' )
						->set_label( __( 'Enable Mobile Menu', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'mobile_breakpoint' )
						->set_label( __( 'Breakpoint', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '767', 'label' => __( 'Mobile (767px)', 'animation-addons-for-elementor' ) ],
							[ 'value' => '1024', 'label' => __( 'Tablet (1024px)', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'mobile_position' )
						->set_label( __( 'Drawer Position', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'right', 'label' => __( 'Right', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'left', 'label' => __( 'Left', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'mobile_close_on_link' )
						->set_label( __( 'Close on Link Click', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'mobile_lock_scroll' )
						->set_label( __( 'Lock Body Scroll', 'animation-addons-for-elementor' ) ),
					AAE_A_Mobile_Nav_Lifecycle_Control::make()
						->set_label( '' )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),
			Section::make()
				->set_label( __( 'Menu Items', 'animation-addons-for-elementor' ) )
				->set_id( 'menu_items' )
				->set_items( [
					AAE_A_Nav_Items_Control::make()
						->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
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
		return [];
	}

	protected function define_default_children() {
		$make_item = function ( $title, $has_dropdown = false, array $children = [] ) {
			$builder = AAE_A_Nav_Item::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => $title ] )
				->settings( [
					'text' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
					'has_dropdown' => Boolean_Prop_Type::generate( $has_dropdown ),
				] );
			if ( $children ) {
				$builder->children( $children );
			}
			return $builder->build();
		};

		$make_sub_item = fn( $text ) => AAE_A_Nav_Sub_Item::generate()
			->editor_settings( [ 'title' => $text ] )
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $text ),
					'children' => [],
				] ),
			] )
			->build();

		return [
			$make_item( 'Menu Item 1' ),
			$make_item( 'Menu Item 2' ),
			$make_item( 'Menu Item 3' ),
			$make_item( 'Menu Item 4' ),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-nav-item' ];
	}

	protected function define_default_html_tag() {
		return 'ul';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-nav' => __DIR__ . '/aae-a-nav.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-nav-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-nav-css' ];
	}
}
