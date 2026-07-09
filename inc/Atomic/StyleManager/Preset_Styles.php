<?php
/**
 * Preset interaction styles — per-preset CSS files, enqueued on demand.
 *
 * WHY THIS EXISTS
 * ----------------
 * Preset hover interactions (card-hover → descendant zoom / slide / tint) can't
 * be expressed by Elementor's atomic per-element styles (no descendant
 * selector) and custom_css is Pro-gated. So each preset ships a small block of
 * class-based CSS.
 *
 * Each preset's CSS lives in its OWN .css file next to the preset JSON
 * (Widgets/<Widget>/presets/css/<preset>.css) and is mapped by the preset's
 * marker class:
 *
 *     'aae-bold-card' => [ 'handle' => 'aae-preset-bold', 'path' => '.../bold-overlay-zoom.css' ]
 *
 * On every frontend render we look at the element's classes; for each marker
 * class present we enqueue that preset's stylesheet (WP de-dupes the handle, so
 * repeats / multiple presets cost nothing extra). Real .css files mean:
 *   - the browser CACHES them across pages (unlike inline CSS),
 *   - they lint / syntax-highlight / diff like normal CSS,
 *   - a page with no preset loads zero preset CSS,
 *   - only the presets actually used on the page load.
 *
 * REUSE: a new preset = one .css file + one line in map(). The marker-class
 * convention means other widgets can register their own preset CSS the same way.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\Atomic\StyleManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Preset_Styles {

	public function register(): void {
		// Frontend: enqueue on demand as matching elements render.
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_enqueue' ], 10, 1 );

		// Editor preview: the user can apply any preset live, so load them all.
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueue_all' ], 100 );
	}

	/**
	 * Marker-class → stylesheet map. `path` is relative to the plugin root.
	 *
	 * @return array<string,array{handle:string,path:string}>
	 */
	public static function map(): array {
		$base = 'inc/AtomicWidgets/Widgets/LoopGrid/presets/css/';

		return [
			'aae-hover-card'   => [ 'handle' => 'aae-preset-image-cover', 'path' => $base . 'image-cover-card.css' ],
			'aae-split-card'   => [ 'handle' => 'aae-preset-split-reveal', 'path' => $base . 'split-reveal.css' ],
			'aae-glass-card'   => [ 'handle' => 'aae-preset-glass', 'path' => $base . 'glass-frosted-card.css' ],
			'aae-slideup-card' => [ 'handle' => 'aae-preset-slide-up', 'path' => $base . 'slide-up-content.css' ],
			'aae-bold-card'    => [ 'handle' => 'aae-preset-bold', 'path' => $base . 'bold-overlay-zoom.css' ],
		];
	}

	/**
	 * Inspect a rendering element and enqueue the stylesheet for any preset
	 * marker class it carries.
	 *
	 * @param \Elementor\Element_Base|object $element
	 */
	public function maybe_enqueue( $element ): void {
		if ( is_admin() ) {
			return;
		}
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_raw_data' ) ) {
			return;
		}

		$raw     = $element->get_raw_data();
		$classes = $raw['settings']['classes']['value'] ?? null;
		if ( ! is_array( $classes ) ) {
			return;
		}

		$map = self::map();
		foreach ( $classes as $class ) {
			if ( is_string( $class ) && isset( $map[ $class ] ) ) {
				$this->enqueue_one( $map[ $class ] );
			}
		}
	}

	/** Enqueue every preset stylesheet (editor preview). */
	public function enqueue_all(): void {
		foreach ( self::map() as $entry ) {
			$this->enqueue_one( $entry );
		}
	}

	/**
	 * Register (if needed) and enqueue a single preset stylesheet. WordPress
	 * de-dupes by handle, so calling this repeatedly for the same preset (many
	 * cards, or the same preset used twice) outputs one <link>.
	 *
	 * @param array{handle:string,path:string} $entry
	 */
	private function enqueue_one( array $entry ): void {
		$handle = $entry['handle'];

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			$abs = WCF_ADDONS_PATH . $entry['path'];
			$ver = file_exists( $abs ) ? (string) filemtime( $abs ) : WCF_ADDONS_VERSION;

			wp_register_style( $handle, WCF_ADDONS_URL . $entry['path'], [], $ver );
		}

		wp_enqueue_style( $handle );
	}
}
