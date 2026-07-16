<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBoxMain;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-flip-box-main-face.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Flip_Box_Main extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-flip-box-main';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-flip-box-main';
	}

	public function get_title(): string {
		return esc_html__( 'AAE Flip Box Main', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-flip-box';
	}

	public function get_keywords(): array {
		return [ 'flip', 'box', 'card', 'hover', 'atomic', 'animation' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'flip_type'   => String_Prop_Type::make()
				->enum( [ 'animate-left', 'animate-right', 'animate-up', 'animate-down', 'animate-zoom-in', 'animate-zoom-out', 'animate-fade-in' ] )
				->default( 'animate-left' ),

			'flip_3d'        => Boolean_Prop_Type::make()->default( false ),

			'flip_height'    => Number_Prop_Type::make()->default( 300 ),

			'show_back_face' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Flip Box', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'flip_type' )
						->set_label( __( 'Flip Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'animate-left',     'label' => __( 'Flip Left',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-right',    'label' => __( 'Flip Right',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-up',       'label' => __( 'Flip Top',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-down',     'label' => __( 'Flip Bottom', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-zoom-in',  'label' => __( 'Zoom In',     'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-zoom-out', 'label' => __( 'Zoom Out',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'animate-fade-in',  'label' => __( 'Fade In',     'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'flip_3d' )
						->set_label( __( '3D Depth', 'animation-addons-for-elementor' ) ),

					Number_Control::bind_to( 'flip_height' )
						->set_label( __( 'Height (px)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 100, 'max' => 1000, 'step' => 1 ] ),

					Switch_Control::bind_to( 'show_back_face' )
						->set_label( __( 'Show Back Face', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
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
				->add_variant( Style_Variant::make()->add_props( [
					'display'  => String_Prop_Type::generate( 'block' ),
					'width'    => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'position' => String_Prop_Type::generate( 'relative' ),
					'overflow' => String_Prop_Type::generate( 'hidden' ),
				] ) ),
		];
	}

	protected function define_default_children(): array {
		return [
			AAE_A_Flip_Box_Main_Face::generate()
				->settings( [
					'face_side' => String_Prop_Type::generate( 'front' ),
				] )
				->editor_settings( [ 'title' => 'Front Face' ] )
				->children( AAE_A_Flip_Box_Main_Face::build_default_children( 'Front Title', 'This is front side content.' ) )
				->build(),

			AAE_A_Flip_Box_Main_Face::generate()
				->settings( [
					'face_side' => String_Prop_Type::generate( 'back' ),
				] )
				->editor_settings( [ 'title' => 'Back Face' ] )
				->children( AAE_A_Flip_Box_Main_Face::build_default_children( 'Back Title', 'This is back side content.' ) )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-aae-a-flip-box-main-face' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-flip-box-main' => __DIR__ . '/aae-a-flip-box-main.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-flip-box-main-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-flip-box-main-css' ];
	}
}