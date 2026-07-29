<?php
/**
 * AAE Image Hotspot — atomic element (container).
 *
 * A background image with clickable/hoverable markers on top. The background
 * image is a real, native `e-image` (Atomic_Image) child, not a custom image
 * prop — same choice ImageCompare made for its Before/After images — so it
 * gets full native Style-tab control for free. Markers are real
 * `e-aae-a-hotspot-point` children, added/reordered/removed through the
 * "Hotspots" element-control (AAE_A_Hotspots_Control), same convention as
 * NestedSlider's slides / Accordion's items.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

require_once __DIR__ . '/class-aae-a-hotspot-point.php';
require_once __DIR__ . '/class-aae-a-hotspots-control.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Point;
use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspots_Control;

class AAE_A_Image_Hotspot extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-image-hotspot';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-image-hotspot';
	}

	public function get_title() {
		return esc_html__( 'AAE Image Hotspot', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-image-hotspot';
	}

	public function get_keywords() {
		return [ 'hotspot', 'image', 'tooltip', 'lightbox', 'tour', 'atomic' ];
	}

	protected static function define_props_schema(): array {
		$show_if_tour = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'tour_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'trigger_type' => String_Prop_Type::make()
				->enum( [ 'hover', 'click', 'none' ] )
				->default( 'hover' ),

			'marker_anim' => String_Prop_Type::make()
				->enum( [ 'none', 'beat', 'pulse', 'ripple', 'ring', 'glow', 'bounce' ] )
				->default( 'pulse' ),
			'anim_speed' => Number_Prop_Type::make()->default( 3 ),

			'tour_enabled'              => Boolean_Prop_Type::make()->default( false ),
			'tour_delay'                => Number_Prop_Type::make()->default( 3000 )->set_dependencies( $show_if_tour ),
			'tour_pause_on_interaction' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $show_if_tour ),
			'tour_loop'                 => Boolean_Prop_Type::make()->default( true )->set_dependencies( $show_if_tour ),
			'tour_open_tooltip'         => Boolean_Prop_Type::make()->default( true )->set_dependencies( $show_if_tour ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'hotspots' )
				->set_label( __( 'Hotspots', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Hotspots_Control::make()
						->set_label( __( 'Hotspots', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'behavior' )
				->set_label( __( 'Behavior', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'trigger_type' )
						->set_label( __( 'Trigger', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'hover', 'label' => __( 'Hover', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'click', 'label' => __( 'Click', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',  'label' => __( 'None', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_id( 'marker_animation' )
				->set_label( __( 'Marker Animation', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'marker_anim' )
						->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'none',   'label' => __( 'None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'beat',   'label' => __( 'Beat', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'pulse',  'label' => __( 'Pulse', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ripple', 'label' => __( 'Ripple', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ring',   'label' => __( 'Ring', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'glow',   'label' => __( 'Glow', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'bounce', 'label' => __( 'Bounce', 'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'anim_speed' )
						->set_label( __( 'Speed (s)', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'tour' )
				->set_label( __( 'Guided Tour', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'tour_enabled' )
						->set_label( __( 'Enable Tour', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'tour_delay' )
						->set_label( __( 'Delay per Hotspot (ms)', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'tour_pause_on_interaction' )
						->set_label( __( 'Pause on Hover/Interaction', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'tour_loop' )
						->set_label( __( 'Loop', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'tour_open_tooltip' )
						->set_label( __( 'Auto-open Tooltips', 'animation-addons-for-elementor' ) ),
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
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
						->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'width', String_Prop_Type::generate( '100%' ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			Atomic_Image::generate()
				->editor_settings( [ 'title' => 'Background Image' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-hotspot-bg' ] ),
					'image'   => Image_Prop_Type::generate( [
						'src'  => Image_Src_Prop_Type::generate( [
							'id'  => null,
							'url' => Url_Prop_Type::generate( \Elementor\Utils::get_placeholder_image_src() ),
						] ),
						'size' => String_Prop_Type::generate( 'large' ),
					] ),
				] )
				->build(),

			AAE_A_Hotspot_Point::generate()
				->editor_settings( [ 'title' => 'Hotspot 1' ] )
				->settings( [
					'pos_left' => Number_Prop_Type::generate( 30 ),
					'pos_top'  => Number_Prop_Type::generate( 40 ),
				] )
				->build(),

			AAE_A_Hotspot_Point::generate()
				->editor_settings( [ 'title' => 'Hotspot 2' ] )
				->settings( [
					'pos_left' => Number_Prop_Type::generate( 65 ),
					'pos_top'  => Number_Prop_Type::generate( 60 ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-image', 'e-aae-a-hotspot-point' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-image-hotspot' => __DIR__ . '/aae-a-image-hotspot.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-image-hotspot-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-image-hotspot-css' ];
	}
}
