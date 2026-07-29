<?php
/**
 * AAE Search Toggle — atomic leaf.
 *
 * The open/close trigger used in dropdown / fullscreen mode. Renders an inline
 * search glyph (open) and an inline close glyph (close) — the runtime JS shows the
 * right one and toggles the panel. Hidden in inline mode by the JS. Icons are inline
 * SVG (no e-svg seeding, which Elementor strips from default children); their size
 * + colour live in define_base_styles() so they're a reliable, overridable default.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Toggle extends Atomic_Widget_Base {
	use Has_Template;

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

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			// Uploadable icons. Empty by default → the Twig falls back to the
			// built-in inline search / close glyphs (which honour currentColor).
			'open_icon'  => Svg_Src_Prop_Type::make(),
			'close_icon' => Svg_Src_Prop_Type::make(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_toggle_icons' )
				->set_label( __( 'Icons', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'open_icon' )
						->set_label( __( 'Open Icon', 'animation-addons-for-elementor' ) ),

					Svg_Control::bind_to( 'close_icon' )
						->set_label( __( 'Close Icon', 'animation-addons-for-elementor' ) ),
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
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 44, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#1a1a1a' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-toggle' => __DIR__ . '/aae-a-search-toggle.html.twig',
		];
	}
}
