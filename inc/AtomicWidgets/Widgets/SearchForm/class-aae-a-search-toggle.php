<?php
/**
 * AAE Search Toggle — atomic container.
 *
 * The open/close trigger used in dropdown / fullscreen mode. It is the click target
 * and the focus ring owner; the two glyphs are REAL child elements
 * (e-aae-a-search-toggle-open / -close) rather than markup baked into this Twig, so
 * each can be selected in the Structure panel and styled on its own — pick a
 * different SVG, size or colour for the close icon without touching the open one.
 *
 *   e-aae-a-search-toggle
 *     ├─ e-aae-a-search-toggle-open    magnifier — shown while the panel is closed
 *     └─ e-aae-a-search-toggle-close   ✕ — shown while the panel is open
 *
 * The runtime JS swaps them by writing inline `display` on the two hook classes, so
 * this element only has to guarantee the resting state: the Twig hides the close
 * glyph on the frontend and reveals it in the editor. Hidden by the JS entirely in
 * inline mode, where an always-visible panel makes a trigger redundant.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;

require_once __DIR__ . '/class-aae-a-search-toggle-open.php';
require_once __DIR__ . '/class-aae-a-search-toggle-close.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle_Open;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle_Close;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Search_Toggle extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-search-toggle';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-toggle';
	}

	public function get_title() {
		return esc_html__( 'Search Toggle', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-search';
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

	/**
	 * `color` stays here rather than on the icons: both glyphs use currentColor, so
	 * one value recolours the pair — and a colour set on an individual icon still
	 * overrides it.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#1a1a1a' ) )
			)
			// It's a tabindex=0 div, so it takes the UA focus ring too — drop it.
			->add_variant(
				Style_Variant::make()
					->set_state( Style_States::FOCUS )
					->add_prop( 'outline-style', String_Prop_Type::generate( 'none' ) )
			),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Search_Toggle_Open::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Open Icon' ] )
				->build(),

			AAE_A_Search_Toggle_Close::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Close Icon' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-search-toggle-open', 'e-aae-a-search-toggle-close' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-toggle' => __DIR__ . '/aae-a-search-toggle.html.twig',
		];
	}
}
