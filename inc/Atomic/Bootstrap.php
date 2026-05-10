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

		( new Schema() )->register();
		( new Controls() )->register();
		( new Render() )->register();
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
