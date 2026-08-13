<?php
/**
 * AAE Form Checkbox — atomic leaf WIDGET. Renders <input type="checkbox">.
 *
 * Mirrors Elementor's native e-form-checkbox: appearance:none + display:grid
 * so the box is fully styleable, a ::before pseudo-element clip-path
 * checkmark revealed via the :checked pseudo-state. Inside the form it is
 * seeded wrapped in an e-flexbox "checkbox row" together with its label
 * (the ONLY field part that gets a wrapper).
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

class AAE_A_Form_Checkbox extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form checkbox with group name, choice value, required and checked.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-checkbox';
	}

	public function get_title() {
		return esc_html__( 'Checkbox (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-check-circle';
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
		return [ 'atomic', 'form', 'checkbox' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'       => String_Prop_Type::make()->default( '' ),
			'value'      => String_Prop_Type::make()->default( '' ),
			'required'   => Boolean_Prop_Type::make()->default( false ),
			'checked'    => Boolean_Prop_Type::make()->default( false ),

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
							->set_label( __( 'Group name', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Enter checkbox group name', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'value' )
							->set_label( __( 'Choice value', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Enter choice value', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'checked' )
							->set_label( __( 'Checked', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this checkbox is required but left unchecked. Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
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
	 * Native e-form-checkbox styling: appearance:none kills the browser
	 * checkbox, the box becomes a grid, and ::before is a clip-path
	 * checkmark faded in by :checked.
	 */
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
						// Without appearance:none the checkbox keeps native
						// browser chrome and is virtually impossible to style.
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
								'size' => 0,
								'unit' => 'px',
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
			)
			->add_variant(
				Style_Variant::make()
					->set_state( Style_States::CHECKED )
					->add_props(
						[
							'background' => Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#69727D' ),
								]
							),
						]
					)
			);
	}

	private function get_base_before_style(): Style_Definition {
		return Style_Definition::make()
			->add_variant(
				Style_Variant::make()->add_props(
					[
						'clip-path'  => String_Prop_Type::generate( 'polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%)' ),
						'background' => Background_Prop_Type::generate(
							[
								'color' => Color_Prop_Type::generate( 'currentColor' ),
							]
						),
						'content'    => String_Prop_Type::generate( '""' ),
						'height'     => Size_Prop_Type::generate(
							[
								'size' => 65,
								'unit' => '%',
							]
						),
						'width'      => Size_Prop_Type::generate(
							[
								'size' => 65,
								'unit' => '%',
							]
						),
						'opacity'    => Size_Prop_Type::generate(
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
			'elementor/elements/aae-a-form-checkbox' => __DIR__ . '/aae-a-form-checkbox.html.twig',
		];
	}
}
