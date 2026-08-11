<?php
/**
 * AAE Video Popup — Trigger. The spinning circular badge that opens the
 * popup. A leaf widget (not a container) — the rotating layer (image or
 * flat text, per the `rotator_type` switch) and the static icon on top are
 * both rendered directly by THIS class's own twig, mirroring how
 * AAE_A_Offcanvas_Trigger inlines its icon via an `Svg_Src_Prop_Type` prop
 * instead of nesting a child element for it. video-popup.js binds the OPEN
 * behaviour to the `.aae-video-popup-trigger` hook class hardcoded in the
 * twig (never seeded through the `classes` prop — see CLAUDE.md's "Never
 * put a functional hook class in the classes prop").
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Image_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Video_Popup_Trigger extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'The spinning circular button that opens the Video Popup. Seeded inside the widget; fully styleable via the Style tab.';

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-trigger';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Trigger', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-play';
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'trigger', 'spinner', 'rotate', 'icon', 'atomic' ];
	}

	public function show_in_panel() {
		return false;
	}

	public function hide_on_search() {
		return true;
	}

	protected static function define_props_schema(): array {
		$is_text = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'rotator_type' ],
				'value'    => 'text',
				'effect'   => 'hide',
			] )
			->get();

		$is_image = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'rotator_type' ],
				'value'    => 'image',
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// The rotating layer: an uploaded image, or a flat text block —
			// both spin as a rigid unit (no curved/SVG-textPath text layout).
			'rotator_type' => String_Prop_Type::make()
				->enum( [ 'image', 'text' ] )
				->default( 'image' ),

			'rotator_image' => Image_Prop_Type::make()
				->default_size( 'large' )
				->default_url( \Elementor\Utils::get_placeholder_image_src() )
				->set_dependencies( $is_image ),

			'rotator_text' => String_Prop_Type::make()
				->default( 'WATCH THE VIDEO • WATCH THE VIDEO •' )
				->set_dependencies( $is_text ),

			'rotation_duration' => Number_Prop_Type::make()->default( 8 ),
			'rotation_direction' => String_Prop_Type::make()
				->enum( [ 'cw', 'ccw' ] )
				->default( 'cw' ),

			// Static, non-rotating icon on top. Empty by default → Twig falls
			// back to a built-in play glyph. Inlined from `.html` (never an
			// <img>) so Style-tab colour/hover reach it — same reasoning as
			// AAE_A_Offcanvas_Trigger's identical icon prop.
			'icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/VideoPopup/Parts/assets/icons/play.svg' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Rotator', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'rotator_type' )
						->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'image', 'label' => __( 'Image', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'text',  'label' => __( 'Text', 'animation-addons-for-elementor' ) ],
						] ),
					Image_Control::bind_to( 'rotator_image' )
						->set_label( __( 'Rotator Image', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'rotator_text' )
						->set_label( __( 'Rotator Text', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'rotation_duration' )
						->set_label( __( 'Rotation Duration (s)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'rotation_direction' )
						->set_label( __( 'Direction', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'cw',  'label' => __( 'Clockwise', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ccw', 'label' => __( 'Counter-clockwise', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_id( 'icon' )
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'icon' )
						->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
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

	/**
	 * Neutral circle default — every value below is fully Style-tab
	 * overridable. `overflow: hidden` clips a non-square uploaded rotator
	 * image to the circle; the source PNG in the reference demo is already
	 * round with transparent corners, so this only matters for a differently
	 * shaped upload.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'position'        => String_Prop_Type::generate( 'relative' ),
						'display'         => String_Prop_Type::generate( 'inline-flex' ),
						'align-items'     => String_Prop_Type::generate( 'center' ),
						'justify-content' => String_Prop_Type::generate( 'center' ),
						'width'           => Size_Prop_Type::generate( [ 'size' => 120, 'unit' => 'px' ] ),
						'height'          => Size_Prop_Type::generate( [ 'size' => 120, 'unit' => 'px' ] ),
						'border-radius'   => Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ),
						'overflow'        => String_Prop_Type::generate( 'hidden' ),
						'cursor'          => String_Prop_Type::generate( 'pointer' ),
						'color'           => Color_Prop_Type::generate( '#ffffff' ),
						'background'      => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.4)' ),
						] ),
					] )
				),

			// The static icon layer — sized independently of the circle so it
			// stays a fixed, legible size regardless of the circle's own width/
			// height. Matches AAE_A_Video_PlayBtn's own "icon" style key naming.
			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'width'  => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
					'height' => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
				] ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-trigger' => __DIR__ . '/aae-a-video-popup-trigger.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
