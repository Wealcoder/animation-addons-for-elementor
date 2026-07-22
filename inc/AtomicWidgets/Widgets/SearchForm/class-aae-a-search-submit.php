<?php
/**
 * AAE Search Submit — atomic leaf rendering the <button type="submit">.
 *
 * Icon (inline magnifier SVG) or text button, chosen via a prop. Default centring
 * comes from define_base_styles(); the user styles colours / spacing / size from the
 * panel. No CSS file ships.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Submit extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-search-submit';
	}

	public function get_title() {
		return esc_html__( 'Search Submit', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-button';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'button_type' => String_Prop_Type::make()->enum( [ 'icon', 'text' ] )->default( 'icon' ),
			'button_text' => String_Prop_Type::make()->default( 'Search' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_submit' )
				->set_label( __( 'Button', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'button_type' )
						->set_label( __( 'Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'icon', 'label' => __( 'Icon', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'text', 'label' => __( 'Text', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'button_text' )
						->set_label( __( 'Text', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'min-width', Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ) )
					->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-submit' => __DIR__ . '/aae-a-search-submit.html.twig',
		];
	}
}
