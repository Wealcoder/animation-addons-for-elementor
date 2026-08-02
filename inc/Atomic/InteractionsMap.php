<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton collector for per-element animation configs. Each extension
 * owns its own NAMESPACE so multiple effects can coexist on a single
 * widget without overwriting each other.
 *
 * Pattern mirrors Elementor's atomic Interactions but split per-feature —
 * one global per namespace, each keyed by ELEMENT ID:
 *   - window.AAE_INTERACTIONS_TEXT
 *   - window.AAE_INTERACTIONS_ANIM
 *
 * NO per-namespace marker attribute is emitted. The element is found via
 * Elementor's own `data-interaction-id`, which every atomic widget already
 * renders, and that id is the map key. (An earlier version of this comment
 * described `data-aae-text-id` / `data-aae-anim-id` attributes; they have
 * never been rendered. Anything looking for animated elements in the DOM must
 * read these globals and query `[data-interaction-id="<key>"]` — the
 * Performance module's lazy loader does exactly that, and silently loaded
 * everything up-front for as long as it trusted the old comment.)
 *
 * Why namespace? An element may carry BOTH text + regular animation
 * settings (e-heading). Sharing one map keyed by element id would have
 * the second registration clobber the first.
 *
 * Each namespace renders as its own inline <script> in the footer at
 * `wp_footer` priority 5 — before anything that needs to read them.
 */
final class InteractionsMap {

	/** Map of `namespace => [ element_id => config ]`. */
	private static array $entries = [];

	private static bool $hooked = false;

	/**
	 * Register a config under a namespace. `text` and `anim` are the two
	 * shipped namespaces; future extensions pick their own short ID.
	 */
	public static function register( string $namespace, string $id, array $config ): void {
		if ( '' === $namespace || '' === $id ) {
			return;
		}
		if ( ! isset( self::$entries[ $namespace ] ) ) {
			self::$entries[ $namespace ] = [];
		}
		self::$entries[ $namespace ][ $id ] = $config;
		self::ensure_print_hook();
	}

	/** Hooks the footer print on first registration. Idempotent. */
	private static function ensure_print_hook(): void {
		if ( self::$hooked ) {
			return;
		}
		self::$hooked = true;

		add_action( 'wp_footer',                    [ __CLASS__, 'print_maps' ], 5 );
		add_action( 'elementor/preview/footer',     [ __CLASS__, 'print_maps' ], 5 );
	}

	public static function print_maps(): void {
		if ( empty( self::$entries ) ) {
			return;
		}

		foreach ( self::$entries as $namespace => $map ) {
			if ( empty( $map ) ) {
				continue;
			}
			$json = wp_json_encode( $map );
			if ( false === $json ) {
				continue;
			}

			$window_key = self::window_key( $namespace );
			$script_id  = 'aae-interactions-' . preg_replace( '/[^a-z0-9_-]/i', '', $namespace );

			// Merge so we never clobber earlier entries.
			//
			// `data-no-optimize` / `data-no-defer` are the cache-plugin fence.
			// JS Delay covers INLINE scripts, and LiteSpeed's Guest
			// Optimization forces delay on for every logged-out visitor — a
			// delayed map means the runtime reads `window.AAE_INTERACTIONS_*`
			// as empty and every element on the page renders unanimated. The
			// attributes are LiteSpeed's own convention (`gui.cls.php:1167`)
			// and Rocket honours them too. This lives in FREE deliberately:
			// free ships the runtime and these maps, so the protection cannot
			// depend on Pro being present. See Pro's Cache_Compat for the
			// file-path half of the same fence, and CLAUDE.md → cache compat.
			printf(
				'<script id="%s" data-no-optimize="1" data-no-defer="1">window.%s=Object.assign(window.%s||{},%s);</script>',
				esc_attr( $script_id ),
				esc_js( $window_key ),
				esc_js( $window_key ),
				$json // already JSON-encoded; no further escaping inside <script>
			);
		}

		self::$entries = [];
	}

	/**
	 * Public so JS-side code reading from the map can stay in sync with
	 * the naming rule. namespace 'text' → 'AAE_INTERACTIONS_TEXT'.
	 */
	public static function window_key( string $namespace ): string {
		return 'AAE_INTERACTIONS_' . strtoupper( preg_replace( '/[^a-z0-9_]/i', '', $namespace ) );
	}
}
