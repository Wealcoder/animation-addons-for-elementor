<?php
/**
 * AAE Video Popup — atomic container WIDGET.
 *
 * A spinning circular trigger that opens a popup playing a video — same 5
 * sources (YouTube/hosted/Vimeo/Dailymotion/VideoPress) and the same custom
 * controls bar as the AAE Video widget, but self-contained: this whole
 * family is its OWN copy of the video engine/parts, deliberately NOT
 * sharing code with `Widgets/Video/` (see Parts/class-aae-a-video-popup-
 * player.php's docblock for why — parent-map/registration independence,
 * not oversight).
 *
 * Structure:
 *   AAE_A_Video_Popup (this class)
 *     ├─ AAE_A_Video_Popup_Trigger  — the rotating circle (locked)
 *     ├─ AAE_A_Video_Popup_Overlay  — backdrop (locked, teleported to <body>)
 *     └─ AAE_A_Video_Popup_Panel    — the popup box (locked, teleported to
 *          ├─ AAE_A_Video_Popup_Close     <body>). OWNS every video source/
 *          ├─ AAE_A_Video_Popup_PlayBtn   playback/poster/controls prop —
 *          └─ AAE_A_Video_Popup_Player    see that class's own docblock.
 *
 * Why teleport (video-popup.js, mirrors Offcanvas's own offcanvas.js): the
 * Overlay/Panel use `position: fixed`, which resolves against the nearest
 * transformed ancestor. An Elementor container frequently carries a
 * `transform` (animations, sliders), so the popup must be moved to a
 * transform-free `.elementor` host on <body> at runtime for `fixed` to mean
 * "the viewport" AND for the Panel's own atomic base styles to keep
 * matching (every atomic rule compiles as `.elementor .e-xxx`).
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

require_once __DIR__ . '/Parts/class-aae-a-video-popup-trigger.php';
require_once __DIR__ . '/Parts/class-aae-a-video-popup-overlay.php';
require_once __DIR__ . '/Parts/class-aae-a-video-popup-panel.php';

use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_Trigger;
use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_Overlay;
use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_Panel;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Video_Popup extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-video-popup';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup';
	}

	public function get_title() {
		return esc_html__( 'AAB Video Popup', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-video-camera';
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'modal', 'youtube', 'vimeo', 'spinner', 'circle', 'atomic' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-general' ];
	}

	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Behavior.
			'close_on_overlay' => Boolean_Prop_Type::make()->default( true ),
			'close_on_esc'     => Boolean_Prop_Type::make()->default( true ),

			// Open/close motion — plain CSS, no GSAP (this widget has no
			// GSAP dependency at all, matching AAE Video's own JS).
			'popup_animation' => String_Prop_Type::make()
				->enum( [ 'fade', 'scale-reveal', 'slide-up', 'none' ] )
				->default( 'scale-reveal' ),
			'anim_duration' => Number_Prop_Type::make()->default( 400 ),

			// Editor-only: reveal the panel in-flow so its content is
			// editable. Canvas clicks on the trigger are unreliable
			// (Elementor's own selection overlay swallows them) — same
			// reasoning as Offcanvas's identical switch. No frontend effect.
			'editor_open' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'popup' )
				->set_label( __( 'Popup', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'editor_open' )
						->set_label( __( 'Open Popup (Editor)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'popup_animation' )
						->set_label( __( 'Popup Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'fade',         'label' => __( 'Fade', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'scale-reveal',  'label' => __( 'Scale Reveal', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-up',      'label' => __( 'Slide Up', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',          'label' => __( 'None', 'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'anim_duration' )
						->set_label( __( 'Animation Duration (ms)', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'close_on_overlay' )
						->set_label( __( 'Close on Overlay Click', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'close_on_esc' )
						->set_label( __( 'Close on Esc Key', 'animation-addons-for-elementor' ) ),
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
	 * Root is a thin inline wrapper — it only needs to sit inline with its
	 * neighbours (the visible trigger). Undoes Elementor's own `.e-con`
	 * (full-width flex block), same reasoning as AAE_A_Offcanvas's identical
	 * base style.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',    String_Prop_Type::generate( 'inline-block' ) )
						->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Video_Popup_Trigger::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Trigger' ] )
				->build(),

			AAE_A_Video_Popup_Overlay::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Overlay' ] )
				->build(),

			AAE_A_Video_Popup_Panel::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Panel' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-video-popup-trigger', 'e-aae-a-video-popup-overlay', 'e-aae-a-video-popup-panel' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup' => __DIR__ . '/aae-a-video-popup.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-video-popup-js' ];
	}

	/**
	 * video-popup.scss still carries what can't become a Style-tab prop:
	 * the rotator's `@keyframes`, the curved-text `<textPath>` styling, any
	 * rule that reveals one element off a DIFFERENT element's hover/state
	 * class (no atomic Style_Variant can target that — see
	 * class-aae-a-video-popup-player.php's docblock for the full list of
	 * what already moved OUT of it and into a `define_base_styles()`).
	 */
	public function get_style_depends(): array {
		return [ 'aae-a-video-popup-css' ];
	}
}
