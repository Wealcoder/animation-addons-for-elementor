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
 * Pattern mirrors Elementor's atomic Interactions but split per-feature:
 *   - data-aae-text-id  + window.AAE_INTERACTIONS_TEXT
 *   - data-aae-anim-id  + window.AAE_INTERACTIONS_ANIM
 *   - data-aae-tilt-id  + window.AAE_INTERACTIONS_TILT  (future)
 *
 * Why namespace? An element may carry BOTH text + regular animation
 * settings (e-heading). Sharing one map keyed by element id would have
 * the second registration clobber the first.
 *
 * Each namespace renders as its own inline <script> in the footer.
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
			printf(
				'<script id="%s">window.%s=Object.assign(window.%s||{},%s);</script>',
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
