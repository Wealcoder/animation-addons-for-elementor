<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

require_once __DIR__ . '/class-aae-a-nav-item.php';
require_once __DIR__ . '/class-aae-a-nav-sub.php';
require_once __DIR__ . '/class-aae-a-nav-items-control.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Item;
use WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Sub;
use WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Items_Control;

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
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-nav-items-control.php';

		return [
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
		/* Layout props are kept in nav.scss (class-based selectors) — Elementor
		 * caches base-style class hashes in saved page data, so adding/removing
		 * props here can leave the frontend referencing a stale empty hash and
		 * the styles never reach the rendered HTML. CSS file is always emitted
		 * via get_style_depends() so it's the reliable channel. */
		return [];
	}

	protected function define_default_children() {
		/* Default nav-items ship with only a label (no nav-sub). To show the
		 * dropdown feature out of the box we construct one example item that
		 * has has_dropdown=true and explicit [label, nav-sub] children. The
		 * other 3 items are plain — users add a Nav Dropdown manually when
		 * they want one. (Auto-attaching a nav-sub to every nav-item bloated
		 * the tree to 25 elements/widget and hung the editor on device
		 * switches.) */
		$label = Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'Menu Item' ),
					'children' => [],
				] ),
				'tag' => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		$sub = AAE_A_Nav_Sub::generate()->build();

		$item_with_dropdown = AAE_A_Nav_Item::generate()
			->editor_settings( [ 'title' => 'Menu Item 3' ] )
			->settings( [
				'has_dropdown' => Boolean_Prop_Type::generate( true ),
			] )
			->children( [ $label, $sub ] )
			->build();

		return [
			AAE_A_Nav_Item::generate()->editor_settings( [ 'title' => 'Menu Item 1' ] )->build(),
			AAE_A_Nav_Item::generate()->editor_settings( [ 'title' => 'Menu Item 2' ] )->build(),
			$item_with_dropdown,
			AAE_A_Nav_Item::generate()->editor_settings( [ 'title' => 'Menu Item 4' ] )->build(),
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
