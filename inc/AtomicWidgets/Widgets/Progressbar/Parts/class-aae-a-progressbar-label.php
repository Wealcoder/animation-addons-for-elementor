<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Progressbar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Progress Bar — Label. The "0%" counter text progressbar.js animates on
 * scroll-into-view. Its own widget type exists ONLY so it can carry its own
 * fixed typography via define_base_styles() — see
 * class-aae-a-progressbar-track.php for why this can't be a reused
 * e-paragraph.
 */
class AAE_A_Progressbar_Label extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal percentage label used by the AAE Progress Bar.';

	public static function get_element_type(): string {
		return 'e-aae-a-progressbar-label';
	}

	public function get_title() {
		return esc_html__( 'Progress Bar Label', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'progressbar', 'label', 'percentage' ];
	}

	public function get_icon() {
		return 'eicon-t-letter-bold';
	}

	public function show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'text' => Html_V3_Prop_Type::make()
				->default( [
					'content'  => String_Prop_Type::generate( '0%' ),
					'children' => [],
				] )
				->description( 'The percentage label text.' ),
			'tag' => String_Prop_Type::make()->default( 'span' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Inline_Editing_Control::bind_to( 'text' )
						->set_label( __( 'Label', 'animation-addons-for-elementor' ) ),
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
						->add_prop( 'display',    String_Prop_Type::generate( 'block' ) )
						->add_prop( 'font-size',   Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight', String_Prop_Type::generate( '500' ) )
						->add_prop( 'color',       Color_Prop_Type::generate( '#1a1a1a' ) )
						->add_prop( 'margin', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-progressbar-label' => __DIR__ . '/aae-a-progressbar-label.html.twig',
		];
	}
}
