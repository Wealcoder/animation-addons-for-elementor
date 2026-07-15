<?php
/**
 * AAE Search Results — atomic leaf: the live Ajax results container.
 *
 * Server-renders an empty, absolutely-positioned box; the runtime JS fills it with
 * the markup returned by the shared `live_search` admin-ajax endpoint (the same
 * `.search-item` / `.search-no-result` HTML the v3 widget used) and shows it while
 * typing. Hidden by default (display:none in base styles) — the JS flips it on. No
 * CSS file ships.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Results extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-search-results';
	}

	public function get_title() {
		return esc_html__( 'Search Results', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-list';
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
					->add_prop( 'display', String_Prop_Type::generate( 'none' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
					->add_prop( 'position', String_Prop_Type::generate( 'absolute' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
					->add_prop( 'inset-block-start', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
					->add_prop( 'inset-inline-start', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
					->add_prop( 'z-index', Number_Prop_Type::generate( 99 ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-results' => __DIR__ . '/aae-a-search-results.html.twig',
		];
	}
}
