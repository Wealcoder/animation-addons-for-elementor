<?php
namespace WCF_ADDONS\Atomic;

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
		
		// Regular (preset-based) animation — applied to every atomic widget.
		// Frontend reads window.AAE_INTERACTIONS_ANIM[<id>].
		( new \WCF_ADDONS\Atomic\RegularAnimation\Schema() )->register();
		( new \WCF_ADDONS\Atomic\RegularAnimation\Controls() )->register();
		( new \WCF_ADDONS\Atomic\RegularAnimation\Render() )->register();

		// Parallax (ScrollSmoother) — applied to every atomic widget.
		// Frontend reads window.AAE_INTERACTIONS_PLX[<id>].
		( new \WCF_ADDONS\Atomic\Parallax\Schema() )->register();
		( new \WCF_ADDONS\Atomic\Parallax\Controls() )->register();
		( new \WCF_ADDONS\Atomic\Parallax\Render() )->register();

		// Text animation — char/word/reveal/etc. for heading-class widgets.
		( new \WCF_ADDONS\Atomic\TextAnimation\Schema() )->register();
		( new \WCF_ADDONS\Atomic\TextAnimation\Controls() )->register();
		( new \WCF_ADDONS\Atomic\TextAnimation\Render() )->register();

		// Image animation — reveal/scale/stretch for e-image / e-svg.
		// Frontend reads window.AAE_INTERACTIONS_IMG[<id>].
		( new \WCF_ADDONS\Atomic\ImageAnimation\Schema() )->register();
		( new \WCF_ADDONS\Atomic\ImageAnimation\Controls() )->register();
		( new \WCF_ADDONS\Atomic\ImageAnimation\Render() )->register();

		// Image hover — cursor-following floating image overlay on any
		// atomic widget. Frontend reads window.AAE_INTERACTIONS_IH[<id>].
		( new \WCF_ADDONS\Atomic\ImageHover\Schema() )->register();
		( new \WCF_ADDONS\Atomic\ImageHover\Controls() )->register();
		( new \WCF_ADDONS\Atomic\ImageHover\Render() )->register();


		// Sticky — pin elements
		( new \WCF_ADDONS\Atomic\Sticky\Schema() )->register();
		( new \WCF_ADDONS\Atomic\Sticky\Controls() )->register();
		( new \WCF_ADDONS\Atomic\Sticky\Render() )->register();

		// horizontal scroll animation
		( new \WCF_ADDONS\Atomic\HorizontalScrollAnim\Schema() )->register();
		( new \WCF_ADDONS\Atomic\HorizontalScrollAnim\Controls() )->register();
		( new \WCF_ADDONS\Atomic\HorizontalScrollAnim\Render() )->register();

		// Cursor hover effect — cursor-following floating element on any
		( new \WCF_ADDONS\Atomic\CursorHoverEffect\Schema() )->register();
		( new \WCF_ADDONS\Atomic\CursorHoverEffect\Controls() )->register();
		( new \WCF_ADDONS\Atomic\CursorHoverEffect\Render() )->register();


		// Mouse move effect — element moves based on mouse position.
		( new \WCF_ADDONS\Atomic\MouseMoveEffect\Schema() )->register();
		( new \WCF_ADDONS\Atomic\MouseMoveEffect\Controls() )->register();
		( new \WCF_ADDONS\Atomic\MouseMoveEffect\Render() )->register();


		// Advance Tooltip
		( new \WCF_ADDONS\Atomic\AdvanceTooltip\Schema() )->register();
		( new \WCF_ADDONS\Atomic\AdvanceTooltip\Controls() )->register();
		( new \WCF_ADDONS\Atomic\AdvanceTooltip\Render() )->register();


		// Tilt
		( new \WCF_ADDONS\Atomic\Tilt\Schema() )->register();
		( new \WCF_ADDONS\Atomic\Tilt\Controls() )->register();
		( new \WCF_ADDONS\Atomic\Tilt\Render() )->register();

		// scrollto 
		( new \WCF_ADDONS\Atomic\ScrollTo\Schema() )->register();
		( new \WCF_ADDONS\Atomic\ScrollTo\Controls() )->register();
		( new \WCF_ADDONS\Atomic\ScrollTo\Render() )->register();

		// Custom CSS
		( new \WCF_ADDONS\Atomic\CustomCss\Schema() )->register();
		( new \WCF_ADDONS\Atomic\CustomCss\Controls() )->register();
		( new \WCF_ADDONS\Atomic\CustomCss\Render() )->register();

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
