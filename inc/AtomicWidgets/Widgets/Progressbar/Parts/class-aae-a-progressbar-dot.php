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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Progress Bar — Dot. One step of the Dot look; progressbar.js fills the
 * first N of them (`.aae-progressbar-dot`, painted with the dot's own border
 * colour) to represent the percentage.
 *
 * Its own widget type exists for the same two reasons the Fill's does: it can
 * carry its own resting look via define_base_styles() without styling every
 * div-block on the site, AND — the reason it was added — its twig can render
 * the `aae-progressbar-dot` JS hook itself. A hook class carried in the
 * `classes` prop is reported by Elementor's panel as "Some classes are
 * missing" (nothing in the style repository resolves it) and that alert's
 * dismiss button unapplies it, silently killing the animation.
 */
class AAE_A_Progressbar_Dot extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal progress-bar dot used by the AAE Progress Bar.';

	public static function get_element_type(): string {
		return 'e-aae-a-progressbar-dot';
	}

	public function get_title() {
		return esc_html__( 'Progress Bar Dot', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'progressbar', 'dot', 'step' ];
	}

	public function get_icon() {
		return 'eicon-dot-circle-o';
	}

	public function show_in_panel() {
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

	/**
	 * The resting (unfilled) dot. progressbar.js paints an active dot by
	 * setting backgroundColor from the computed border colour and opacity to 1
	 * inline, so both always win over these.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',       String_Prop_Type::generate( 'block' ) )
						->add_prop( 'width',         Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ) )
						->add_prop( 'height',        Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ) )
						->add_prop( 'border-width',  Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ) )
						->add_prop( 'border-style',  String_Prop_Type::generate( 'solid' ) )
						->add_prop( 'border-color',  Color_Prop_Type::generate( '#1a1a1a' ) )
						->add_prop( 'opacity',       Size_Prop_Type::generate( [ 'size' => 30, 'unit' => '%' ] ) )
						->add_prop( 'transition', Transition_Prop_Type::generate( [
							Selection_Size_Prop_Type::generate( [
								'selection' => Key_Value_Prop_Type::generate( [
									'key'   => String_Prop_Type::generate( 'All' ),
									'value' => String_Prop_Type::generate( 'all' ),
								] ),
								'size' => Size_Prop_Type::generate( [
									'size' => 300,
									'unit' => 'ms',
								] ),
							] ),
						] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-progressbar-dot' => __DIR__ . '/aae-a-progressbar-dot.html.twig',
		];
	}
}
