<?php
/**
 * AAE Form Range — atomic leaf WIDGET. Renders a single <input type="range">.
 *
 * Split out of AAE_A_Form_Input (which originally gained `range` as a Pro
 * `type` value alongside date/time) into its own widget because a range
 * slider needs a property no other input type does: its own colour.
 * `accent-color` — the one CSS property that actually recolors a native
 * range's track/thumb consistently across browsers — has NO equivalent key
 * in Elementor's atomic Style-panel schema (confirmed: no Color control
 * exists in the Content tab either — atomic widgets only expose color
 * pickers through Style_Definition/Color_Prop_Type, i.e. the Style tab).
 *
 * So the real Style-panel Background Color IS the paintable control here
 * (added to this widget's own base style, same as Select's background prop
 * — Style tab, not Content tab) — and `lib/range.js` bridges it to
 * `accent-color` by reading the input's own computed `background-color` at
 * init (mirrors `lib/multi-select.js`'s `applyStyle()`, which does the same
 * computed-style-copy trick for the multi-select trigger). This is a live
 * bridge, not a build-time one: it keeps working across responsive
 * breakpoints/states without needing the schema to expose `accent-color`
 * directly, and a builder finds it exactly where every other field's colour
 * lives — Style tab → Background.
 *
 * Same free-plugin-vs-Pro reasoning as Rating (see class-aae-a-form-rating.php):
 * a genuinely new element type, so it lives in the FREE plugin
 * unconditionally, with `is_pro => true` in class-atomic.php's dashboard
 * metadata marking it premium-tier (upsell badge only).
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Range extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Range slider field (Pro) — min/max/step, accent color, per-field error message.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-range';
	}

	public function get_title() {
		return esc_html__( 'Range (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'range', 'slider' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'       => String_Prop_Type::make()->default( '' ),

			'min'        => String_Prop_Type::make()->default( '0' ),
			'max'        => String_Prop_Type::make()->default( '100' ),
			'step'       => String_Prop_Type::make()->default( '1' ),

			'required'   => Boolean_Prop_Type::make()->default( false ),

			// Per-field validation message — overrides the form-wide default.
			'error_message' => String_Prop_Type::make()->default( '' ),
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
							->set_placeholder( __( 'Enter field name', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'min' )
							->set_label( __( 'Min value', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'max' )
							->set_label( __( 'Max value', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'step' )
							->set_label( __( 'Step', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Increment between selectable values (e.g. 1, 5, 0.5).', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Please enter a valid value.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field fails validation. Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
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
	 * Same footprint as the text-family Input widget's base style, plus a
	 * `background` prop — Style tab → Background Color IS the slider's color
	 * control here (there's no `accent-color` key in the atomic style
	 * schema): `lib/range.js` reads this element's own computed
	 * background-color at init and copies it onto `accentColor`, the one CSS
	 * property that actually recolors a native range's track/thumb.
	 * Defaults to the same neutral gray the Checkbox's checked state uses
	 * (#69727D), not transparent — a transparent/rgba(0,0,0,0) background
	 * would make accent-color effectively invisible until the user paints a
	 * real color, which reads as "broken" rather than "unstyled default".
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'width'      => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
							'background' => Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#69727D' ),
								]
							),
						]
					)
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::FOCUS )
						->add_props(
							[
								'outline-style' => String_Prop_Type::generate( 'none' ),
							]
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-range' => __DIR__ . '/aae-a-form-range.html.twig',
		];
	}
}
