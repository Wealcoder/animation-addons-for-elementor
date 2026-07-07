<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ButtonPro;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AAE Pro Atomic Button Widget.
 *
 * Eight GSAP-enhanced button styles ported from the legacy Button Pro widget.
 * Style 4 (Ripple) uses gsap.quickTo() for buttery-smooth cursor tracking.
 * Styles 5 & 6 (Group Swap) and Style 1 (Border Divide) rely on JS DOM setup
 * with CSS-based transitions; all other styles are pure CSS.
 */
class AAE_A_Button_Pro extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-button-pro';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-button-pro';
	}

	public function get_title(): string {
		return esc_html__( 'AAE Button Pro - New', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'wcf-icon-Button';
	}

	public function get_keywords(): array {
		return [ 'button', 'cta', 'pro button', 'gsap', 'atomic', 'animation', 'advanced button' ];
	}

	/* =====================================================================
	 *  Schema
	 * =================================================================== */

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Link
			'btn_pro_url'      => String_Prop_Type::make()->default( '#' ),
			'btn_pro_target'   => String_Prop_Type::make()->default( '_self' ),
			'btn_pro_nofollow' => Boolean_Prop_Type::make()->default( false ),

			// Style selector
			'btn_pro_style' => String_Prop_Type::make()->default( '4' ),
			'btn_pro_alignment' => String_Prop_Type::make()->default( 'left' ),

			// Style 10 only — equal width / height for circle shape (px)
			'btn_pro_circle_size' => Number_Prop_Type::make()->default( 140 ),

			// Hover colour overrides surfaced as CSS custom properties
			'btn_pro_hover_color'        => String_Prop_Type::make()->default( '' ),
			'btn_pro_hover_bg_color'     => String_Prop_Type::make()->default( '' ),
			'btn_pro_hover_border_color' => String_Prop_Type::make()->default( '' ),
		];
	}

	/* =====================================================================
	 *  Controls
	 * =================================================================== */

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Button', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'btn_pro_style' )
						->set_label( __( 'Style', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '4',  'label' => __( '4  — Ripple (GSAP)',   'animation-addons-for-elementor' ) ],
							[ 'value' => '5',  'label' => __( '5  — Group Swap L',    'animation-addons-for-elementor' ) ],
							[ 'value' => '6',  'label' => __( '6  — Group Swap R',    'animation-addons-for-elementor' ) ],
							[ 'value' => '9',  'label' => __( '9  — Oval',            'animation-addons-for-elementor' ) ],
							[ 'value' => '10', 'label' => __( '10 — Circle',          'animation-addons-for-elementor' ) ],
							[ 'value' => '11', 'label' => __( '11 — Ellipse',         'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'btn_pro_url' )
						->set_label( __( 'URL', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'btn_pro_target' )
						->set_label( __( 'Open In', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '_self',  'label' => __( 'Same Window', 'animation-addons-for-elementor' ) ],
							[ 'value' => '_blank', 'label' => __( 'New Window',  'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'btn_pro_nofollow' )
						->set_label( __( 'Add Nofollow', 'animation-addons-for-elementor' ) ),

					Number_Control::bind_to( 'btn_pro_circle_size' )
						->set_label( __( 'Circle Size (px)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 40, 'max' => 400, 'step' => 1 ] ),

					Select_Control::bind_to( 'btn_pro_alignment' )
						->set_label( __( 'Alignment', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'left',   'label' => __( 'Left',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'center', 'label' => __( 'Center', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'right',  'label' => __( 'Right',  'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Hover Colors', 'animation-addons-for-elementor' ) )
				->set_id( 'btn_hv_colors_tab' )
				->set_items( [
					Text_Control::bind_to( 'btn_pro_hover_color' )
						->set_label( __( 'Hover Text Color', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'btn_pro_hover_bg_color' )
						->set_label( __( 'Hover Background', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'btn_pro_hover_border_color' )
						->set_label( __( 'Hover Border Color', 'animation-addons-for-elementor' ) ),
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

	/* =====================================================================
	 *  Base styles — layout / typography defaults settable from Style panel
	 * =================================================================== */

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					// Layout
					'display'         => String_Prop_Type::generate( 'inline-flex' ),
					'align-items'     => String_Prop_Type::generate( 'center' ),
					'justify-content' => String_Prop_Type::generate( 'center' ),
					'column-gap'      => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
					'width'           => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),

					'padding' => Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 17, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 17, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => 35, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => 35, 'unit' => 'px' ] ),
					] ),

					// Typography
					'font-size'       => String_Prop_Type::generate( '16px' ),
					'font-weight'     => String_Prop_Type::generate( '500' ),
					'line-height'     => String_Prop_Type::generate( '1' ),
					'text-decoration' => String_Prop_Type::generate( 'none' ),

					// Interaction
					'cursor'     => String_Prop_Type::generate( 'pointer' ),
					'transition' => String_Prop_Type::generate( 'all 0.3s' ),
					'outline'    => String_Prop_Type::generate( 'none' ),
					'color'      => Color_Prop_Type::generate( '#000' ),
				] ) ),
		];
	}

	/* =====================================================================
	 *  Default children — Paragraph (text) + SVG (icon)
	 * =================================================================== */

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->settings( [
					'paragraph' => \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::generate( [
						'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'Click here' ),
						'children' => [],
					] ),
					'tag' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( 'span' ),
				] )
				->build(),
			Atomic_Svg::generate()->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading', 'e-image' ];
	}

	protected function define_default_html_tag() {
		return 'a';
	}

	/* =====================================================================
	 *  Template
	 * =================================================================== */

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-button-pro' => __DIR__ . '/aae-a-button-pro.html.twig',
		];
	}

	/* =====================================================================
	 *  Assets
	 * =================================================================== */

	public function get_script_depends(): array {
		return [ 'aae-a-button-pro-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-button-pro-css' ];
	}
}
