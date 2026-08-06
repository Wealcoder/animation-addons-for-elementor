<?php
/**
 * AAE Offcanvas Close — atomic leaf WIDGET. Renders
 * <button class="aae-a-offcanvas-close aae-offcanvas-close" ...>.
 *
 * A REAL selectable/styleable element (not plain markup baked into the panel
 * twig) so builders get full Style-tab control — size (font-size drives the
 * 1em icon), color, background, border, hover, padding, and position — and can
 * move or delete it freely. offcanvas.js binds the close behaviour to the
 * `.aae-offcanvas-close` hook class found inside the panel (frontend only), so
 * this widget just needs to render that class; if the user deletes it, Esc +
 * overlay-click still close the drawer.
 *
 * Seeded automatically as the panel's first default child — hidden from the
 * widget panel + search (show_in_panel/hide_on_search, the classic Widget_Base
 * hooks, since this extends Atomic_Widget_Base → Widget_Base). Mirrors
 * class-aae-a-form-next.php.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Offcanvas_Close extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Closes the offcanvas drawer. Seeded inside the panel; fully styleable via the Style tab.';

	public static function get_element_type(): string {
		return 'e-aae-a-offcanvas-close';
	}

	public function get_title() {
		return esc_html__( 'Offcanvas Close', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-close';
	}

	public function get_keywords() {
		return [ 'offcanvas', 'close', 'drawer', 'icon', 'atomic' ];
	}

	// Seeded as the panel's default child — not a general-purpose draggable
	// widget. Classic Widget_Base hooks (this extends Atomic_Widget_Base →
	// Widget_Base), NOT Atomic_Element_Base's should_show_in_panel().
	public function show_in_panel() {
		return false;
	}

	public function hide_on_search() {
		return true;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Empty by default → Twig falls back to a built-in X glyph. Swap in any
			// uploaded SVG via the Icon control (same pattern as the trigger icon).
			'icon'       => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Offcanvas/assets/icons/close.svg' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'icon' )
						->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	/**
	 * Neutral, design-less defaults: a chromeless icon button sitting top-right
	 * of the panel. font-size drives the 1em icon so a single Font Size edit
	 * resizes the icon; everything else is fully overridable.
	 *
	 * The right alignment is `margin-inline-start: auto` on a BLOCK-level flex
	 * box, not `align-self: flex-end`. The panel is a plain block (see
	 * AAE_A_Offcanvas_Panel::define_base_styles) and `align-self` only does
	 * anything to a flex/grid ITEM — left as-is it would be dead CSS and this
	 * button would silently fall back to the left edge. An auto inline-start
	 * margin is the block-flow equivalent, and it needs `display:flex` rather
	 * than `inline-flex` (an inline box ignores auto margins) plus a
	 * `fit-content` width so the box hugs the icon instead of filling the row.
	 */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 'fit-content', 'unit' => 'custom' ] ) )
						->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
						->add_prop( 'color', Color_Prop_Type::generate( '#000000' ) )
						->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [
								'color' => Color_Prop_Type::generate( 'transparent' ),
							] )
						)
						->add_prop( 'border-width', $zero )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate( [
								'block-start'  => $zero,
								'block-end'    => $zero,
								'inline-start' => $zero,
								'inline-end'   => $zero,
							] )
						)
						// `inline-start: auto` is what pushes the button to the panel's
						// right edge now that the panel is a block (see the docblock).
						// It shares this one prop with the 16px block-end gap — margin
						// is a single Dimensions prop, so a second add_prop('margin')
						// would silently drop whichever came first.
						->add_prop(
							'margin',
							Dimensions_Prop_Type::generate( [
								'block-start'  => $zero,
								'block-end'    => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
								'inline-start' => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] ),
								'inline-end'   => $zero,
							] )
						)
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop( 'color', Color_Prop_Type::generate( '#666666' ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-offcanvas-close' => __DIR__ . '/aae-a-offcanvas-close.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
