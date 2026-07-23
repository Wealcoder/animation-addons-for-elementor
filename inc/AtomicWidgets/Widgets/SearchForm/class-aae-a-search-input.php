<?php
/**
 * AAE Search Input — atomic leaf rendering the <input type="search">.
 *
 * No core atomic element renders a search input, so this is a dedicated leaf. It
 * carries the placeholder / autocomplete / field-name props and pre-fills the value
 * from the current search query. Grows to fill the field row via a flex default in
 * define_base_styles(); everything visual is user-styleable from the panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Input extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-search-input';
	}

	public function get_title() {
		return esc_html__( 'Search Input', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'placeholder'  => String_Prop_Type::make()->default( 'Search...' ),
			'autocomplete' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_input' )
				->set_label( __( 'Input', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'placeholder' )
						->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'autocomplete' )
						->set_label( __( 'Browser Autocomplete', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'flex', Flex_Prop_Type::generate( [
						'flexGrow'   => Number_Prop_Type::generate( 1 ),
						'flexShrink' => Number_Prop_Type::generate( 1 ),
						'flexBasis'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => '%' ] ),
					] ) )
					->add_prop( 'min-width', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
					->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-input' => __DIR__ . '/aae-a-search-input.html.twig',
		];
	}

	/** Pre-fill the input with the current search query (so results pages keep the term). */
	public function get_atomic_settings(): array {
		$settings                  = parent::get_atomic_settings();
		$settings['search_value']  = get_search_query();
		return $settings;
	}
}
