<?php
namespace WCF_ADDONS\Atomic;

use WCF_ADDONS\AtomicWidgets\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	const MIN_ELEMENTOR_VERSION = '4.0.0';


	public static function get_label( $text ) {
		return $text;
	}


	public static function init(): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		if ( version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '<' ) ) {
			return;
		}

		// This can run before animation-addons-for-elementor.php's own
		// require_once for it (class-plugin.php's require, further up the
		// call stack, executes Bootstrap::init() synchronously), so load it
		// defensively here. require_once is a no-op on the later, normal load.
		if ( ! class_exists( Atomic::class ) ) {
			require_once WCF_ADDONS_PATH . 'inc/AtomicWidgets/class-atomic.php';
		}

		$extensions = Atomic::instance();

		// Schema + Controls only. The editor UI for these twelve extensions stays
		// in the free plugin — the panel sections must keep appearing and saving
		// on a free site, and the Schema must keep existing or Elementor erases
		// every aae_* prop from _elementor_data on the next save.
		//
		// Their Render classes and effect bundles live in the Pro plugin
		// (inc/AtomicV4/Extensions/). Nothing here renders them; a site without a
		// licensed Pro shows the settings and no animation, which is the point.
		// See animation-addons-for-elementor-pro/docs/atomic-v4/extension-frontend-migration.md.

		// Regular (preset-based) animation — applied to every atomic widget.
		// Frontend reads window.AAE_INTERACTIONS_ANIM[<id>].
		if ( $extensions->is_extension_active( 'regular-animation' ) ) {
			( new \WCF_ADDONS\Atomic\RegularAnimation\Schema() )->register();
			( new \WCF_ADDONS\Atomic\RegularAnimation\Controls() )->register();
		}

		// Parallax (ScrollSmoother) — applied to every atomic widget.
		// Frontend reads window.AAE_INTERACTIONS_PLX[<id>].
		if ( $extensions->is_extension_active( 'parallax' ) ) {
			( new \WCF_ADDONS\Atomic\Parallax\Schema() )->register();
			( new \WCF_ADDONS\Atomic\Parallax\Controls() )->register();
		}

		// Text animation — char/word/reveal/etc. for heading-class widgets.
		if ( $extensions->is_extension_active( 'text-animation' ) ) {
			( new \WCF_ADDONS\Atomic\TextAnimation\Schema() )->register();
			( new \WCF_ADDONS\Atomic\TextAnimation\Controls() )->register();
		}

		// Image animation — reveal/scale/stretch for e-image / e-svg.
		// Frontend reads window.AAE_INTERACTIONS_IMG[<id>].
		if ( $extensions->is_extension_active( 'image-animation' ) ) {
			( new \WCF_ADDONS\Atomic\ImageAnimation\Schema() )->register();
			( new \WCF_ADDONS\Atomic\ImageAnimation\Controls() )->register();
			( new \WCF_ADDONS\Atomic\ImageAnimation\Render() )->register();
		}

		// Image hover — cursor-following floating image overlay on any
		// atomic widget. Frontend reads window.AAE_INTERACTIONS_IH[<id>].
		if ( $extensions->is_extension_active( 'image-hover' ) ) {
			( new \WCF_ADDONS\Atomic\ImageHover\Schema() )->register();
			( new \WCF_ADDONS\Atomic\ImageHover\Controls() )->register();
		}

		// Sticky — pin elements
		if ( $extensions->is_extension_active( 'sticky' ) ) {
			( new \WCF_ADDONS\Atomic\Sticky\Schema() )->register();
			( new \WCF_ADDONS\Atomic\Sticky\Controls() )->register();
		}

		// horizontal scroll animation
		if ( $extensions->is_extension_active( 'horizontal-scroll-anim' ) ) {
			( new \WCF_ADDONS\Atomic\HorizontalScrollAnim\Schema() )->register();
			( new \WCF_ADDONS\Atomic\HorizontalScrollAnim\Controls() )->register();
		}

		// Cursor hover effect — cursor-following floating element on any
		if ( $extensions->is_extension_active( 'cursor-hover-effect' ) ) {
			( new \WCF_ADDONS\Atomic\CursorHoverEffect\Schema() )->register();
			( new \WCF_ADDONS\Atomic\CursorHoverEffect\Controls() )->register();
		}

		// Mouse move effect — element moves based on mouse position.
		if ( $extensions->is_extension_active( 'mouse-move-effect' ) ) {
			( new \WCF_ADDONS\Atomic\MouseMoveEffect\Schema() )->register();
			( new \WCF_ADDONS\Atomic\MouseMoveEffect\Controls() )->register();
		}

		// Advance Tooltip
		if ( $extensions->is_extension_active( 'advance-tooltip' ) ) {
			( new \WCF_ADDONS\Atomic\AdvanceTooltip\Schema() )->register();
			( new \WCF_ADDONS\Atomic\AdvanceTooltip\Controls() )->register();
		}

		// Tilt
		if ( $extensions->is_extension_active( 'tilt' ) ) {
			( new \WCF_ADDONS\Atomic\Tilt\Schema() )->register();
			( new \WCF_ADDONS\Atomic\Tilt\Controls() )->register();
		}

		// scrollto
		if ( $extensions->is_extension_active( 'scroll-to' ) ) {
			( new \WCF_ADDONS\Atomic\ScrollTo\Schema() )->register();
			( new \WCF_ADDONS\Atomic\ScrollTo\Controls() )->register();
		}

		// Custom CSS — NOT part of the move to Pro, so it keeps its Render here.
		// Two bundled free presets
		// (Presets/e-button/shine-pulse.json, Presets/e-flexbox/pill-button.json)
		// carry their animation in this extension's props, so taking it away
		// would leave them rendering as static elements with no error.
		if ( $extensions->is_extension_active( 'custom-css' ) ) {
			( new \WCF_ADDONS\Atomic\CustomCss\Schema() )->register();
			( new \WCF_ADDONS\Atomic\CustomCss\Controls() )->register();
			( new \WCF_ADDONS\Atomic\CustomCss\Render() )->register();
		}

		// Presets — "Apply Preset" picker section for NATIVE atomic widgets
		// (e-heading, e-button, …). Preset JSONs live one folder per element
		// type under inc/AtomicWidgets/Presets/; the section only appears for
		// types that have at least one preset bundled. AAE's own widgets add
		// the section themselves in define_atomic_controls().
		( new \WCF_ADDONS\Atomic\Presets\Controls() )->register();

		// Nested Slider. (No Controls class — the slider's panel section is built
		// directly in AAE_A_Slider::define_atomic_controls(), so there's nothing
		// to inject via the controls filter.)
		( new \WCF_ADDONS\Atomic\NestedSlider\Schema() )->register();
		( new \WCF_ADDONS\Atomic\NestedSlider\Render() )->register();

		// Loop Grid Slider — reuses the Nested Slider schema (NS_*) and the shared
		// 'ns' InteractionsMap namespace + runtime, so no separate Schema is needed.
		// This Render only publishes the config for e-aae-a-loop-grid-slider and
		// enqueues the shared slider runtime plus the load-more bridge.
		( new \WCF_ADDONS\Atomic\LoopGridSlider\Render() )->register();

		// Style Manager — registers AAE utility classes (aae-flex, aae-a-p0,
		// aae-a-svg, …) via the atomic styles pipeline.
		( new \WCF_ADDONS\Atomic\StyleManager\Manager() )->register();

		// Preset interaction styles — keyed CSS map, printed inline on demand
		// for the presets actually used on the page (see Preset_Styles).
		( new \WCF_ADDONS\Atomic\StyleManager\Preset_Styles() )->register();

		( new Assets() )->register();

		// Remote preset system — "Presets" panel section for native atomic
		// widgets, and the same-origin proxy route the editor's JS fetches
		// (merges remote + local presets; see Atomic\Presets\Cache).
		( new \WCF_ADDONS\Atomic\Presets\Controls() )->register();
		( new \WCF_ADDONS\Atomic\Presets\Rest() )->register();
	}

	public static function target_element_types(): array {
		return [
			'e-heading',
			'e-paragraph',
			'e-button',
			'e-image',
			'e-svg',
			'e-flexbox',
			'e-div-block',
			'e-grid',
			'e-aae-a-post-title',
			'e-aae-a-post-image',
			'e-aae-a-icon-list',
			'e-aae-a-icon-list-item',
		];
	}
}
