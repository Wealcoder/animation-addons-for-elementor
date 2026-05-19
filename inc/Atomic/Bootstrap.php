<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	const MIN_ELEMENTOR_VERSION = '4.0.0';

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

	

		( new Assets() )->register();
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
		];
	}
}
