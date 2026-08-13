<?php
/**
 * AAE Form Rating — atomic leaf WIDGET. Renders a star-rating field.
 *
 * Multi-Step Forms (Pro) precedent: the widget class lives in the FREE
 * plugin unconditionally (Elementor's atomic widget registry has no
 * mechanism for a Pro plugin to add a brand-new element type from outside —
 * see class-atomic.php's widgets_registry) while `is_pro => true` in
 * class-atomic.php's dashboard metadata marks it as a premium-tier feature
 * (upsell badge only, not a functional gate).
 *
 * Progressive enhancement, same shape as lib/multi-select.js: the real
 * control is a plain `<input type="number">` (min 1, max = star count, step
 * 1) so submit/validation/sanitize are completely unchanged — Validator.php
 * validates it exactly like any other number field. lib/rating.js hides the
 * native spinner and overlays a row of clickable star buttons that mirror
 * clicks onto the real input's value + dispatch a `change` event, so the
 * rest of the form runtime never needs to know rating is anything but a
 * number field.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Rating extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Star-rating field (Pro) — required, custom star count, per-field error message.';

	const DEFAULT_MAX = 5;

	public static function get_element_type(): string {
		return 'e-aae-a-form-rating';
	}

	public function get_title() {
		return esc_html__( 'Rating (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-star-o';
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
		return [ 'atomic', 'form', 'rating', 'star', 'review', 'feedback' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'       => String_Prop_Type::make()->default( '' ),

			// How many stars to render. The native input's max mirrors this so
			// a crafted request can never post a value above it (Validator.php
			// re-checks min/max from the SCHEMA snapshot, not this control).
			'max'        => Number_Prop_Type::make()->default( self::DEFAULT_MAX ),

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
						Text_Control::bind_to( 'max' )
							->set_label( __( 'Number of stars', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'How many stars to show (e.g. 5 or 10).', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field is required but no star is picked. Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
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
	 * Base style targets the STAR ROW wrapper (.aae-a-form-rating), not the
	 * hidden native input — rating.js paints stars as inline SVGs that inherit
	 * `color`/`font-size` from here, same "style the wrapper, runtime inherits
	 * it" approach as the Next/Prev button icons.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							// Full-width like every other field widget (Input/
							// Select/Textarea) — without it Rating shrink-wraps
							// to its star row's own width, leaving room for
							// Submit (or its own error message) to tuck in on
							// the same line instead of wrapping below.
							'width'     => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
							'display'   => String_Prop_Type::generate( 'flex' ),
							'gap'       => Size_Prop_Type::generate(
								[
									'size' => 4,
									'unit' => 'px',
								]
							),
							'font-size' => Size_Prop_Type::generate(
								[
									'size' => 28,
									'unit' => 'px',
								]
							),
							'color'     => Color_Prop_Type::generate( '#D6D5D5' ),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-rating' => __DIR__ . '/aae-a-form-rating.html.twig',
		];
	}
}
