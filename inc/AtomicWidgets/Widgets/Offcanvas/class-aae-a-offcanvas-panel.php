<?php
/**
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-offcanvas-close.php';

class AAE_A_Offcanvas_Panel extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-offcanvas-panel';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-offcanvas-panel';
	}

	public function get_title() {
		return esc_html__( 'Offcanvas Panel', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-inner-section';
	}

	public function get_keywords() {
		return [ 'offcanvas', 'panel', 'drawer', 'atomic' ];
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
		// No content controls — the panel is a pure drop-zone. The close button
		// is now a real, separately-styleable child element (AAE_A_Offcanvas_Close),
		// not a Close Icon setting here.
		return [];
	}

	/**
	 * Seed a real close button as the panel's first child so the drawer is
	 * closable out of the box.
	 *
	 * LOCKED, like the Trigger / Overlay / Panel on the parent. It stays fully
	 * selectable, movable and Style-tab styleable — locking only blocks deletion.
	 *
	 * This used to be unlocked, on the reasoning that Esc and overlay-click still
	 * close the drawer. They do not reliably: `close_on_esc` and
	 * `close_on_overlay` are both builder-facing Switch props on the parent, so
	 * turning both off and deleting this button leaves an open drawer with NO way
	 * to close it — a dead end for the visitor, with nothing in the editor
	 * flagging it. The escape hatch that justified leaving it deletable is itself
	 * deletable, so it could not carry that weight.
	 */
	protected function define_default_children(): array {
		return [
			AAE_A_Offcanvas_Close::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => __( 'Close', 'animation-addons-for-elementor' ) ] )
				->build(),
		];
	}

	/**
	 * Broad drop-zone: users compose the drawer from any content. `widget`
	 * covers AAE / core leaf widgets (nav menu, search, social icons…);
	 * containers let them build multi-column layouts inside the drawer.
	 */
	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-con', 'e-flexbox', 'e-div-block', 'e-grid', 'e-heading', 'e-paragraph', 'e-svg', 'e-button', 'e-image', 'e-divider','e-aae-a-nav' ];
	}
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_props( [
							// A plain block, NOT a flex column — the panel is a
							// drop-zone, so its children should stack in normal flow and
							// the builder decides any layout by dropping a Div Block /
							// Flexbox inside it.
							//
							// `block` must be stated EXPLICITLY: the Twig puts `e-con` on
							// this element and `.e-con` carries `display:flex`, so merely
							// omitting `display` here would leave it flex. Declared as a
							// base style it beats `.e-con` and still loses to the Style
							// tab, so Display stays overridable.
							//
							// `flex-direction` is gone with it — inert on a block box.
							'display'        => String_Prop_Type::generate( 'block' ),
							'overflow-y'     => String_Prop_Type::generate( 'auto' ),
							'max-width'      => Size_Prop_Type::generate( [ 'size' => 90, 'unit' => 'vw' ] ),
							'height'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => 'vh' ] ),
							// The bound that makes `overflow-y: auto` above actually DO
							// something. A top/bottom drawer is pinned by offcanvas.js's POS
							// table with `bottom:auto` (resp. `top:auto`) and an INLINE
							// `height:auto`, so it has no height bound at all — the inline
							// value also outranks the `height:100vh` on the line above. An
							// unbounded box never scrolls, so a drawer whose content grew
							// past the viewport simply kept growing and the PAGE scrolled
							// instead of the drawer (measured on a real top drawer: content
							// 2000px, panel clientHeight unbounded, page scrolled).
							//
							// 100vh, not the 90vh cap that POS used to apply inline. Two
							// things follow from that number: it is a no-op for left/right
							// drawers, which are already exactly 100vh, so this single shared
							// prop is safe for all four edges — the reason the old cap could
							// not live here; and living in a base style it stays overridable
							// from the Style tab, which the inline version never was.
							'max-height'     => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => 'vh' ] ),
							'background'     => Background_Prop_Type::generate( [
								'color' => Color_Prop_Type::generate( '#ffffff' ),
							] ),
							'padding'        => Dimensions_Prop_Type::generate( [
								'block-start'  => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
								'block-end'    => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
								'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
								'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							] ),
						] )
				),
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-offcanvas-panel' => __DIR__ . '/aae-a-offcanvas-panel.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
