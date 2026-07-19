<?php
/**
 * AAE Form Input — atomic leaf WIDGET. Renders a single <input>.
 *
 * Atomic_Widget_Base, NOT Atomic_Element_Base: the editor injects its
 * element-overlay markup INSIDE an element's root node, which breaks
 * void/plain-text roots (<input>, <textarea> shows the overlay as literal
 * text). Widgets get their overlay handled outside the rendered markup —
 * this mirrors Elementor's native e-form-input, which is also a widget.
 *
 * One class handles the text-family input types (text/email/tel/number/
 * password) via the `type` enum prop. Linked to its label by ID: the
 * label's `input-id` prop points at this widget's `_cssid`.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Input extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form input with customizable type (text/email/number/tel/password), placeholder, required and readonly.';

	const TYPES = [ 'text', 'email', 'number', 'tel', 'password' ];

	/**
	 * The input `type` values this widget accepts — the free base plus
	 * whatever the pro plugin adds (e.g. date, time) via the filter. Used for
	 * BOTH the prop enum and the Select control's options, so a pro-added
	 * type is selectable AND passes schema validation.
	 *
	 * @return array<int,array{value:string,label:string}>
	 */
	public static function type_options(): array {
		$options = [
			[
				'value' => 'text',
				'label' => __( 'Text', 'animation-addons-for-elementor' ),
			],
			[
				'value' => 'email',
				'label' => __( 'Email', 'animation-addons-for-elementor' ),
			],
			[
				'value' => 'number',
				'label' => __( 'Number', 'animation-addons-for-elementor' ),
			],
			[
				'value' => 'tel',
				'label' => __( 'Tel', 'animation-addons-for-elementor' ),
			],
			[
				'value' => 'password',
				'label' => __( 'Password', 'animation-addons-for-elementor' ),
			],
		];

		/**
		 * Filter the input type options. Pro adds date/time here.
		 *
		 * @param array $options [ [ 'value' => string, 'label' => string ], … ]
		 */
		return (array) apply_filters( 'aae_form/input_types', $options );
	}

	/** Just the value slugs, for the prop enum. */
	private static function type_values(): array {
		return array_values(
			array_filter(
				array_map(
					static function ( $opt ) {
						return is_array( $opt ) ? ( $opt['value'] ?? '' ) : '';
					},
					self::type_options()
				),
				'strlen'
			)
		);
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-input';
	}

	public function get_title() {
		return esc_html__( 'Input (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'input', 'text', 'email', 'number', 'tel', 'password' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'placeholder' => String_Prop_Type::make()->default( '' ),
			'type'        => String_Prop_Type::make()->enum( self::type_values() )->default( 'text' ),
			'required'    => Boolean_Prop_Type::make()->default( false ),
			'readonly'    => Boolean_Prop_Type::make()->default( false ),

			// Validation rules (user-supplied): accepted value range for number
			// fields, rendered as native min/max attributes. Kept as strings so
			// empty = no rule.
			'min'         => String_Prop_Type::make()->default( '' ),
			'max'         => String_Prop_Type::make()->default( '' ),

			// Per-field validation message — overrides the form-wide default
			// (the Field Error element's text) for THIS field only.
			'error_message' => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'placeholder' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Enter placeholder text', 'animation-addons-for-elementor' ) ),
						Select_Control::bind_to( 'type' )
						->set_label( __( 'Type', 'animation-addons-for-elementor' ) )
						->set_options( self::type_options() ),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'readonly' )
							->set_label( __( 'Read only', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'min' )
							->set_label( __( 'Min value', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Number fields: smallest accepted value (e.g. 18).', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'max' )
							->set_label( __( 'Max value', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Number fields: largest accepted value.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field fails validation (empty, wrong format, out of range). Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
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
			'elementor/elements/aae-a-form-input' => __DIR__ . '/aae-a-form-input.html.twig',
		];
	}
}
