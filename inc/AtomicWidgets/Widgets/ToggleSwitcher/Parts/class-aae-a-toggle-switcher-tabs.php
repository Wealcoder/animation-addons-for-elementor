<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Border_Width_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-toggle-switcher-tab.php';

/**
 * AAE Toggle Switcher — Tabs. The flex row holding the tab buttons, with the
 * shared bottom rule the active tab's own underline sits on top of.
 */
class AAE_A_Toggle_Switcher_Tabs extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher-tabs';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-tabs';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher Tabs', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tabs', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
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
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'gap',     Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ) )
						->add_prop( 'border-width', Border_Width_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ) )
						->add_prop( 'border-style', String_Prop_Type::generate( 'solid' ) )
						->add_prop( 'border-color', Color_Prop_Type::generate( '#e0ded7' ) )
						->add_prop( 'margin', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	/**
	 * Exposed publicly so the parent Switcher's define_default_children() can
	 * seed a fresh Tabs row directly (mirrors
	 * AAE_A_Timeline_Item::build_default_inner_children()). Tab 1 carries the
	 * literal `e--selected` class as the default active tab; toggle-switcher.js
	 * toggles the same class at runtime alongside `.active`.
	 */
	public static function build_default_inner_children(): array {
		return [
			AAE_A_Toggle_Switcher_Tab::generate()
				->editor_settings( [ 'title' => 'Tab — Monthly' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-ts-label-before', 'active', 'e--selected' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Monthly' ),
						'children' => [],
					] ),
				] )
				->build(),

			AAE_A_Toggle_Switcher_Tab::generate()
				->editor_settings( [ 'title' => 'Tab — Yearly' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-ts-label-after' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Yearly' ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-aae-a-toggle-switcher-tab' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-tabs' => __DIR__ . '/aae-a-toggle-switcher-tabs.html.twig',
		];
	}
}
