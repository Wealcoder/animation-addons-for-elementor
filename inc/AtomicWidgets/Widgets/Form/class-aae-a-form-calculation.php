<?php
/**
 * AAE Form Calculation — atomic leaf WIDGET. A read-only field whose value is
 * computed from OTHER fields by a builder-authored formula ("{qty} * {price}").
 *
 * Same free-plugin/is_pro-badge arrangement as Rating/Range/Country: the class
 * lives in the free plugin unconditionally (Elementor's atomic widget registry
 * has no mechanism for a Pro plugin to add a brand-new element type from
 * outside — see class-atomic.php's widgets_registry) while `is_pro => true` in
 * the dashboard metadata marks it premium-tier (upsell badge, not a gate).
 *
 * Shape mirrors Rating: a real (hidden) `<input>` carries the computed value
 * so it submits/stores/reaches emails exactly like any other field, and a
 * visible display box shows the formatted result. lib/calculation.js does the
 * live math; inc/Forms/Formula.php re-does it server-side and the Validator
 * rejects a mismatch — a visitor editing the hidden input in DevTools cannot
 * change the stored total.
 *
 * The input is readonly (not disabled) so it still submits, and it is NEVER
 * "required" — its value is derived, so a required-check would be meaningless.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Calculation extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Read-only field that computes a total from other fields using a formula, e.g. {quantity} * {price}.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-calculation';
	}

	public function get_title() {
		return esc_html__( 'Calculation (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	/**
	 * Listed in the AAE Form panel category so a builder can drag this field
	 * into a form instead of only getting what a preset seeded.
	 *
	 * Leaf form widgets extend Atomic_Widget_Base (→ classic Widget_Base), so the
	 * panel reads THIS pair — show_in_panel() + get_categories(). The
	 * Atomic_Element_Base pair (should_show_in_panel() + define_panel_categories())
	 * is silently never called here; see class-atomic.php::register_atomic_categories().
	 */
	public function show_in_panel() {
		return true;
	}

	public function get_categories(): array {
		return [ 'aae-atomic-form' ];
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'calculation', 'calculator', 'total', 'price', 'quote', 'sum' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'       => String_Prop_Type::make()->default( '' ),

			// The formula itself. Field references use {field_name}; the server
			// re-evaluates it from the SCHEMA snapshot, never from the posted
			// value, so editing the hidden input can't change what gets stored.
			'formula'    => String_Prop_Type::make()->default( '' ),

			// Display formatting. These never affect the stored value — the
			// submitted number stays machine-readable.
			'decimals'   => Number_Prop_Type::make()->default( 2 ),
			'prefix'     => String_Prop_Type::make()->default( '' ),
			'suffix'     => String_Prop_Type::make()->default( '' ),

			// Shown while the formula can't be computed yet (empty inputs,
			// division by zero) — nothing is submitted in that state.
			'empty_text' => String_Prop_Type::make()->default( '—' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'name' )
							->set_label( __( 'Name', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'e.g. order_total', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'The key this total is saved and emailed under.', 'animation-addons-for-elementor' )
							),
						Textarea_Control::bind_to( 'formula' )
							->set_label( __( 'Formula', 'animation-addons-for-elementor' ) )
							->set_placeholder( '{quantity} * {price}' )
							->set_description(
								__( 'Reference other fields by their Name in curly braces. Supports + - * / % and round(), floor(), ceil(), abs(), min(), max() — e.g. round({quantity} * {price} * 1.15, 2). Empty fields count as 0. For dates use days_between({check_in}, {check_out}) — subtracting dates with "−" does not work.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'decimals' )
							->set_label( __( 'Decimal places', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'How many digits after the decimal point (0–6).', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'prefix' )
							->set_label( __( 'Prefix', 'animation-addons-for-elementor' ) )
							->set_placeholder( '$' )
							->set_description(
								__( 'Shown before the number. Display only — not part of the saved value.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'suffix' )
							->set_label( __( 'Suffix', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( '/month', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown after the number. Display only — not part of the saved value.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'empty_text' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) )
							->set_placeholder( '—' )
							->set_description(
								__( 'Shown before the formula can be calculated. Nothing is submitted in that state.', 'animation-addons-for-elementor' )
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
	 * Base style targets the visible display box (the real input is hidden),
	 * so builders style it like any other field — same "style the wrapper"
	 * approach Rating uses for its star row.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'width'         => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
							'display'       => String_Prop_Type::generate( 'flex' ),
							'align-items'   => String_Prop_Type::generate( 'center' ),
							'min-height'    => Size_Prop_Type::generate(
								[
									'size' => 40,
									'unit' => 'px',
								]
							),
							'padding'       => Size_Prop_Type::generate(
								[
									'size' => 10,
									'unit' => 'px',
								]
							),
							'border-width'  => Size_Prop_Type::generate(
								[
									'size' => 1,
									'unit' => 'px',
								]
							),
							'border-style'  => String_Prop_Type::generate( 'solid' ),
							'border-color'  => Color_Prop_Type::generate( '#D6D5D5' ),
							'border-radius' => Size_Prop_Type::generate(
								[
									'size' => 3,
									'unit' => 'px',
								]
							),
							'font-size'     => Size_Prop_Type::generate(
								[
									'size' => 14,
									'unit' => 'px',
								]
							),
							'font-weight'   => String_Prop_Type::generate( '600' ),
							'color'         => Color_Prop_Type::generate( '#0c0d0e' ),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-calculation' => __DIR__ . '/aae-a-form-calculation.html.twig',
		];
	}
}
