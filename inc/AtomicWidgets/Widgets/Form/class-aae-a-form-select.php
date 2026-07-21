<?php
/**
 * AAE Form Select — atomic leaf WIDGET. Renders <select> with options.
 *
 * Mirrors Elementor's native e-form-select, EXCEPT the options control:
 * native uses Pro-only Repeatable_Attributes_Control + Options_Prop_Type
 * (ElementorPro namespace — unavailable to this free plugin), so options
 * here are a plain textarea, one option per line, "value|Label" (label
 * falls back to the value). A richer repeater control can replace it later
 * without changing the stored format's meaning.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Select extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form select with options (one per line, value|Label), required and multiple.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-select';
	}

	public function get_title() {
		return esc_html__( 'Select (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-select';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'select', 'dropdown', 'options' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'        => String_Prop_Type::make()->default( '' ),
			// One option per line, "value|Label|selected"; label falls back to
			// value, the optional 3rd part "selected" pre-selects that option.
			// The default set includes one |selected line as a live example.
			'options'     => String_Prop_Type::make()->default( "option-1|Option 1|selected\noption-2|Option 2\noption-3|Option 3" ),
			// Trigger/empty-state text for the multi-select UI (and the empty
			// first option of a single select). Empty keeps the default.
			'placeholder' => String_Prop_Type::make()->default( '' ),
			'required'    => Boolean_Prop_Type::make()->default( false ),
			'multiple'    => Boolean_Prop_Type::make()->default( false ),

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
							->set_label( __( 'Name', 'animation-addons-for-elementor' ) ),
						Textarea_Control::bind_to( 'options' )
							->set_label( __( 'Options', 'animation-addons-for-elementor' ) )
							->set_placeholder( "value|Label|selected\nvalue-2|Label 2" )
							->set_description(
								__( 'One option per line as value|Label. Label is optional (falls back to value). Add |selected at the end to pre-select it — e.g. yes|Yes|selected. In a multi-select you can pre-select more than one.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'placeholder' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Select…', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Empty-state text shown before anything is chosen. Leave blank to use “Select…”.', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'multiple' )
							->set_label( __( 'Multiple', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
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
							'height'        => Size_Prop_Type::generate(
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
							'background'    => Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( 'transparent' ),
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
							'color'         => Color_Prop_Type::generate( '#0c0d0e' ),
						]
					)
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::FOCUS )
						->add_props(
							[
								'border-color'  => Color_Prop_Type::generate( '#706F6F' ),
								'outline-style' => String_Prop_Type::generate( 'none' ),
							]
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-select' => __DIR__ . '/aae-a-form-select.html.twig',
		];
	}
}
