<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

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
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Slider_Divider extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Slash separator between current and total slide numbers.';
	
	
	// Atomic_Widget_Base extends V3 Widget_Base, whose panel visibility is read
	// from show_in_panel() — NOT should_show_in_panel() (that's the
	// Atomic_Element_Base name). Use the correct method so this sub-widget stays
	// hidden from the widget panel (it's only inserted inside the slider).
	public function show_in_panel() {
		return false;
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-divider';
	}

	public function get_title() {
		return esc_html__( 'Slider Divider', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-divider';
	}

	public function get_keywords() {
		return [ 'slider', 'divider', 'separator', 'slash', 'atomic' ];
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
		$styles = [
			'display'     => String_Prop_Type::generate( 'inline-block' ),
			'font-size'   => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
			'font-weight' => String_Prop_Type::generate( '400' ),
			'line-height' => String_Prop_Type::generate( '1' ),
			'color'       => String_Prop_Type::generate( '#ffffff' ),
			'opacity'     => String_Prop_Type::generate( '0.5' ),
		];

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-divider' => __DIR__ . '/aae-a-slider-divider.html.twig',
		];
	}
}
