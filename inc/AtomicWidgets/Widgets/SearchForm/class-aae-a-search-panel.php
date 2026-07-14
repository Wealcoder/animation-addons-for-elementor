<?php
/**
 * AAE Search Panel — atomic container.
 *
 * The togglable container that holds the search <form> and the live-results box.
 * In inline mode it's always visible; in dropdown / fullscreen mode the runtime JS
 * flips it between hidden and a floating panel / viewport overlay (positioning is
 * applied as inline styles at runtime — no CSS file ships).
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-search-field.php';
require_once __DIR__ . '/class-aae-a-search-results.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Field;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Results;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Search_Panel extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-search-panel';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-panel';
	}

	public function get_title() {
		return esc_html__( 'Search Panel', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-container';
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
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
			),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Search_Field::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Field' ] )
				->build(),

			AAE_A_Search_Results::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Results' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-search-field', 'e-aae-a-search-results' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-panel' => __DIR__ . '/aae-a-search-panel.html.twig',
		];
	}
}
