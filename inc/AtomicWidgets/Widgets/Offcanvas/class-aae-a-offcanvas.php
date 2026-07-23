<?php
/**
 * AAE Offcanvas — atomic NESTED element (parent).
 *
 * A design-less offcanvas / drawer. The widget itself ships almost NO look:
 * it only provides the machinery (a trigger button, a scrim overlay, and a
 * sliding Panel drop-zone) and the minimal STRUCTURAL rules that a base style
 * cannot express. Everything visual is either:
 *   - a base style on the Panel child (width / height / background / padding),
 *     fully overridable from the Style panel, OR
 *   - the user's own atomic widgets dropped INTO the Panel (nav menu, image,
 *     icon list, social icons, search, …). That is the v4 replacement for the
 *     v3 widget's baked-in logo / menu / contact / language / social / search
 *     repeaters — none of those are hard-coded here anymore.
 *
 * Structure:
 *   AAE_A_Offcanvas (this class — the element users drop in)
 *     ├─ .aae-offcanvas-trigger   (plain markup — opens the drawer)
 *     ├─ .aae-offcanvas-overlay   (plain markup — teleported to <body>)
 *     └─ AAE_A_Offcanvas_Panel    (locked atomic child — teleported to <body>)
 *
 * Why teleport: the panel/overlay use `position: fixed`, which resolves against
 * the nearest transformed ancestor. Elementor containers frequently carry a
 * `transform`, so the drawer must be moved to <body> at runtime for `fixed` to
 * mean "the viewport". The teleport lives in offcanvas.js — see the file's
 * comments for how it preserves the Style-panel background across the move.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

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
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-offcanvas-panel.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Panel;

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
		return [ 'offcanvas', 'drawer', 'sidebar', 'panel', 'menu', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-sidebar';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'          => Classes_Prop_Type::make()->default( [] ),
			'attributes'       => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Which edge the drawer slides in from. Drives the fixed-position +
			// slide transform applied by the JS (POS / TRANSFORMS tables).
			'position'         => String_Prop_Type::make()->enum( [ 'left', 'right', 'top', 'bottom' ] )->default( 'left' ),

			// Trigger icon. The default hamburger ships as an SVG asset so the
			// widget is usable the moment it's dropped.
			'trigger_icon'     => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Offcanvas/assets/icons/hamburger.svg' ),

			// Scrim intensity behind the open panel. Structural, not a design
			// choice — no color control exists in atomic content controls, and a
			// dimming scrim rarely needs a bespoke color, so an enum is enough.
			'overlay'          => String_Prop_Type::make()->enum( [ 'dark', 'darker', 'none' ] )->default( 'dark' ),

			// Behaviour toggles (mirrors what the v3 drawer did implicitly).
			'close_on_overlay' => Boolean_Prop_Type::make()->default( true ),
			'close_on_esc'     => Boolean_Prop_Type::make()->default( true ),

			// Open (enter) + close (exit) motion. Self-contained drawer presets
			// applied by offcanvas.js via GSAP (falls back to a CSS slide when
			// GSAP isn't present). `reverse` plays the open animation backwards.
			'open_animation'   => String_Prop_Type::make()->enum( [ 'slide', 'fade', 'fade-slide', 'zoom', 'flip', 'blur', 'none' ] )->default( 'slide' ),
			'close_animation'  => String_Prop_Type::make()->enum( [ 'reverse', 'slide', 'fade', 'fade-slide', 'zoom', 'flip', 'blur', 'none' ] )->default( 'reverse' ),
			'anim_duration'    => Number_Prop_Type::make()->default( 400 ),
			'anim_easing'      => String_Prop_Type::make()->enum( [ 'power2.out', 'power3.out', 'back.out', 'elastic.out', 'expo.out', 'none' ] )->default( 'power2.out' ),

			// Editor-only: reveal the panel in-flow so its content is editable.
			// Canvas clicks on the trigger are unreliable (Elementor's selection
			// overlay swallows them), so this switch is the dependable way to open
			// the panel for editing. Has NO effect on the frontend.
			'editor_open'      => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'offcanvas' )
				->set_label( __( 'Offcanvas', 'animation-addons-for-elementor' ) )
				->set_items( [
					// Editor-only helper, placed first so it's the first thing the
					// user sees on drop: toggle it to open the panel for editing.
					Switch_Control::bind_to( 'editor_open' )
						->set_label( __( 'Open Panel (Editor)', 'animation-addons-for-elementor' ) ),
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
				] ),

			Section::make()
				->set_id( 'behavior' )
				->set_label( __( 'Behavior', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'overlay' )
						->set_label( __( 'Overlay', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'dark',   'label' => __( 'Dark',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'darker', 'label' => __( 'Darker', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',   'label' => __( 'None',   'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'close_on_overlay' )
						->set_label( __( 'Close on Overlay Click', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'close_on_esc' )
						->set_label( __( 'Close on Esc Key', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'animation' )
				->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'open_animation' )
						->set_label( __( 'Open Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'slide',      'label' => __( 'Slide',        'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade',       'label' => __( 'Fade',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade-slide', 'label' => __( 'Fade + Slide', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'zoom',       'label' => __( 'Zoom',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'flip',       'label' => __( 'Flip',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'blur',       'label' => __( 'Blur',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',       'label' => __( 'None',         'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'close_animation' )
						->set_label( __( 'Close Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'reverse',    'label' => __( 'Same as Open (reverse)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide',      'label' => __( 'Slide',        'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade',       'label' => __( 'Fade',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade-slide', 'label' => __( 'Fade + Slide', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'zoom',       'label' => __( 'Zoom',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'flip',       'label' => __( 'Flip',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'blur',       'label' => __( 'Blur',         'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',       'label' => __( 'None',         'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'anim_duration' )
						->set_label( __( 'Duration (ms)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'anim_easing' )
						->set_label( __( 'Easing', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'power2.out',  'label' => __( 'Smooth',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'power3.out',  'label' => __( 'Strong',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'back.out',    'label' => __( 'Back',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'elastic.out', 'label' => __( 'Elastic', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'expo.out',    'label' => __( 'Expo',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',        'label' => __( 'Linear',  'animation-addons-for-elementor' ) ],
						] ),
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
	 * The ROOT is a thin inline wrapper that only has to sit inline with its
	 * neighbours (so the trigger flows like a button). It carries no look — the
	 * drawer's look lives on the Panel child's base style. One declaration only:
	 * `display: inline-block`.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'inline-block' ) )
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

	/**
	 * No external stylesheet by design. The panel's look is a base style; the
	 * only non-base rules are (a) minimal structural CSS for the plain-markup
	 * trigger / overlay, emitted inline in the Twig, and (b) the fixed-position
	 * drawer geometry, applied inline by the JS after it teleports the panel to
	 * <body> (a descendant-scoped stylesheet would stop matching post-teleport).
	 */
	public function get_style_depends(): array {
		return [];
	}
}
