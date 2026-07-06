<?php

namespace WCF_ADDONS\Atomic;

if (! defined('ABSPATH')) {
	exit;
}

final class Assets
{

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
		'aae-effect-animation'       => ['file' => 'effects/animation.js', 'deps' => ['SplitText']],
		'aae-effect-image-animation' => 'effects/image-animation.js',
		'aae-effect-image-hover'     => 'effects/image-hover.js',
		'aae-effect-tilt'            => 'effects/tilt.js',
		'aae-effect-sticky'          => 'effects/sticky.js',
		'aae-effect-horizontal'      => 'effects/horizontal.js',
		'aae-effect-mouse-move'      => 'effects/mouse-move-effect.js',
		'aae-effect-cursor-hover'    => 'effects/cursor-hover-effect.js',
		'aae-effect-advance-tooltip' => 'effects/advance-tooltip.js',
		'aae-effect-scroll-to'       => ['file' => 'effects/scroll-to.js', 'deps' => ['ScrollToPlugin']],
		'aae-effect-parallax'        => 'effects/parallax.js',
		'aae-effect-custom-css'      => 'effects/custom-css.js',
		'aae-effect-nested-slider'   => 'effects/nested-slider.js',
	];

	public function register(): void
	{
		// Public frontend: register only. Render.php triggers wp_enqueue_script()
		// per-widget when an animation actually applies. Editor preview keeps the
		// blanket enqueue because the user may toggle effects on/off live and the
		// runtime must already be loaded.
		add_action('wp_enqueue_scripts',                     [$this, 'register_common'], 100);
		add_action('elementor/preview/enqueue_scripts',      [$this, 'enqueue_all_in_editor'], 100);
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_bridge'], 100);
	}

	/**
	 * Stable handle so other code can pass it to wp_enqueue_script() if it
	 * specifically wants only the core runtime (rare — usually you enqueue an
	 * effect bundle and the core comes along as a dependency).
	 */
	public static function common_handle(): string
	{
		return self::HANDLE;
	}

	/**
	 * Public-frontend path: register the core runtime AND every effect bundle.
	 * Enqueue is deferred to render time — Render.php picks the bundles a
	 * widget needs and calls wp_enqueue_script() with their handles.
	 */
	public function register_common(): void
	{
		$core = $this->load_asset('common');

		$deps = $this->frontend_deps($core['dependencies']);

		wp_register_script(
			self::HANDLE,
			WCF_ADDONS_URL . self::BUILD_DIR . 'common.js',
			$deps,
			$core['version'],
			true
		);

		if (
			class_exists('\Elementor\Plugin')
			&& isset(\Elementor\Plugin::$instance->breakpoints)
			&& method_exists(\Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints')
		) {

			$breakpoints = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
			$config = [];
			foreach ($breakpoints as $key => $breakpoint) {
				if (is_object($breakpoint) && method_exists($breakpoint, 'get_value')) {
					$config[$key] = $breakpoint->get_value();
				}
			}
			wp_localize_script(
				self::HANDLE,
				'AAE_CONFIG',
				[
					'breakpoints' => $config,
					'tooltip_css_url' => WCF_ADDONS_URL . 'assets/build/modules/atomic/effects/advance-tooltip.css',
				]
			);
		}

		// Register every effect bundle with the core runtime as a dep, so
		// enqueueing an effect automatically pulls in the runtime.
		foreach (self::EFFECT_BUNDLES as $handle => $config) {
			$relative = is_array($config) ? $config['file'] : $config;
			$manual_deps = is_array($config) && isset($config['deps']) ? $config['deps'] : [];

			// Path uses the same .asset.php sidecar as the core runtime; the
			// webpack entry name (without .js) matches the relative path.
			$entry_key = preg_replace('/\\.js$/', '', $relative);
			$asset     = $this->load_asset($entry_key, $manual_deps);		
			wp_register_script(
				$handle,
				WCF_ADDONS_URL . self::BUILD_DIR . $relative,
				array_merge([self::HANDLE], $asset['dependencies']),
				$asset['version'],
				true
			);
		}
	}

	/**
	 * Editor preview path: load the runtime AND every effect bundle blanket,
	 * since the user can toggle any effect on/off without a server round-trip.
	 */
	public function enqueue_all_in_editor(): void
	{
		// Re-use the public registration path so handles + deps are set up
		// identically, then upgrade each to enqueued.
		$this->register_common();

		wp_enqueue_script(self::HANDLE);
		foreach (array_keys(self::EFFECT_BUNDLES) as $handle) {
			wp_enqueue_script($handle);
		}
	}

	/**
	 * Merge GSAP / ScrollTrigger into the dep list. Falls back to the Pro
	 * plugin's bundled copies when nobody else has registered them — Pro
	 * gates its own registration behind a dashboard setting, so on a plain
	 * install our atomic widgets would otherwise tween-less.
	 */
	private function frontend_deps(array $deps): array
	{
		$this->ensure_gsap_registered();
		if (wp_script_is('gsap', 'registered')) {
			$deps[] = 'gsap';
		}
		if (wp_script_is('ScrollTrigger', 'registered')) {
			$deps[] = 'ScrollTrigger';
		}
		if (wp_script_is('SplitText', 'registered')) {
			$deps[] = 'SplitText';
		}
		if (wp_script_is('ScrollToPlugin', 'registered')) {
			$deps[] = 'ScrollToPlugin';
		}
		return $deps;
	}

	/**
	 * Register gsap / ScrollTrigger from the Pro plugin's lib folder if no
	 * one else has registered them yet. No-op when already registered, or
	 * when Pro isn't installed (no fallback source).
	 */
	private function ensure_gsap_registered(): void
	{
		if (! defined('WCF_ADDONS_PRO_URL')) {
			return;
		}
		if (! wp_script_is('gsap', 'registered')) {
			wp_register_script(
				'gsap',
				WCF_ADDONS_PRO_URL . 'assets/lib/gsap.min.js',
				[],
				defined('WCF_ADDONS_PRO_VERSION') ? WCF_ADDONS_PRO_VERSION : WCF_ADDONS_VERSION,
				true
			);
		}
		if (! wp_script_is('ScrollTrigger', 'registered')) {
			wp_register_script(
				'ScrollTrigger',
				WCF_ADDONS_PRO_URL . 'assets/lib/ScrollTrigger.min.js',
				['gsap'],
				defined('WCF_ADDONS_PRO_VERSION') ? WCF_ADDONS_PRO_VERSION : WCF_ADDONS_VERSION,
				true
			);
		}
		if (! wp_script_is('SplitText', 'registered')) {
			wp_register_script(
				'SplitText',
				WCF_ADDONS_PRO_URL . 'assets/lib/SplitText.min.js',
				['gsap'],
				defined('WCF_ADDONS_PRO_VERSION') ? WCF_ADDONS_PRO_VERSION : WCF_ADDONS_VERSION,
				true
			);
		}
		if (! wp_script_is('ScrollToPlugin', 'registered')) {
			wp_register_script(
				'ScrollToPlugin',
				WCF_ADDONS_PRO_URL . 'assets/lib/ScrollToPlugin.min.js',
				['gsap'],
				defined('WCF_ADDONS_PRO_VERSION') ? WCF_ADDONS_PRO_VERSION : WCF_ADDONS_VERSION,
				true
			);
		}
	}

	/**
	 * Script handles that the editor-bridge needs but @wordpress/scripts'
	 * dependency-extraction-webpack-plugin cannot auto-detect (it only knows
	 * about @wordpress/* packages, not @elementor/*). Listed here manually so
	 * Elementor's editor packages are loaded before our bundle runs and the
	 * `window.elementorV2.editorControls` global is available for the webpack
	 * externals mapping to resolve at runtime.
	 */
	const EDITOR_BRIDGE_ELEMENTOR_DEPS = [
		'elementor-v2-editor-controls',
		'elementor-v2-editor-editing-panel',
		'elementor-v2-editor-elements',
		'elementor-v2-editor-props',
		'elementor-v2-editor-responsive',
		'elementor-v2-editor-ui',
		'elementor-v2-ui',
	];

	/** Editor-only: enqueues the live-edit bridge that mirrors settings to the preview iframe. */
	public function enqueue_editor_bridge(): void
	{
		$asset = $this->load_asset('editor-bridge');

		// Merge the auto-detected deps (@wordpress/*) with the manually-listed
		// Elementor packages. dedup just in case future @wordpress/scripts
		// versions start auto-detecting @elementor/* too.
		$deps = array_values(array_unique(array_merge(
			$asset['dependencies'],
			self::EDITOR_BRIDGE_ELEMENTOR_DEPS
		)));

		wp_enqueue_script(
			self::HANDLE . '-editor-bridge',
			WCF_ADDONS_URL . self::BUILD_DIR . 'editor-bridge.js',
			$deps,
			$asset['version'],
			true
		);

		wp_localize_script(
			self::HANDLE . '-editor-bridge',
			'aaeAtomicBridge',
			[
				'is_pro' => defined( 'WCF_ADDONS_PRO_FILE' ),
			]
		);
	}

	private function load_asset(string $entry, array $manual_deps = []): array
	{
		$file = WCF_ADDONS_PATH . self::BUILD_DIR . $entry . '.asset.php';

		if (! file_exists($file)) {
			return [
				'dependencies' => $manual_deps,
				'version'      => WCF_ADDONS_VERSION,
			];
		}

		$asset = require $file;
		$merged_deps = array_unique(array_merge($asset['dependencies'] ?? [], $manual_deps));

		return [
			'dependencies' => array_values($merged_deps),
			'version'      => $asset['version']      ?? WCF_ADDONS_VERSION,
		];
	}
}
