<?php
/**
 * AAE Hotspot Content — atomic element (container).
 *
 * The tooltip/lightbox BOX for a Hotspot Point — a real, styleable container
 * (background, color, padding, width, border-radius, position, z-index all
 * live in define_base_styles() below, editable via the Style tab) instead of
 * a plain hardcoded <div> in image-hotspot.scss.
 *
 * Unrestricted children (mirrors AAE_A_Flip_Box) — this is exactly where a
 * builder drops the tooltip/lightbox body, including another
 * e-aae-a-image-hotspot for drill-down hotspots.
 *
 * Deliberately has NO prop of its own describing whether it's acting as an
 * inline tooltip or a teleported lightbox — that's the PARENT Hotspot Point's
 * `tooltip_type` prop, which a child element's own twig can't read directly.
 * Instead, Point publishes it via Render_Context (define_render_context() in
 * class-aae-a-hotspot-point.php, same mechanism AAE_A_Post_Pagination uses
 * for its Prev/Next), and THIS class's own build_template_context() reads
 * that context and renders it as Content's OWN `data-aae-hotspot-mode`
 * attribute in aae-a-hotspot-content.html.twig — server-side, at first
 * paint, no JS dependency (still needed for the TOOLTIP-mode visibility rule
 * in image-hotspot.scss; see that rule's own comment).
 *
 * The base style below is always the TOOLTIP geometry (position: absolute,
 * its own inset offsets/translateX centering/margin) — Content never gets a
 * lightbox-specific position of its own anymore. Lightbox display instead
 * moves Content's live DOM node INSIDE a dedicated `AAE_A_Hotspot_Lightbox`
 * frame at runtime (image-hotspot.js's initLightboxes()), which owns the
 * fixed/centered positioning on ITS OWN base style; a small nesting-scoped
 * CSS reset in image-hotspot.scss (`.aae-hotspot-lightbox .aae-hotspot-content`)
 * neutralizes Content's tooltip positioning while it's nested there. This
 * replaced an earlier design where Content read its OWN `data-aae-hotspot-
 * mode="lightbox"` attribute and got a higher-specificity CSS override —
 * moved to a separate frame element specifically so lightbox mode's fixed/
 * centered "chrome" could also become Style-tab editable, without forking
 * Content into two widget types (which would've stranded a builder's
 * customized content if they later switched a Point from Tooltip to
 * Lightbox mode).
 *
 * Actual VISIBILITY (opacity/visibility/pointer-events, toggled by the
 * `.active` class on open/close) can't move to base style at all — no
 * Style_Variant/Style_States mechanism expresses "look when a runtime-
 * toggled custom class is present", same reasoning as ToggleSwitcher's
 * `.show`/`.active` staying in toggle-switcher.scss.
 *
 * The lightbox's decorative "box chrome" (box-shadow, max-width, max-height)
 * lives here, UNCONDITIONALLY — moved off the mode-gated CSS rule per
 * explicit user request, so it's Style-tab editable. This means it now also
 * applies in tooltip mode (a small shadow + size cap that wasn't there
 * before) — Style_Variant has no way to apply a prop only when an ancestor's
 * setting has a given value, so "chrome only in lightbox mode" was only
 * possible as plain CSS.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Box_Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Functions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Move_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/Parts/class-aae-a-hotspot-close.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Close;

// NOT require_once'd — class-aae-a-hotspot-point.php already requires THIS
// file, so requiring it back would be circular. `::class` below only needs
// the name resolved at compile time, not the class actually loaded, so a
// plain `use` for the alias is enough.
use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Point;

class AAE_A_Hotspot_Content extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-hotspot-content';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-hotspot-content';
	}

	public function get_title() {
		return esc_html__( 'Hotspot Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}

	public function get_keywords() {
		return [ 'hotspot', 'tooltip', 'lightbox', 'content', 'atomic' ];
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
	 * The shared, resting DECORATIVE look — used whether this box ends up an
	 * inline tooltip or a teleported lightbox. See the class docblock for the
	 * exact position/z-index override mechanics and why opacity/visibility
	 * can't move here at all.
	 *
	 * Placement is a FIXED default (bottom of the marker, centered,
	 * pointing-up arrow) rather than a builder-facing enum — there used to be
	 * `tooltip_position`/`tooltip_gap`/`tooltip_align` props on the PARENT
	 * Image Hotspot driving 4 direction variants in image-hotspot.scss.
	 * Removed 2026-07 by design: a builder who wants the tooltip somewhere
	 * else selects THIS element in the Navigator and edits the SAME fields
	 * below from Elementor's own native Style tab (Layout section for
	 * inset/margin, Transform section for the centering) — the whole point
	 * of putting this in define_base_styles() rather than plain CSS is that
	 * a base-style value is exactly what the Style tab shows as the current/
	 * default value AND lets a builder override.
	 *
	 * The offset/centering first shipped as `top`/`left`/`margin-top`/a
	 * string `transform` — all silently ignored, because none of those are
	 * real keys/shapes in Elementor's atomic style schema (confirmed by
	 * reading `modules/atomic-widgets/styles/style-schema.php` directly).
	 * The REAL equivalents used below:
	 *  - `top` → `inset-block-start` (Size_Prop_Type; the `calc()` string
	 *    rides the 'custom' unit — same trick this plugin already uses
	 *    successfully for `max-width` in
	 *    AAE_A_Post_Pagination_Preview::define_base_styles()).
	 *  - `left` → `inset-inline-start` (Size_Prop_Type, %).
	 *  - `margin-top` → the single `margin` prop (Dimensions_Prop_Type, only
	 *    block-start non-zero) — margin has no per-side key, same as padding
	 *    right below it.
	 *  - `transform: translateX(-50%)` → `Transform_Prop_Type`, whose shape
	 *    is `transform-functions` (an array of Move/Scale/Rotate/Skew
	 *    shapes) → one `Transform_Move_Prop_Type` entry with only `x` set
	 *    (y/z default to 0px, matching a plain translateX). Verified against
	 *    Elementor core's actual prop-type classes
	 *    (`prop-types/transform/*`), since this codebase had zero existing
	 *    usage of Transform_Prop_Type to copy from.
	 */
	protected function define_base_styles(): array {
		$pad = Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] );

		// Shared by the box and the arrow — both are only ever centered
		// horizontally (translateX(-50%); y/z stay at Transform_Move's own
		// 0px default).
		$center_x = Transform_Prop_Type::generate( [
			'transform-functions' => Transform_Functions_Prop_Type::generate( [
				Transform_Move_Prop_Type::generate( [
					'x' => Size_Prop_Type::generate( [ 'size' => -50, 'unit' => '%' ] ),
				] ),
			] ),
		] );

		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position', String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'z-index', Number_Prop_Type::generate( 20 ) )
						->add_prop( 'inset-block-start', Size_Prop_Type::generate( [ 'size' => 'calc(100% + 15px)', 'unit' => 'custom' ] ) )
						->add_prop( 'inset-inline-start', Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ) )
						->add_prop( 'transform', $center_x )
						->add_prop(
							'margin',
							Dimensions_Prop_Type::generate( [
								'block-start'  => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
								'block-end'    => $zero,
								'inline-start' => $zero,
								'inline-end'   => $zero,
							] )
						)
						->add_prop( 'text-align', String_Prop_Type::generate( 'center' ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#e8e8e8' ) ] )
						)
						->add_prop( 'color', Color_Prop_Type::generate( '#222222' ) )
						->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 280, 'unit' => 'px' ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate( [
								'block-start'  => $pad,
								'block-end'    => $pad,
								'inline-start' => $pad,
								'inline-end'   => $pad,
							] )
						)
						// Lightbox "box chrome" — used to be lightbox-only plain CSS
						// (image-hotspot.scss). Now unconditional here so it's
						// Style-tab editable, per explicit user request — the
						// trade-off (also visible in tooltip mode now) is called
						// out in this method's docblock. The actual fixed
						// positioning/centering transform for lightbox mode still
						// can't move here (see docblock) and stays mode-gated CSS.
						->add_prop( 'box-shadow', Box_Shadow_Prop_Type::generate( [
							Shadow_Prop_Type::generate( [
								'hOffset' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								'vOffset' => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
								'blur'    => Size_Prop_Type::generate( [ 'size' => 60, 'unit' => 'px' ] ),
								'spread'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								'color'   => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.35)' ),
							] ),
						] ) )
						->add_prop( 'max-width', Size_Prop_Type::generate( [ 'size' => '90vw', 'unit' => 'custom' ] ) )
						->add_prop( 'max-height', Size_Prop_Type::generate( [ 'size' => '85vh', 'unit' => 'custom' ] ) )
						->add_prop( 'overflow', String_Prop_Type::generate( 'auto' ) )
				),

			'base::after' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'content', String_Prop_Type::generate( '""' ) )
						->add_prop( 'position', String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ) )
						->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 15, 'unit' => 'px' ] ) )
						->add_prop( 'clip-path', String_Prop_Type::generate( 'polygon(50% 0, 0 100%, 100% 100%)' ) )
						->add_prop( 'inset-block-start', Size_Prop_Type::generate( [ 'size' => -14, 'unit' => 'px' ] ) )
						->add_prop( 'inset-inline-start', Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ) )
						->add_prop( 'transform', $center_x )
				),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Hotspot_Close::generate()
				->editor_settings( [ 'title' => 'Close' ] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Tooltip Content' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( __( 'Tooltip content', 'animation-addons-for-elementor' ) ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-hotspot-content' => __DIR__ . '/aae-a-hotspot-content.html.twig',
		];
	}

	/**
	 * Reads the ancestor Point's `tooltip_type` off the Render_Context stack
	 * (pushed by AAE_A_Hotspot_Point::define_render_context()) and exposes it
	 * to aae-a-hotspot-content.html.twig as `aae_hotspot_mode`, rendered
	 * there as this element's OWN `data-aae-hotspot-mode` attribute. See the
	 * class docblock for why this needs to be server-rendered rather than
	 * left to image-hotspot.js.
	 */
	protected function build_template_context(): array {
		$ctx = Render_Context::get( AAE_A_Hotspot_Point::class );

		return array_merge( $this->build_base_template_context(), [
			'aae_hotspot_mode' => isset( $ctx['tooltip_type'] ) ? $ctx['tooltip_type'] : 'tooltip',
		] );
	}

	public function get_style_depends(): array {
		return [];
	}
}
