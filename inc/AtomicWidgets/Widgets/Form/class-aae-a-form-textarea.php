<?php
/**
 * AAE Form Textarea — atomic leaf WIDGET. Renders a single <textarea>.
 *
 * Atomic_Widget_Base (not Atomic_Element_Base) so the editor overlay is
 * handled outside the rendered markup — an element-based <textarea> root
 * showed the overlay HTML as literal text inside the field. Mirrors
 * Elementor's native e-form-textarea. See class-aae-a-form-input.php.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Textarea extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form textarea with customizable rows, placeholder, required and readonly.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-textarea';
	}

	public function get_title() {
		return esc_html__( 'Textarea (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-textarea';
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
		return [ 'atomic', 'form', 'textarea', 'message' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'placeholder' => String_Prop_Type::make()->default( '' ),
			'rows'        => Number_Prop_Type::make()->default( 4 ),
			'required'    => Boolean_Prop_Type::make()->default( false ),
			'readonly'    => Boolean_Prop_Type::make()->default( false ),

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
						Text_Control::bind_to( 'placeholder' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) ),
						Number_Control::bind_to( 'rows' )
							->set_label( __( 'Rows', 'animation-addons-for-elementor' ) )
							->set_min( 1 ),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'readonly' )
							->set_label( __( 'Read only', 'animation-addons-for-elementor' ) ),
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
			'elementor/elements/aae-a-form-textarea' => __DIR__ . '/aae-a-form-textarea.html.twig',
		];
	}
}
