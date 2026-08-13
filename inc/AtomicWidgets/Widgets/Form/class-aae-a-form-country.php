<?php
/**
 * AAE Form Country — atomic leaf WIDGET. Renders a country <select>.
 *
 * Structurally a single-value sibling of AAE_A_Form_Select: same "value|Label"
 * options format, same base styles, same twig mechanics — but the options
 * default to the full built-in ISO 3166-1 list (WCF_ADDONS\Forms\Countries),
 * values submit as ISO alpha-2 codes, and the rendered <select> carries
 * autocomplete="country-name" for browser autofill (spec MVP requirement).
 *
 * The list ships as the `options` prop DEFAULT (not injected at render time)
 * deliberately: the twig also renders client-side in the editor preview,
 * where only settings + prop defaults exist — extra PHP-side context would
 * leave the editor's select empty. Builders can prune/reorder the list to
 * pin priority countries, or append |selected to pre-pick one.
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
use WCF_ADDONS\Forms\Countries;

class AAE_A_Form_Country extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Country dropdown with the full ISO country list built in; values submit as ISO codes.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-country';
	}

	public function get_title() {
		return esc_html__( 'Country (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-globe';
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
		return [ 'atomic', 'form', 'country', 'select', 'dropdown', 'nationality' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'name'        => String_Prop_Type::make()->default( '' ),
			// Same one-per-line "value|Label|selected" format as the Select
			// widget, pre-filled with the full built-in country list.
			'options'     => String_Prop_Type::make()->default( Countries::options_string() ),
			'placeholder' => String_Prop_Type::make()->default( __( 'Select country…', 'animation-addons-for-elementor' ) ),
			'required'    => Boolean_Prop_Type::make()->default( false ),

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
							->set_label( __( 'Countries', 'animation-addons-for-elementor' ) )
							->set_placeholder( "BD|Bangladesh\nUS|United States|selected" )
							->set_description(
								__( 'One country per line as code|Name. The full list is pre-filled — delete lines you don’t need, move favorites to the top to pin them, or add |selected at the end of one line to pre-select it.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'placeholder' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Select country…', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Empty-state text shown before a country is chosen.', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
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
		// Mirrors AAE_A_Form_Select so both dropdown fields look identical
		// out of the box.
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
			'elementor/elements/aae-a-form-country' => __DIR__ . '/aae-a-form-country.html.twig',
		];
	}
}
