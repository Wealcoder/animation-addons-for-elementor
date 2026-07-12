<?php
/**
 * AAE Offcanvas Panel — atomic NESTED element (locked child of AAE_A_Offcanvas).
 *
 * This is the sliding drawer surface AND the drop-zone: users add their own
 * atomic widgets here (nav menu, image, icon list, social icons, search, …).
 * That's the v4 replacement for the v3 drawer's hard-coded content repeaters.
 *
 * The panel OWNS the drawer's visual defaults through define_base_styles()
 * (width / height / background / padding / column layout), so every one of
 * them is overridable from the Style panel. The base-style classes are global,
 * so they survive the runtime teleport to <body> (unlike descendant-scoped
 * CSS). The only inline rule in its Twig is a structural reset for the plain
 * close button — the close button is not an atomic element, so it can't carry
 * a base style.
 *
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
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

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
		return false; // Only added via the parent Offcanvas — never dragged alone.
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'close_icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Offcanvas/assets/icons/close.svg' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'panel' )
				->set_label( __( 'Panel', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'close_icon' )
						->set_label( __( 'Close Icon', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	/**
	 * Broad drop-zone: users compose the drawer from any content. `widget`
	 * covers AAE / core leaf widgets (nav menu, search, social icons…);
	 * containers let them build multi-column layouts inside the drawer.
	 */
	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-con', 'e-flexbox', 'e-div-block', 'e-grid', 'e-heading', 'e-paragraph', 'e-svg', 'e-button', 'e-image', 'e-divider' ];
	}

	/**
	 * The drawer's full default look. Every declaration here is a Style-panel
	 * default the user can override. Height is 100vh (a full-height side drawer);
	 * the JS overrides height/width inline only for the top/bottom positions
	 * (a horizontal drawer is content-height, full-width). See offcanvas.js.
	 *
	 * NOTE (from CLAUDE.md): one invalid key fails the WHOLE definition. width /
	 * height / max-width are Size_Prop_Type; display / flex-direction /
	 * overflow-y are plain strings.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_props( [
							'display'        => String_Prop_Type::generate( 'flex' ),
							'flex-direction' => String_Prop_Type::generate( 'column' ),
							'overflow-y'     => String_Prop_Type::generate( 'auto' ),
							'max-width'      => Size_Prop_Type::generate( [ 'size' => 90, 'unit' => 'vw' ] ),
							'height'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => 'vh' ] ),
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
