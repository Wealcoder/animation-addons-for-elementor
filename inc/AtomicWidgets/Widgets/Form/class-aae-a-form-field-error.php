<?php
/**
 * AAE Form Field Error — atomic leaf WIDGET. The STYLE SOURCE for the
 * inline "This field is required." validation messages.
 *
 * The real error containers stay runtime-injected by form.js (spec:
 * validation messages are built-in slots, never removable widgets — a
 * deleted widget must not silently kill validation feedback). This widget
 * only makes them styleable: it renders a sample <span> that is visible in
 * the editor canvas (style it there like any atomic element) and hidden on
 * the frontend; the submit runtime copies its classes — base + user styles —
 * and its text onto every injected .aae-form-field-error span in the same
 * form. No widget present → errors fall back to the stylesheet defaults.
 *
 * Atomic_Widget_Base (not Atomic_Element_Base) so the editor overlay is
 * handled outside the rendered markup — see class-aae-a-form-input.php.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Field_Error extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Style source for the inline field validation messages — its look and text are copied onto every "This field is required." error the form shows.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-field-error';
	}

	public function get_title() {
		return esc_html__( 'Field Error (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-warning';
	}

	public function show_in_panel() {
		return false;
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'error', 'validation', 'required', 'message' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Doubles as the form's required-field message: the runtime reads
			// this element's text for empty-required errors (format errors like
			// "Please enter a valid email address." keep the localized copy).
			'text'       => Html_V3_Prop_Type::make()->default(
				[
					'content'  => String_Prop_Type::generate( __( 'This field is required.', 'animation-addons-for-elementor' ) ),
					'children' => [],
				]
			),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Inline_Editing_Control::bind_to( 'text' )
							->set_label( __( 'Required message', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown under every required field the visitor left empty. Visible only in the editor — on the live page it appears when validation fails.', 'animation-addons-for-elementor' )
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'display'   => String_Prop_Type::generate( 'block' ),
							'color'     => Color_Prop_Type::generate( '#870000' ),
							'font-size' => Size_Prop_Type::generate(
								[
									'size' => 0.85,
									'unit' => 'em',
								]
							),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-field-error' => __DIR__ . '/aae-a-form-field-error.html.twig',
		];
	}
}
