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
		( new \WCF_ADDONS\Atomic\RegularAnimation\Schema() )->register();
		( new \WCF_ADDONS\Atomic\RegularAnimation\Controls() )->register();
		( new \WCF_ADDONS\Atomic\RegularAnimation\Render() )->register();

		// Text animation — char/word/reveal/etc. for heading-class widgets.
		( new \WCF_ADDONS\Atomic\TextAnimation\Schema() )->register();
		( new \WCF_ADDONS\Atomic\TextAnimation\Controls() )->register();
		( new \WCF_ADDONS\Atomic\TextAnimation\Render() )->register();

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
