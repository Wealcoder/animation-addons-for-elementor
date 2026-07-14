<?php
/**
 * AAE Search Field — atomic container rendered as the <form>.
 *
 * The real search form: submits natively to the site's search results (GET ?s=)
 * when Ajax is off, and is the row that holds the Input, the Filter and the Submit
 * button. Its default flex-row layout comes from define_base_styles(); everything
 * else is user-styleable from the panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-search-input.php';
require_once __DIR__ . '/class-aae-a-search-filter-date.php';
require_once __DIR__ . '/class-aae-a-search-filter-category.php';
require_once __DIR__ . '/class-aae-a-search-submit.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Input;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Date;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Category;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Submit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Search_Field extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-search-field';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-field';
	}

	public function get_title() {
		return esc_html__( 'Search Field', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function show_in_panel() {
		return false;
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Search_Input::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Input' ] )
				->build(),

			AAE_A_Search_Filter_Date::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Date Filter' ] )
				->build(),

			AAE_A_Search_Filter_Category::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Category Filter' ] )
				->build(),

			AAE_A_Search_Submit::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Submit' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-search-input', 'e-aae-a-search-filter-date', 'e-aae-a-search-filter-category', 'e-aae-a-search-submit' ];
	}

	protected function define_default_html_tag() {
		return 'form';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-field' => __DIR__ . '/aae-a-search-field.html.twig',
		];
	}

	/** Expose the native search action URL so the <form> can GET ?s= when Ajax is off. */
	public function get_atomic_settings(): array {
		$settings                 = parent::get_atomic_settings();
		$settings['form_action']  = home_url( '/' );
		return $settings;
	}
}
