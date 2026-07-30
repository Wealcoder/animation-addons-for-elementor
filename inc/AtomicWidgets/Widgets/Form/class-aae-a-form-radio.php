<?php
/**
 * AAE Form Radio — atomic leaf WIDGET. Renders <input type="radio">.
 *
 * Mirrors Elementor's native e-form-radio-button: same appearance:none +
 * grid technique as the checkbox, but round (border-radius 50%) with a
 * ::before dot revealed via :checked. Radios sharing the same `name`
 * form one exclusive group.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Radio extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form radio button with group name, choice value, required and checked. Radios sharing a group name are exclusive.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-radio';
	}

	public function get_title() {
		return esc_html__( 'Radio (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-circle-o';
	}

	public function show_in_panel() {
		return false;
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'radio', 'choice' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'       => String_Prop_Type::make()->default( '' ),
			'value'      => String_Prop_Type::make()->default( '' ),
			'required'   => Boolean_Prop_Type::make()->default( false ),
			'checked'    => Boolean_Prop_Type::make()->default( false ),

			// Per-field validation message — any radio in the group may carry
			// it; the first non-empty one wins for the whole group.
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
							->set_label( __( 'Group name', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Enter radio group name', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Give every radio in the SAME group the SAME group name — that is what makes only one selectable at a time. Different groups need different names.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'value' )
							->set_label( __( 'Choice value', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Enter choice value', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'The value submitted when this radio is chosen — make it unique within the group (e.g. yes / no / maybe).', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'checked' )
							->set_label( __( 'Checked', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when the group is required but nothing is chosen — set it on any one radio of the group. Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
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

	/** Native e-form-radio-button styling: round box, ::before dot on :checked. */
	protected function define_base_styles(): array {
		return [
			'base'                 => $this->get_base_style(),
			'base::before'         => $this->get_base_before_style(),
			'base:checked::before' => $this->get_base_checked_before_style(),
		];
	}

	private function get_base_style(): Style_Definition {
		return Style_Definition::make()
			->add_variant(
				Style_Variant::make()->add_props(
					[
						// Without appearance:none the radio keeps native browser
						// chrome and is virtually impossible to style.
						'appearance'    => String_Prop_Type::generate( 'none' ),
						'color'         => Color_Prop_Type::generate( '#ffffff' ),
						'display'       => String_Prop_Type::generate( 'grid' ),
						'background'    => Background_Prop_Type::generate(
							[
								'color' => Color_Prop_Type::generate( 'transparent' ),
							]
						),
						'align-items'   => String_Prop_Type::generate( 'center' ),
						'justify-items' => String_Prop_Type::generate( 'center' ),
						'border-radius' => Size_Prop_Type::generate(
							[
								'size' => 50,
								'unit' => '%',
							]
						),
						'border-color'  => Color_Prop_Type::generate( '#D6D5D5' ),
						'border-width'  => Size_Prop_Type::generate(
							[
								'size' => 1,
								'unit' => 'px',
							]
						),
						'border-style'  => String_Prop_Type::generate( 'solid' ),
						'width'         => Size_Prop_Type::generate(
							[
								'size' => 1.15,
								'unit' => 'em',
							]
						),
						'height'        => Size_Prop_Type::generate(
							[
								'size' => 1.15,
								'unit' => 'em',
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
							'border-color'  => Color_Prop_Type::generate( '#706F6F' ),
							'outline-style' => String_Prop_Type::generate( 'none' ),
						]
					)
			);
	}

	private function get_base_before_style(): Style_Definition {
		return Style_Definition::make()
			->add_variant(
				Style_Variant::make()->add_props(
					[
						'border-radius' => Size_Prop_Type::generate(
							[
								'size' => 50,
								'unit' => '%',
							]
						),
						'background'    => Background_Prop_Type::generate(
							[
								'color' => Color_Prop_Type::generate( '#706F6F' ),
							]
						),
						'content'       => String_Prop_Type::generate( '""' ),
						'height'        => Size_Prop_Type::generate(
							[
								'size' => 65,
								'unit' => '%',
							]
						),
						'width'         => Size_Prop_Type::generate(
							[
								'size' => 65,
								'unit' => '%',
							]
						),
						'opacity'       => Size_Prop_Type::generate(
							[
								'size' => 0,
								'unit' => '%',
							]
						),
					]
				)
			);
	}

	private function get_base_checked_before_style(): Style_Definition {
		return Style_Definition::make()
			->add_variant(
				Style_Variant::make()->add_props(
					[
						'opacity' => Size_Prop_Type::generate(
							[
								'size' => 100,
								'unit' => '%',
							]
						),
					]
				)
			);
	}

	protected function define_atomic_pseudo_states(): array {
		return [
			Style_States::get_pseudo_states_map()['checked'],
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-radio' => __DIR__ . '/aae-a-form-radio.html.twig',
		];
	}
}
