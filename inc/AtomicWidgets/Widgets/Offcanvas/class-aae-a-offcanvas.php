<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-offcanvas-panel.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Panel;

/**
 * AAE Offcanvas — animated offcanvas drawer (vanilla JS, no GSAP).
 *
 * Structure:
 *   AAE_A_Offcanvas (this class — the outer widget users drop in)
 *     └─ AAE_A_Offcanvas_Panel  (locked — the sliding panel container)
 *
 * The trigger button and overlay are static Twig markup in the parent.
 * The panel child is where users drop their content widgets.
 * JS adds/removes `is-open` on the parent to drive CSS transitions.
 */
class AAE_A_Offcanvas extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-offcanvas';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-offcanvas';
	}

	public function get_title() {
		return esc_html__( 'AAE Offcanvas', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'offcanvas', 'drawer', 'sidebar', 'panel', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-sidebar';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'position'     => String_Prop_Type::make()->enum( [ 'left', 'right', 'top', 'bottom' ] )->default( 'left' ),
			'trigger_icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Offcanvas/assets/icons/hamburger.svg' ),
			'editor_open'  => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Offcanvas', 'animation-addons-for-elementor' ) )
				->set_id( 'offcanvas' )
				->set_items( [
					Select_Control::bind_to( 'position' )
						->set_label( __( 'Position', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'left',   'label' => __( 'Left',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'right',  'label' => __( 'Right',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'top',    'label' => __( 'Top',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'bottom', 'label' => __( 'Bottom', 'animation-addons-for-elementor' ) ],
						] ),
					Svg_Control::bind_to( 'trigger_icon' )
						->set_label( __( 'Trigger Icon', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'editor_open' )
						->set_label( __( 'Preview Open (Editor)', 'animation-addons-for-elementor' ) ),
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
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_props( [
							'display'  => String_Prop_Type::generate( 'inline-block' ),
							'position' => String_Prop_Type::generate( 'relative' ),
							'padding'  => Dimensions_Prop_Type::generate( [
								'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							] ),
						] )
				),
		];
	}

	protected function define_default_children(): array {
		return [
			AAE_A_Offcanvas_Panel::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Panel' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-aae-a-offcanvas-panel' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-offcanvas' => __DIR__ . '/aae-a-offcanvas.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-offcanvas-js' ];
	}

	public function get_style_depends(): array {
		return [];
	}
}
