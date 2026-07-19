<?php
/**
 * AAE Offcanvas Trigger — atomic leaf (the open button/icon).
 *
 * Split out from the parent's plain markup so it's a real, click-selectable,
 * independently-styleable atomic element (click it on canvas → it selects → Style
 * tab). Renders an uploaded SVG (Svg_Control) or falls back to a built-in inline
 * hamburger glyph (currentColor, so the Color style recolours it). Default size /
 * colour live in define_base_styles() (base styles survive as reliable defaults;
 * seeded e-svg children get stripped, which is why this is a custom element).
 *
 * The frontend JS binds the drawer-open behaviour to `.aae-offcanvas-trigger`,
 * which this element keeps as a hook class.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
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

class AAE_A_Offcanvas_Trigger extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-offcanvas-trigger';
	}

	public function get_title() {
		return esc_html__( 'Offcanvas Trigger', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-menu-bar';
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
			// Empty by default → Twig falls back to the inline hamburger glyph.
			'icon'       => Svg_Src_Prop_Type::make(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_offcanvas_trigger' )
				->set_label( __( 'Trigger Icon', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'icon' )
						->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
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
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#1a1a1a' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-offcanvas-trigger' => __DIR__ . '/aae-a-offcanvas-trigger.html.twig',
		];
	}
}
