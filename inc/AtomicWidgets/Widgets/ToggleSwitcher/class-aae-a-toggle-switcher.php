<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Toggle_Switcher extends Atomic_Widget_Base {

	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher';
	}

	public function get_title() {
		return esc_html__( 'AAE Toggle Switcher', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-toggle';
	}

	public function get_keywords() {
		return [ 'toggle', 'switcher', 'switch', 'tabs', 'pricing', 'atomic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'switcher_style' => String_Prop_Type::make()->default( '1' ),

			'item1_title'   => String_Prop_Type::make()->default( 'Monthly' ),
			'item1_content' => String_Prop_Type::make()->default( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.' ),

			'item2_title'   => String_Prop_Type::make()->default( 'Yearly' ),
			'item2_content' => String_Prop_Type::make()->default( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Toggle Switcher', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'switcher_style' )
						->set_label( __( 'Style', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '1', 'label' => __( 'One', 'animation-addons-for-elementor' ) ],
							[ 'value' => '2', 'label' => __( 'Two', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'item1_title' )
						->set_label( __( 'Item 1 Title', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'item2_title' )
						->set_label( __( 'Item 2 Title', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Item 1 Content', 'animation-addons-for-elementor' ) )
				->set_id( 'item1' )
				->set_items( [
					Text_Control::bind_to( 'item1_content' )
						->set_label( __( 'Content', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Item 2 Content', 'animation-addons-for-elementor' ) )
				->set_id( 'item2' )
				->set_items( [
					Text_Control::bind_to( 'item2_content' )
						->set_label( __( 'Content', 'animation-addons-for-elementor' ) ),
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
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', 'flex' )
						->add_prop( 'flex-direction', 'column' )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher' => __DIR__ . '/aae-a-toggle-switcher.html.twig',
		];
	}

	public function render_markdown(): string {
		$settings = $this->get_atomic_settings();
		$title1   = $settings['item1_title'] ?? 'Monthly';
		$title2   = $settings['item2_title'] ?? 'Yearly';

		return esc_html( $title1 ) . ' / ' . esc_html( $title2 );
	}

	public function get_script_depends(): array {
		return [ 'aae-a-toggle-switcher-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-toggle-switcher-css' ];
	}
}
