<?php
/**
 * AAE Search Toggle Open Icon — atomic leaf.
 *
 * The "open" glyph (magnifier) the Toggle shows while the panel is closed. Split out
 * of the Toggle so it is its own selectable / styleable element in the editor tree,
 * independent from the Close Icon — pick it in the Structure panel and the Style tab
 * applies to this glyph alone.
 *
 * Upload an SVG to replace the built-in glyph. Size comes from Font Size (the fallback
 * glyph is sized 1em); colour is inherited from the Toggle unless set here, so styling
 * the Toggle still recolours both icons by default.
 *
 * The runtime JS finds it by the `aae-a-search-toggle__open` hook class, which this
 * element's own Twig renders — never seed a functional hook class into the `classes`
 * prop, the panel reports it as missing and its ✕ unapplies it.
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

class AAE_A_Search_Toggle_Open extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-search-toggle-open';
	}

	public function get_title() {
		return esc_html__( 'Open Icon', 'animation-addons-for-elementor' );
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
			// Empty by default → the Twig falls back to the built-in inline
			// magnifier glyph, which honours currentColor and Font Size.
			'icon'       => Svg_Src_Prop_Type::make(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_toggle_open_icon' )
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'icon' )
						->set_label( __( 'Open Icon', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	/**
	 * Size is driven by Font Size (the glyph is 1em) so a single Style-tab control
	 * resizes it; line-height 0 keeps the box tight around the glyph. Colour is
	 * deliberately NOT set — it inherits the Toggle's, and setting it here overrides.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ) )
					->add_prop( 'line-height', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-toggle-open' => __DIR__ . '/aae-a-search-toggle-open.html.twig',
		];
	}
}
