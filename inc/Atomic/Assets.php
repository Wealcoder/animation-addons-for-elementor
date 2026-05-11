<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	const HANDLE      = 'aae-atomic-animations';
	const BUILD_DIR   = 'assets/build/modules/atomic/';

	public function register(): void {
		// Public frontend: register only. Render.php triggers wp_enqueue_script()
		// per-widget when an animation actually applies. Editor preview keeps the
		// blanket enqueue because the user may toggle effects on/off live and the
		// runtime must already be loaded.
		add_action( 'wp_enqueue_scripts',                     [ $this, 'register_frontend' ], 100 );
		add_action( 'elementor/preview/enqueue_scripts',      [ $this, 'enqueue_frontend' ],  100 );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_bridge' ], 100 );
	}

	/**
	 * Stable handle so Render.php (or any other call site that decides this
	 * widget needs the runtime) can pass it to wp_enqueue_script().
	 */
	public static function frontend_handle(): string {
		return self::HANDLE;
	}

	/** Public-frontend path: register the handle but do not enqueue. */
	public function register_frontend(): void {
		$asset = $this->load_asset( 'frontend' );
		$deps  = $this->frontend_deps( $asset['dependencies'] );

		wp_register_script(
			self::HANDLE,
			WCF_ADDONS_URL . self::BUILD_DIR . 'frontend.js',
			$deps,
			$asset['version'],
			true
		);
	}

	/** Editor preview path: always load the runtime (live editing needs it). */
	public function enqueue_frontend(): void {
		$asset = $this->load_asset( 'frontend' );
		$deps  = $this->frontend_deps( $asset['dependencies'] );

		wp_enqueue_script(
			self::HANDLE,
			WCF_ADDONS_URL . self::BUILD_DIR . 'frontend.js',
			$deps,
			$asset['version'],
			true
		);
	}

	/** Merge GSAP / ScrollTrigger into the dep list if they're registered. */
	private function frontend_deps( array $deps ): array {
		if ( wp_script_is( 'gsap', 'registered' ) ) {
			$deps[] = 'gsap';
		}
		if ( wp_script_is( 'ScrollTrigger', 'registered' ) ) {
			$deps[] = 'ScrollTrigger';
		}
		return $deps;
	}

	public function enqueue_editor_bridge(): void {
		$asset = $this->load_asset( 'editor-bridge' );

		wp_enqueue_script(
			self::HANDLE . '-editor-bridge',
			WCF_ADDONS_URL . self::BUILD_DIR . 'editor-bridge.js',
			$asset['dependencies'],
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
