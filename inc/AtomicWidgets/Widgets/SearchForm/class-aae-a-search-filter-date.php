<?php
/**
 * AAE Search Filter (Date) — atomic leaf.
 *
 * The Date filter, split out from the old combined filter so it is its own
 * selectable / styleable atomic element in the editor tree (independent from the
 * Category filter). Renders the toggle (label + chevron) and the dropdown (today /
 * yesterday / this-week / this-month presets + a custom from/to range + clear /
 * apply). Its dropdown is absolutely positioned via minimal inline structure — no
 * CSS file ships; everything visual is user-styleable from the panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Filter_Date extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-search-filter-date';
	}

	public function get_title() {
		return esc_html__( 'Date Filter', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'label'      => String_Prop_Type::make()->default( 'Date' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_filter_date' )
				->set_label( __( 'Date Filter', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'label' )
						->set_label( __( 'Label', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-filter-date' => __DIR__ . '/aae-a-search-filter-date.html.twig',
		];
	}
}
