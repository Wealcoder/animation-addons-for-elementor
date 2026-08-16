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

		// Mask — clips an element to a shape. Registered as real atomic STYLE
		// props (`mask-image` & friends on the styles schema), NOT settings
		// props, so responsive values, :hover variants and global classes come
		// from Elementor's own styles engine and the CSS compiles into the
		// element's stylesheet — no runtime JS. v3's mask is widget-only, so
		// containers gain something they never had.
		if ( $extensions->is_extension_active( 'mask' ) ) {
			( new \WCF_ADDONS\Atomic\Mask\Schema() )->register();
			( new \WCF_ADDONS\Atomic\Mask\Transformers() )->register();
		}

		// Background Video — a video layer behind e-flexbox / e-div-block /
		// e-grid. Wholly free (Schema + Controls + Render + runtime): it adds
		// what v4's atomic Background control is missing rather than an
		// animation, and it needs no GSAP, so there is nothing here for the Pro
		// split to own. Frontend reads window.AAE_INTERACTIONS_BGV[<id>].
		if ( $extensions->is_extension_active( 'background-video' ) ) {
			( new \WCF_ADDONS\Atomic\BackgroundVideo\Schema() )->register();
			( new \WCF_ADDONS\Atomic\BackgroundVideo\Controls() )->register();
			( new \WCF_ADDONS\Atomic\BackgroundVideo\Render() )->register();
		}

		// Custom CSS — NOT part of the move to Pro, so it keeps its Render here.
		// Presets are the reason: an "animated" preset (keyframes, ::before
		// layers, descendant :hover — anything atomic per-element styles cannot
		// express) bakes its CSS into THIS extension's props, so a preset that
		// uses them renders as a static element, with no error, wherever the
		// extension is missing. That is true of remote presets as much as
		// bundled ones. (It used to cite two bundled free presets by name;
		// those files are gone — see the Presets note below — but the
		// dependency is a property of the preset format, not of those files.)
		if ( $extensions->is_extension_active( 'custom-css' ) ) {
			( new \WCF_ADDONS\Atomic\CustomCss\Schema() )->register();
			( new \WCF_ADDONS\Atomic\CustomCss\Controls() )->register();
			( new \WCF_ADDONS\Atomic\CustomCss\Render() )->register();
		}

		// Presets — "Apply Preset" picker section for NATIVE atomic widgets
		// (e-heading, e-button, …). AAE's own widgets add the section
		// themselves in define_atomic_controls(); this only covers types whose
		// classes we do not own.
		//
		// CURRENTLY A NO-OP, deliberately: injection is driven by
		// Controls::ALLOWED_NATIVE_TYPES, which is empty, so no native widget
		// offers the picker. It is registered anyway because the whitelist is
		// the intended on-switch — put a type in it and the section returns.
		// The section does NOT appear just because presets exist for a type;
		// that was the old behaviour and it surfaced "Presets" on widgets
		// nobody had opted in (e-paragraph, e-image), which is why the
		// whitelist replaced it.
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

	/**
	 * Every element type the shared extensions (Regular Animation, Parallax,
	 * Tilt, Scroll To, Mouse Move, Cursor Hover, Advance Tooltip, Image Hover,
	 * Custom CSS) offer their panel section on.
	 *
	 * PARENT WIDGETS ONLY, deliberately. Composite widgets register their
	 * structural children as separate element types (`e-aae-a-slider-dot`,
	 * `e-aae-a-form-input`, `e-aae-a-timeline-year`, …) — 100+ of them, all
	 * flagged `is_internal => true` in `AtomicWidgets\Atomic`'s registry and
	 * mapped to their parent in `WIDGET_PARENT_MAP`. Listing those here would
	 * put nine extension sections on every internal part a user can select,
	 * for no gain: animating the part is what animating the parent already
	 * does, and the panel noise is proportional to the part count.
	 *
	 * `e-aae-a-icon-list-item` is the one internal child kept in the list. It
	 * predates this rule and saved pages already carry `aae_*` props on it —
	 * removing it would strip them on the next save (Props_Parser::validate()
	 * erases any prop the schema does not declare). Do not "tidy" it out.
	 *
	 * Pro-owned types (`e-aae-a-nav`, `e-aae-a-btn-pro`, `e-aae-a-lottie`, …)
	 * belong here too — atomic element types can only be REGISTERED from the
	 * free plugin, and this list is matched by type string, so it is the same
	 * seam either way.
	 *
	 * Adding a type here is not enough on its own: the matching editor-bridge
	 * list in `src/modules/atomic/editor-bridge/features.js` decides which
	 * types mirror LIVE into the canvas, and a type present here but missing
	 * there reads as "works on the frontend, never updates in the editor".
	 *
	 * @return string[]
	 */
	public static function target_element_types(): array {
		return [
			// Elementor core atomic elements.
			'e-heading',
			'e-paragraph',
			'e-button',
			'e-image',
			'e-svg',
			'e-flexbox',
			'e-div-block',
			'e-grid',

			// Content / dynamic.
			'e-aae-a-post-title',
			'e-aae-a-post-image',
			'e-aae-a-post-content',
			'e-aae-a-posts',
			'e-aae-a-post-pagination',
			'e-aae-a-loop-grid',
			'e-aae-a-loop-grid-slider',
			'e-aae-a-search-form',
			'e-aae-a-search-query',
			'e-aae-a-toc',
			'e-aae-a-site-logo',

			// Navigation.
			'e-aae-a-nav',
			'e-aae-a-menu',
			'e-aae-a-offcanvas',

			// Interactive / composite.
			'e-aae-a-accordion',
			'e-aae-a-toggle-switcher',
			'e-aae-a-slider',
			'e-aae-a-stack-cards',
			'e-aae-a-timeline',
			'e-aae-a-flip-box',
			'e-aae-a-image-compare',
			'e-aae-a-image-hotspot',
			'e-aae-a-form',

			// Media.
			'e-aae-a-video',
			'e-aae-a-video-mask',
			'e-aae-a-video-popup',
			'e-aae-a-lottie',
			'e-aae-a-draw-svg',

			// Basic.
			'e-aae-a-advanced-heading',
			'e-aae-a-btn',
			'e-aae-a-btn-pro',
			'e-aae-a-counter',
			'e-aae-a-countdown',
			'e-aae-a-progressbar',
			'e-aae-a-social-share',
			'e-aae-a-icon-list',

			// Internal child — kept for back-compat only, see the docblock.
			'e-aae-a-icon-list-item',
		];
	}
}
