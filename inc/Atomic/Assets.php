<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	const HANDLE      = 'aae-atomic-animations';
	const BUILD_DIR   = 'assets/build/modules/atomic/';

	public function register(): void {
		add_action( 'wp_enqueue_scripts',                [ $this, 'enqueue_frontend' ], 100 );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueue_frontend' ], 100 );
		// editor-bridge JS no longer needed — controls are now plain UI without aaePlayButton shim.
	}

	
	

	public function enqueue_frontend(): void {
		$asset = $this->load_asset( 'frontend' );

		$deps = $asset['dependencies'];

		if ( wp_script_is( 'gsap', 'registered' ) ) {
			$deps[] = 'gsap';
		}

		if ( wp_script_is( 'ScrollTrigger', 'registered' ) ) {
			$deps[] = 'ScrollTrigger';
		}

		wp_enqueue_script(
			self::HANDLE,
			WCF_ADDONS_URL . self::BUILD_DIR . 'frontend.js',
			$deps,
			$asset['version'],
			true
		);
	}

	private function load_asset( string $entry ): array {
		$file = WCF_ADDONS_PATH . self::BUILD_DIR . $entry . '.asset.php';

		if ( ! file_exists( $file ) ) {
			return [
				'dependencies' => [],
				'version'      => WCF_ADDONS_VERSION,
			];
		}

		$asset = require $file;

		return [
			'dependencies' => $asset['dependencies'] ?? [],
			'version'      => $asset['version']      ?? WCF_ADDONS_VERSION,
		];
	}
}
