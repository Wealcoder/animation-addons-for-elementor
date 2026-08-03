<?php
namespace WCF_ADDONS\Atomic\Presets;

use WCF_ADDONS\AtomicWidgets\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This plugin's own proxy REST route for the remote preset system. The
 * editor's JS calls THIS route (same-origin — no CORS involved) rather than
 * themecrowdy.com directly, because:
 *
 *  1. CORS: a direct browser fetch to themecrowdy.com would need it to send
 *     permissive CORS headers for every AAE install's domain — fragile.
 *  2. The transient/manifest-diff cache (Cache.php) has to live in PHP
 *     regardless, so the JS was always calling into this plugin's PHP.
 *  3. Matches this plugin's existing convention — Library_Source
 *     (inc/library-source.php) already proxies a remote fetch server-side,
 *     the JS never talks to block.animation-addons.com directly.
 */
final class Rest {

	const NAMESPACE = 'aae/v1';
	const ROUTE     = '/presets';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_presets' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'element_type' => [ 'type' => 'string', 'required' => true ],
					'category'     => [ 'type' => 'string', 'required' => false ],
				],
			]
		);
	}

	public function get_presets( \WP_REST_Request $request ): \WP_REST_Response {
		$type     = (string) $request->get_param( 'element_type' );
		$category = (string) $request->get_param( 'category' );

		if ( '' === $type ) {
			return new \WP_REST_Response(
				[
					'presets'       => [],
					'remote_failed' => false,
				],
				200
			);
		}

		$is_dev = class_exists( Atomic::class ) && Atomic::instance()->is_dev_environment_public();

		$cache  = new Cache();
		$result = $cache->get_presets_for_type( $type, $category, $is_dev );

		return new \WP_REST_Response(
			[
				'presets' => array_values( $result['presets'] ),
				/*
				 * A remote outage and a type that genuinely has no presets both
				 * produce an empty `presets` array on a 200 response, so the
				 * editor cannot tell them apart from the list alone — and it must,
				 * because it caches the answer for the whole session and hides the
				 * preset control on an empty one. This flag is that distinction:
				 * true means the remote half could not be read (and no cached copy
				 * existed), so the list is incomplete rather than authoritative.
				 */
				'remote_failed' => $result['remote_failed'],
			],
			200
		);
	}
}
