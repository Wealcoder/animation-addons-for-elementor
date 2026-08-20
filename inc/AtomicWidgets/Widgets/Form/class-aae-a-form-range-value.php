<?php
/**
 * AAE Form Range Value — atomic leaf WIDGET. The live readout half of a
 * Range Group ("250 Sq.").
 *
 * Why this is its OWN element type instead of a core Atomic_Paragraph the
 * parent seeds text into: the runtime has to FIND this node, and the only
 * way to hand a core element a durable hook is the `classes` prop — which
 * the editing panel reports as "Some classes are missing" and whose ✕
 * button unapplies (see CLAUDE.md, "Never put a functional hook class in
 * the `classes` prop"). One click would break the readout. Its own type
 * means its own twig, so `data-aae-range-value` is rendered markup nothing
 * in the panel can strip — and the prefix/suffix travel as props on the
 * element that actually prints them, not as parent state.
 *
 * It renders a real `<output>`: the semantic element for "a value computed
 * from other form controls", so assistive tech announces updates without
 * an aria-live hack.
 *
 * DESIGN-LESS on purpose — the base style carries one structural prop and
 * no look at all. Typography, colour, spacing and background are the
 * builder's, through the normal Style tab.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Range_Value extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Live value readout for a Range Group — prints the slider\'s current value with an optional prefix/suffix.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-range-value';
	}

	public function get_title() {
		return esc_html__( 'Range Value (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	/**
	 * Listed in the AAE Form panel category so a builder who deleted it can
	 * drag it back into a Range Group.
	 *
	 * Leaf widgets extend Atomic_Widget_Base (→ classic Widget_Base), so the
	 * panel reads THIS pair — show_in_panel() + get_categories(); the
	 * Atomic_Element_Base pair is silently never called here.
	 */
	public function show_in_panel() {
		return true;
	}

	public function get_categories(): array {
		return [ 'aae-atomic-form' ];
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'range', 'slider', 'value', 'output', 'readout' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Printed immediately before/after the number. The prefix joins it
			// directly ("$250"); the suffix is spaced ("250 Sq.") — the runtime
			// rebuilds the string the same way, so dragging never changes the
			// spacing the builder sees at rest.
			'prefix'       => String_Prop_Type::make()->default( '' ),
			'suffix'       => String_Prop_Type::make()->default( '' ),

			// What this prints before the runtime has read the slider — i.e.
			// the no-JS fallback. The number itself is never authored: it
			// belongs to the slider.
			'default_text' => String_Prop_Type::make()->default( '0' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'prefix' )
							->set_label( __( 'Prefix', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'e.g. $', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Printed directly before the number, with no space.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'suffix' )
							->set_label( __( 'Suffix', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'e.g. Sq.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Printed after the number, separated by a space.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'default_text' )
							->set_label( __( 'Fallback value', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown until the slider is read — visitors with JavaScript off see this.', 'animation-addons-for-elementor' )
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
	 * One structural prop, no look. `<output>` is `display: inline` per the UA
	 * sheet, which makes width/padding/alignment from the Style tab behave
	 * unpredictably; `inline-block` is the smallest change that makes every
	 * Style-tab control mean what it says. Everything cosmetic is left to the
	 * builder (see the skill's design-less rule).
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'display' => String_Prop_Type::generate( 'inline-block' ),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-range-value' => __DIR__ . '/aae-a-form-range-value.html.twig',
		];
	}
}
