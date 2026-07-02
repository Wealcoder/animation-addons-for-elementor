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

class AAE_A_Slider_Current extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Shows the current active slide number.';
	
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'permanently_locked', true );
	}

	public static function generate() {
		return parent::generate()->is_locked( true );
	}

	// Atomic_Widget_Base reads show_in_panel(), not should_show_in_panel().
	public function show_in_panel() {
		return false;
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider-current';
	}

	public function get_title() {
		return esc_html__( 'Slider Current', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_keywords() {
		return [ 'slider', 'current', 'number', 'atomic' ];
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
			'font-weight' => String_Prop_Type::generate( '600' ),
			'line-height' => String_Prop_Type::generate( '1' ),
			'color'       => String_Prop_Type::generate( '#ffffff' ),
		];

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-current' => __DIR__ . '/aae-a-slider-current.html.twig',
		];
	}
}
