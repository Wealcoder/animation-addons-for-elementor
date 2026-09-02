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
	 * Catalogue of per-effect JS bundles that belong to THIS plugin. Each entry
	 * maps a stable handle to the build-time entry path (relative to BUILD_DIR).
	 * Render.php calls wp_enqueue_script( $handle ) for the effects a widget
	 * actually uses.
	 *
	 * Custom CSS is here because it never moved to Pro (two bundled free presets
	 * depend on it); nested-slider is here because it is a WIDGET runtime, not an
	 * extension effect — the Nested Slider and Loop Grid Slider both enqueue it.
	 * Image animation stays free (its Schema/Controls/Render never moved to Pro),
	 * so its bundle is registered here too.
	 */
	const EFFECT_BUNDLES = [
		'aae-effect-custom-css'      => 'effects/custom-css.js',
		'aae-effect-nested-slider'   => 'effects/nested-slider.js',
		'aae-effect-image-animation' => 'effects/image-animation.js',
		// No GSAP dependency — a muted looping <video> needs none, so this one
		// costs only its own ~2KB on pages that use it.
		'aae-effect-background-video' => 'effects/background-video.js',
		// No GSAP dependency either — a plain background + mix-blend-mode
		// application, not an animation. See ImageOverlay/Render.php.
		'aae-effect-image-overlay'    => 'effects/image-overlay.js',
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

		// Preset interaction CSS is handled by StyleManager\Preset_Styles (a
		// keyed CSS map printed inline on demand), not enqueued here.
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
	 *
	 * Deliberately NOT here: SplitText and ScrollToPlugin. Those belong to
	 * single effects, and the bundles that need them declare them — those
	 * bundles now live in Pro (inc/AtomicV4/Extensions/Assets.php:
	 * aae-effect-animation -> SplitText, aae-effect-scroll-to ->
	 * ScrollToPlugin), which is why ensure_gsap_registered() below still
	 * REGISTERS both handles even though nothing here depends on them: Pro
	 * declares the dependency, this plugin owns the file. WordPress then pulls
	 * each one in exactly on the pages where Pro's Render enqueues that effect.
	 * Listing them on the shared core handle shipped ~70KB of unused JS to every
	 * page with ANY animation — flagged by Lighthouse as unused JavaScript
	 * on pages with no text-animation / scroll-to at all. common.js itself
	 * only reads window.SplitText lazily at play time (getSplitText), so it
	 * has no load-order dependency on either plugin.
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
		// DrawSVG + MotionPath: needed by the DrawSVG atomic widget. Pro only
		// registers these when its GSAP-library dashboard toggle is on, so
		// register them here too (no-op if already registered).
		if (! wp_script_is('DrawSVGPlugin', 'registered')) {
			wp_register_script(
				'DrawSVGPlugin',
				WCF_ADDONS_PRO_URL . 'assets/lib/DrawSVGPlugin.min.js',
				['gsap'],
				defined('WCF_ADDONS_PRO_VERSION') ? WCF_ADDONS_PRO_VERSION : WCF_ADDONS_VERSION,
				true
			);
		}
		if (! wp_script_is('MotionPathPlugin', 'registered')) {
			wp_register_script(
				'MotionPathPlugin',
				WCF_ADDONS_PRO_URL . 'assets/lib/MotionPathPlugin.min.js',
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
	 *
	 * SPLIT IN TWO, because Elementor registers the halves under different
	 * conditions and only one half decides whether this bundle can work at all.
	 * Verified against Elementor 4.x's own source rather than assumed:
	 *
	 *  - The six below come from `Atomic_Widgets\Module::PACKAGES`, added to the
	 *    `elementor/editor/v2/packages` filter by a constructor that returns early
	 *    unless `Module::is_active()`. No atomic editor, no handles.
	 *  - The five in the next constant are in `Editor_V2_Loader::LIBS`, which is
	 *    unconditional — they exist in every Elementor editor.
	 *
	 * That split is exactly what the reported notice listed as unregistered, which
	 * is the confirmation that this is the real boundary and not a guess.
	 */
	const EDITOR_BRIDGE_ATOMIC_DEPS = [
		'elementor-v2-editor-canvas',
		'elementor-v2-editor-controls',
		'elementor-v2-editor-editing-panel',
		'elementor-v2-editor-elements',
		'elementor-v2-editor-props',
		'elementor-v2-editor-styles',
		// Read by editor-bridge/hook-classes-provider.js. Also an atomic PACKAGE
		// (Atomic_Widgets Module::PACKAGES), so it belongs in this half of the
		// deps rather than the always-present one.
		'elementor-v2-editor-styles-repository',
	];

	/**
	 * The always-present half — Elementor's `Editor_V2_Loader::LIBS`.
	 *
	 * Still tested rather than trusted: "unconditional today" is a fact about one
	 * release, and a handle that quietly moves out of LIBS should cost us a
	 * feature and a debug line, not a notice across the top of every editor load.
	 */
	const EDITOR_BRIDGE_CORE_DEPS = [
		'elementor-v2-editor-responsive',
		'elementor-v2-editor-ui',
		'elementor-v2-editor-v1-adapters',
		'elementor-v2-schema',
		'elementor-v2-ui',
	];

	/**
	 * Editor-only: enqueues the live-edit bridge that mirrors settings to the
	 * preview iframe.
	 *
	 * DOES NOTHING WHEN THE EDITOR HAS NO ATOMIC LAYER, which is every V3-era
	 * site. This hook fires on EVERY editor load, so the bundle was being
	 * enqueued against six handles Elementor had never registered, and WordPress
	 * printed this across the top of the editor each time:
	 *
	 *   Function WP_Scripts::add was called incorrectly. The script with the
	 *   handle "aae-atomic-common-editor-bridge" was enqueued with dependencies
	 *   that are not registered: elementor-v2-editor-canvas, …
	 *
	 * The notice was the visible half. The real problem is that the bundle exists
	 * solely to bridge to `window.elementorV2.editorControls` and friends, which
	 * are not there either — so ~700 KB was being shipped into an editor that had
	 * nothing for it to attach to.
	 *
	 * TESTED BY ASKING WORDPRESS, not by asking whether OUR atomic registry is on.
	 * Those are different questions: a site can have AAE's atomic widgets switched
	 * off while Elementor's atomic editor is loaded, and vice versa. The literal
	 * precondition is "do the handles this script declares exist", and
	 * `wp_script_is()` answers exactly that — so a future Elementor that renames a
	 * package degrades to a missing feature with a debug line rather than to a
	 * notice on every editor load. Priority 100 on
	 * `elementor/editor/after_enqueue_scripts` is late enough for Elementor to
	 * have registered them if it is going to.
	 */
	public function enqueue_editor_bridge(): void
	{
		// GATE ONE — is there anything of OURS for it to drive?
		//
		// The bundle powers panel controls, the preset picker, the mask picker and
		// the responsive rows for AAE's atomic widgets and extensions. With every
		// one of them switched off in the dashboard there is nothing on screen it
		// could attach to, so shipping it is pure weight — and on a V3-era site
		// that is the normal state, not an edge case.
		//
		// Extensions count as well as widgets: an extension adds sections to
		// Elementor's OWN atomic widgets, so a site with every AAE atomic widget
		// off but one extension on still needs the bridge.
		if (! class_exists('\\WCF_ADDONS\\AtomicWidgets\\Atomic')) {
			return;
		}

		$atomic = \WCF_ADDONS\AtomicWidgets\Atomic::instance();

		if (! method_exists($atomic, 'has_active_atomic') || ! $atomic->has_active_atomic()) {
			return;
		}

		// GATE TWO — does the editor it bridges TO exist?
		$missing_atomic = array_values(array_filter(
			self::EDITOR_BRIDGE_ATOMIC_DEPS,
			static fn($handle) => ! wp_script_is($handle, 'registered')
		));

		// ANY of the atomic packages missing and the bridge cannot function: its
		// webpack externals resolve against globals those packages define, so it
		// would load and then do nothing. Enqueue nothing at all.
		if ($missing_atomic) {
			return;
		}

		// The core half is present in every Elementor editor today, so anything
		// missing here is a rename rather than a switched-off feature. Drop it from
		// the list — a stale handle must not cost the whole bridge — and say so
		// where a developer will see it, because the symptom otherwise is one panel
		// section quietly absent with nothing on screen to explain it.
		$core = array_values(array_filter(
			self::EDITOR_BRIDGE_CORE_DEPS,
			static fn($handle) => wp_script_is($handle, 'registered')
		));

		$missing_core = array_diff(self::EDITOR_BRIDGE_CORE_DEPS, $core);

		if ($missing_core && defined('WP_DEBUG') && WP_DEBUG) {
			error_log(sprintf(
				'AAE: editor-bridge enqueued without %d unregistered Elementor package(s): %s',
				count($missing_core),
				implode(', ', $missing_core)
			));
		}

		$asset = $this->load_asset('editor-bridge');

		// Merge the auto-detected deps (@wordpress/*) with the Elementor packages
		// that are actually REGISTERED. dedup just in case future @wordpress/scripts
		// versions start auto-detecting @elementor/* too.
		$deps = array_values(array_unique(array_merge(
			$asset['dependencies'],
			self::EDITOR_BRIDGE_ATOMIC_DEPS,
			$core
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

				// Mask shape catalogue for the Style-tab section's picker.
				// Sent from PHP rather than rebuilt in JS so the panel and the
				// renderer can never disagree about which shapes exist or where
				// their SVGs live — Shapes::all() is the single source of truth,
				// filter included.
				'mask_shapes' => class_exists( '\WCF_ADDONS\Atomic\Mask\Shapes' )
					? \WCF_ADDONS\Atomic\Mask\Shapes::all()
					: [],
			]
		);

		// Remote preset system config — read by PresetPickerControl.jsx /
		// preset-apply.js's ensurePresetsLoaded(), both of which ship inside
		// THIS bundle (src/modules/atomic/editor-bridge.js). Must be
		// localized onto this handle, not 'aae-atomic-editor' (a separate,
		// unrelated small bundle — see class-atomic.php's
		// enqueue_atomic_editor_scripts()).
		wp_localize_script(
			self::HANDLE . '-editor-bridge',
			'AAE_PRESET_CONFIG',
			[
				'restUrl'          => esc_url_raw( rest_url( 'aae/v1/presets' ) ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'proActive'        => defined( 'WCF_ADDONS_PRO_VERSION' ),
				'placeholderThumb' => WCF_ADDONS_URL . 'assets/images/preset-placeholder.png',
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
