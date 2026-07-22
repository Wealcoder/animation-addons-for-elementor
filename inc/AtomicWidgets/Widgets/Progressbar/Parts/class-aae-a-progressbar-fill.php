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
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Progress Bar — Fill. The colored bar inside the Track; its rendered
 * width is driven at runtime by progressbar.js reading the parent's
 * pb_percentage (an inline style, so it always wins over this base style's
 * resting width). Its own widget type exists ONLY so it can carry its own
 * fixed color/radius via define_base_styles() — see
 * class-aae-a-progressbar-track.php for why this can't be a reused
 * Div_Block.
 */
class AAE_A_Progressbar_Fill extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal progress-bar fill used by the AAE Progress Bar Track.';

	public static function get_element_type(): string {
		return 'e-aae-a-progressbar-fill';
	}

	public function get_title() {
		return esc_html__( 'Progress Bar Fill', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'progressbar', 'fill' ];
	}

	public function get_icon() {
		return 'eicon-skill-bar';
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
						->add_prop( 'display',       String_Prop_Type::generate( 'block' ) )
						->add_prop( 'width',         Size_Prop_Type::generate( [ 'size' => 0, 'unit' => '%' ] ) )
						->add_prop( 'height',        Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
						->add_prop( 'background',    Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#1a1a1a' ) ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 999, 'unit' => 'px' ] ) )
						->add_prop( 'transition', Transition_Prop_Type::generate( [
							Selection_Size_Prop_Type::generate( [
								'selection' => Key_Value_Prop_Type::generate( [
									'key'   => String_Prop_Type::generate( 'Width' ),
									'value' => String_Prop_Type::generate( 'width' ),
								] ),
								'size' => Size_Prop_Type::generate( [
									'size' => 400,
									'unit' => 'ms',
								] ),
							] ),
						] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-progressbar-fill' => __DIR__ . '/aae-a-progressbar-fill.html.twig',
		];
	}
}
