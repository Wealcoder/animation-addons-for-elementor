<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

class AAE_A_Offcanvas_Panel extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-offcanvas-panel';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-offcanvas-panel';
	}

	public function get_title() {
		return esc_html__( 'Offcanvas Panel', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-inner-section';
	}

	public function get_keywords() {
		return [ 'offcanvas', 'panel', 'drawer', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'close_icon'  => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Offcanvas/assets/icons/close.svg' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'panel' )
				->set_label( __( 'Panel', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'close_icon' )
						->set_label( __( 'Close Icon', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-svg', 'e-button', 'e-image', 'e-divider' ];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_props( [
							'width'          => Size_Prop_Type::generate( [ 'size' => 320, 'unit' => 'px' ] ),
							'max-width'      => Size_Prop_Type::generate( [ 'size' => 90, 'unit' => 'vw' ] ),
							'height'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => 'vh' ] ),
							'background'     => Background_Prop_Type::generate( [
								'color' => Color_Prop_Type::generate( '#ffffff' ),
							] ),
							'padding'        => Dimensions_Prop_Type::generate( [
								'block-start'  => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
								'block-end'    => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
								'inline-start' => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
								'inline-end'   => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
							] ),
						] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-offcanvas-panel' => __DIR__ . '/aae-a-offcanvas-panel.html.twig',
		];
	}
}
