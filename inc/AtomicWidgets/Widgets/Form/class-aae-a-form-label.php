<?php
/**
 * AAE Form Label — atomic leaf WIDGET. Renders a single <label for="…">.
 *
 * Atomic_Widget_Base (not Atomic_Element_Base) so the editor overlay is
 * handled outside the rendered markup — mirrors Elementor's native
 * e-form-label. See class-aae-a-form-input.php for the full reasoning.
 *
 * A label and its input are loose siblings inside the form, associated only
 * by ID — this widget's `input-id` prop → the input's `_cssid` → renders
 * `for=`. The input-id control is a Select bound to the `form-elements`
 * collection, so the editor shows a dropdown of the form's field IDs.
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

class AAE_A_Form_Label extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form label, linked to an input by ID (renders the for attribute).';

	public static function get_element_type(): string {
		return 'e-aae-a-form-label';
	}

	public function get_title() {
		return esc_html__( 'Label (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function show_in_panel() {
		return false;
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'label' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'tag'        => String_Prop_Type::make()->default( 'label' ),
			'text'       => Html_V3_Prop_Type::make()->default(
				[
					'content'  => String_Prop_Type::generate( __( 'Label', 'animation-addons-for-elementor' ) ),
					'children' => [],
				]
			),
			'input-id'   => String_Prop_Type::make()->default( '' )->description( 'ID of the connected input' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Inline_Editing_Control::bind_to( 'text' )
							->set_label( __( 'Label text', 'animation-addons-for-elementor' ) ),
						Select_Control::bind_to( 'input-id' )
							->set_label( __( 'Connected to input ID', 'animation-addons-for-elementor' ) )
							->set_options( [] )
							->set_collection_id( 'form-elements' )
							->set_meta( [ 'layout' => 'full' ] ),
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
							'display'     => String_Prop_Type::generate( 'block' ),
							'font-size'   => Size_Prop_Type::generate(
								[
									'size' => 14,
									'unit' => 'px',
								]
							),
							'font-weight' => String_Prop_Type::generate( '500' ),
							'color'       => Color_Prop_Type::generate( '#0c0d0e' ),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-label' => __DIR__ . '/aae-a-form-label.html.twig',
		];
	}
}
