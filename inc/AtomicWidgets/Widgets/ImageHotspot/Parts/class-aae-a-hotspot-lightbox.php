<?php
/**
 * AAE Hotspot Lightbox — atomic leaf widget.
 *
 * The full-viewport dark backdrop AND centering frame for a lightbox-mode
 * Point's Content, merged into one element (was two — a separate Scrim +
 * Lightbox Frame — until a user pointed out two Navigator items for what's
 * conceptually a single "the lightbox" concept was confusing). Being
 * `display: flex; align-items: center; justify-content: center` over a
 * full-viewport box means it darkens the background AND centers Content as
 * a flex child in one move — no more manual `top:50%;left:50%;
 * transform:translate(-50%,-50%)` math, which is a genuine simplification,
 * not just a merge for its own sake.
 *
 * Seeded as the Hotspot Point's third default child (alongside Marker,
 * Content); only relevant in lightbox mode. Content is NEVER a real
 * Elementor child of this element — it stays Point's own direct child in
 * the saved element tree, exactly as in tooltip mode, specifically so
 * switching a Point's "On Click/Hover" setting from Tooltip to Lightbox
 * later doesn't strand a builder's already-customized Content on some other
 * element. image-hotspot.js's initLightboxes() instead MOVES Content's live
 * DOM node inside this element at runtime, only while teleporting into the
 * shared portal.
 *
 * The show/hide toggle (opacity/visibility/pointer-events, driven by the
 * JS-added `.active` class) stays plain CSS in image-hotspot.scss — no
 * Style schema key for `visibility`, and no Style_States hook for an
 * arbitrary JS-toggled class. The pop-in scale animation (0.92 → 1, also
 * `.active`-driven) lives on a nested `.aae-hotspot-lightbox
 * .aae-hotspot-content` rule instead of on this element itself, since
 * flexbox already handles centering and the scale is purely decorative on
 * Content.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Hotspot_Lightbox extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'The dark backdrop + centering frame for a hotspot lightbox. Seeded automatically; fully styleable via the Style tab.';

	public static function get_element_type(): string {
		return 'e-aae-a-hotspot-lightbox';
	}

	public function get_title() {
		return esc_html__( 'Hotspot Lightbox', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-lightbox-expand';
	}

	public function get_keywords() {
		return [ 'hotspot', 'lightbox', 'backdrop', 'modal', 'overlay', 'atomic' ];
	}

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
		];
	}

	protected function define_atomic_controls(): array {
		return [
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
	 * Full-viewport, dark, and a flex-centering container in one — no single
	 * `inset` shorthand key exists in Elementor's atomic style schema
	 * (confirmed by reading get_position_props() in style-schema.php), so all
	 * four logical inset properties are set individually.
	 */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position', String_Prop_Type::generate( 'fixed' ) )
						->add_prop( 'inset-block-start', $zero )
						->add_prop( 'inset-inline-end', $zero )
						->add_prop( 'inset-block-end', $zero )
						->add_prop( 'inset-inline-start', $zero )
						->add_prop( 'z-index', Number_Prop_Type::generate( 99999 ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.6)' ) ] )
						)
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-hotspot-lightbox' => __DIR__ . '/aae-a-hotspot-lightbox.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
