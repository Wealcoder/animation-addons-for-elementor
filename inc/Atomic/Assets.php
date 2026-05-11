<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	/**
	 * Handle for the always-loaded core runtime (common.js). Every effect
	 * bundle declares this as a dependency so enqueueing any effect pulls
	 * the core in automatically.
	 */
	const HANDLE      = 'aae-atomic-common';
	const BUILD_DIR   = 'assets/build/modules/atomic/';

	/**
	 * Catalogue of per-effect JS bundles. Each entry maps a stable handle to
	 * the build-time entry path (relative to BUILD_DIR). Render.php calls
	 * wp_enqueue_script( $handle ) for the effects a widget actually uses.
	 */
	const EFFECT_BUNDLES = [
		'aae-effect-animation' => 'effects/animation.js',
		// 'aae-effect-tilt'      => 'effects/tilt.js',
		// 'aae-effect-pin'       => 'effects/pin.js',
		// add more as effects are ported
	];

	public function register(): void {
		// Public frontend: register only. Render.php triggers wp_enqueue_script()
		// per-widget when an animation actually applies. Editor preview keeps the
		// blanket enqueue because the user may toggle effects on/off live and the
		// runtime must already be loaded.
		add_action( 'wp_enqueue_scripts',                     [ $this, 'register_common' ], 100 );
		add_action( 'elementor/preview/enqueue_scripts',      [ $this, 'enqueue_all_in_editor' ], 100 );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_bridge' ], 100 );
	}

	/**
	 * Stable handle so other code can pass it to wp_enqueue_script() if it
	 * specifically wants only the core runtime (rare — usually you enqueue an
	 * effect bundle and the core comes along as a dependency).
	 */
	public static function common_handle(): string {
		return self::HANDLE;
	}

	/**
	 * Public-frontend path: register the core runtime AND every effect bundle.
	 * Enqueue is deferred to render time — Render.php picks the bundles a
	 * widget needs and calls wp_enqueue_script() with their handles.
	 */
	public function register_common(): void {
		$core = $this->load_asset( 'common' );
		$deps = $this->frontend_deps( $core['dependencies'] );

		wp_register_script(
			self::HANDLE,
			WCF_ADDONS_URL . self::BUILD_DIR . 'common.js',
			$deps,
			$core['version'],
			true
		);

		// Register every effect bundle with the core runtime as a dep, so
		// enqueueing an effect automatically pulls in the runtime.
		foreach ( self::EFFECT_BUNDLES as $handle => $relative ) {
			// Path uses the same .asset.php sidecar as the core runtime; the
			// webpack entry name (without .js) matches the relative path.
			$entry_key = preg_replace( '/\\.js$/', '', $relative );
			$asset     = $this->load_asset( $entry_key );

			wp_register_script(
				$handle,
				WCF_ADDONS_URL . self::BUILD_DIR . $relative,
				array_merge( [ self::HANDLE ], $asset['dependencies'] ),
				$asset['version'],
				true
			);
		}
	}

	/**
	 * Editor preview path: load the runtime AND every effect bundle blanket,
	 * since the user can toggle any effect on/off without a server round-trip.
	 */
	public function enqueue_all_in_editor(): void {
		// Re-use the public registration path so handles + deps are set up
		// identically, then upgrade each to enqueued.
		$this->register_common();

		wp_enqueue_script( self::HANDLE );
		foreach ( array_keys( self::EFFECT_BUNDLES ) as $handle ) {
			wp_enqueue_script( $handle );
		}
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
