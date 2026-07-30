<?php
/**
 * AAE Form Step — atomic CONTAINER element. One page of a multi-step form.
 *
 * A form with 2+ e-aae-a-form-step children becomes a multi-step form:
 * form.js shows exactly one step at a time (an "active" class, same
 * `-{value}` class-generation trick as the form's own `form-state` prop),
 * wires Next/Previous navigation, and — the hard requirement — validates
 * ONLY that step's fields before allowing Next to advance.
 *
 * Unlike the locked/hidden success/error message containers
 * (class-aae-a-form-message.php), a Step is a normal, visible, unlocked,
 * unrestricted-children container — the user builds each step like any
 * other part of the form (fields, headings, paragraphs, div blocks).
 *
 * Registered in the FREE plugin (not pro) because Elementor's atomic
 * widget registry has no mechanism for a Pro plugin to add a brand-new
 * element type from outside — see class-atomic.php's widgets_registry.
 * "Multi-Step Forms (Pro)" here follows the same shape as AAE_A_Btn_Pro
 * (Widgets/BtnPro/class-aae-a-btn-pro.php): the widget class itself lives
 * in the free plugin and works unconditionally; `is_pro => true` in
 * class-atomic.php's dashboard metadata is what actually marks the
 * feature as a premium tier (upsell badge), not a functional gate here.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Element_Builder;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-form-next.php';
require_once __DIR__ . '/class-aae-a-form-prev.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Next;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Prev;

class AAE_A_Form_Step extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'One page of a multi-step form. Add 2+ of these inside a form to turn it into a multi-step form — form.js shows one at a time and validates each step before advancing.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-form-step';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-step';
	}

	public function get_title() {
		return esc_html__( 'Form Step', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-single-page';
	}

	public function should_show_in_panel() {
		return false;
	}

	public function get_keywords() {
		return [ 'form', 'step', 'multi-step', 'page', 'wizard', 'atomic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Shown in the step-nav UI (progress label / step title) the
			// runtime renders above the active step — NOT the field labels
			// inside it, which stay on their own widgets as usual.
			'step_title' => String_Prop_Type::make()
				->default( __( 'Step', 'animation-addons-for-elementor' ) )
				->description( __( 'Shown in the step progress indicator, not inside the step itself.', 'animation-addons-for-elementor' ) ),

			// Reserved for a future explicit ordering control — today step
			// order is just DOM order (matches how field order already
			// works). Kept as a hidden prop so Schema_Walker/JS can carry an
			// explicit index without a later prop-schema migration.
			'step_index' => Number_Prop_Type::make()->default( 0 ),

			// Per-step (not form-level — moved here 2026-07-20): when ON
			// (default), multi-step.js auto-injects its own Prev/Next DOM
			// buttons into THIS step if it has neither an AAE_A_Form_Prev nor
			// AAE_A_Form_Next widget of its own — so a step never becomes
			// un-advanceable. A builder doing fully custom navigation on one
			// particular step (e.g. a review/confirm step with its own
			// bespoke buttons) can turn this off for just that step, without
			// affecting the rest of the wizard. Read by multi-step.js via
			// data-aae-form-step-nav-auto on the step element itself — see
			// aae-a-form-step.html.twig.
			'step_nav_auto' => Boolean_Prop_Type::make()->default( true ),

			// Pure-CSS transition played when THIS step becomes the active
			// one (both on Next arriving here AND Prev arriving here —
			// direction is inferred at runtime from which neighbor index the
			// previous active step was, not stored). Per-step so a wizard
			// can mix effects (e.g. plain fade for most steps, a slide for a
			// final "success-style" review step). 'none' = instant swap —
			// read by multi-step.js via data-aae-form-step-transition on the
			// step element itself.
			'step_transition' => String_Prop_Type::make()
				->enum( [ 'none', 'fade', 'slide', 'fade-slide' ] )
				->default( 'fade-slide' ),
		];
	}

	protected function define_allowed_child_types(): array {
		// Unrestricted, like the parent form itself — a step holds fields,
		// headings, paragraphs, div-block layout wrappers, anything.
		return [];
	}

	/**
	 * A fresh Step drops in with a Previous + Next pair, side by side in a
	 * flexbox row so they read like a nav bar from the start. Builders can
	 * delete/move/restyle them freely — multi-step.js only injects its OWN
	 * fallback Prev/Next DOM buttons when a step has NEITHER widget present
	 * (see lib/multi-step.js), so removing one here just means "this step
	 * has no back button", not "the runtime breaks".
	 *
	 * The very first step never needs Previous and the very last step never
	 * needs Next, but pruning that here would fight the "step order is just
	 * DOM order" model — a step doesn't know if it's first/last at drop time
	 * (it isn't inside the form yet), and previews/edits routinely reorder
	 * steps afterward. The runtime already hides the wrong one per-step
	 * (aae-form-step-nav buttons for a role that doesn't apply on the
	 * current step just don't get shown) — see multi-step.js's render().
	 */
	protected function define_default_children(): array {
		$prev = AAE_A_Form_Prev::generate()
			->editor_settings( [ 'title' => __( 'Previous', 'animation-addons-for-elementor' ) ] )
			->build();

		$next = AAE_A_Form_Next::generate()
			->editor_settings( [ 'title' => __( 'Next', 'animation-addons-for-elementor' ) ] )
			->build();

		return [
			Element_Builder::make( 'e-flexbox' )
				->children( [ $prev, $next ] )
				->settings(
					[
						'classes' => Classes_Prop_Type::generate( [ 'aae-form-step-nav-row' ] ),
					]
				)
				->editor_settings( [ 'title' => __( 'Step navigation', 'animation-addons-for-elementor' ) ] )
				->build(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'step_title' )
							->set_label( __( 'Step title', 'animation-addons-for-elementor' ) )
							->set_meta(
								[
									'description' => __( 'Shown in the step progress indicator, not inside the step itself.', 'animation-addons-for-elementor' ),
								]
							),
						Switch_Control::bind_to( 'step_nav_auto' )
							->set_label( __( 'Auto Step Navigation', 'animation-addons-for-elementor' ) )
							->set_meta(
								[
									'topDivider'  => true,
									'description' => __( 'Auto-adds Previous/Next buttons to THIS step if it has none of its own. Turn off to use ONLY the Next/Previous widgets you place yourself on this step.', 'animation-addons-for-elementor' ),
								]
							),
						Select_Control::bind_to( 'step_transition' )
							->set_label( __( 'Transition Effect', 'animation-addons-for-elementor' ) )
							->set_meta(
								[
									'description' => __( 'Animation played when this step becomes active (Next or Previous). Pure CSS — no extra script required.', 'animation-addons-for-elementor' ),
								]
							)
							->set_options(
								[
									[
										'value' => 'fade-slide',
										'label' => __( 'Fade + Slide', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'fade',
										'label' => __( 'Fade Only', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'slide',
										'label' => __( 'Slide Only', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'none',
										'label' => __( 'None (instant)', 'animation-addons-for-elementor' ),
									],
								]
							),
					]
				),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( '_cssid' )
							->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
							->set_meta( $this->get_css_id_control_meta() ),
					]
				),
		];
	}

	/**
	 * Every step is hidden by default; form.js adds `aae-form-step-active`
	 * to exactly one step at a time (mirrors the form's own
	 * `form-state-{value}` reveal pattern in form.scss).
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'display'        => String_Prop_Type::generate( 'none' ),
							'flex-direction' => String_Prop_Type::generate( 'column' ),
							'gap'            => Size_Prop_Type::generate(
								[
									'size' => 16,
									'unit' => 'px',
								]
							),
							'width'          => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
						]
					)
				),
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-step' => __DIR__ . '/aae-a-form-step.html.twig',
		];
	}
}
