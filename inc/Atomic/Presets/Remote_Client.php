<?php
namespace WCF_ADDONS\Atomic\Presets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wp_remote_get() wrapper against the AAE Preset Server REST API
 * (themecrowdy.com — see aae-preset-server plugin). Mirrors the existing
 * server-side-fetch pattern already used by Library_Source::request_template_data()
 * (inc/library-source.php) — this plugin's JS never talks to the remote
 * server directly; only PHP does, then this plugin exposes its own proxy
 * route (see Rest.php) for the editor's JS to call.
 *
 * Never throws. Any failure (network error, non-200, malformed JSON) is
 * logged (gated by WP_DEBUG — this codebase has no centralized logger) and
 * resolved to null/[] so callers can always fall through to the next tier
 * of the fallback/merge chain without special-casing exceptions.
 */
final class Remote_Client {

	const BASE_URL = 'https://crowdytheme.com/assets/wp-json/aae-preset-server/v1';

	const TIMEOUT = 8;

	/**
	 * GET /manifest — cheap, no JSON bodies. Returns [] on any failure,
	 * including a genuinely-empty-but-200 response (both are "nothing to
	 * show", callers don't need to distinguish them).
	 *
	 * @return array<int, array> Manifest entries, or [] on failure.
	 */
	public function fetch_manifest( string $element_type = '', string $category = '' ): array {
		$data = $this->request( '/manifest', $element_type, $category );

		return $data['presets'] ?? [];
	}

	/**
	 * GET /presets — full list with resolved `model` bodies.
	 *
	 * Unlike fetch_manifest(), this one DOES distinguish "the server answered
	 * with nothing" from "the request failed": null means the request itself
	 * failed (network error, non-200, malformed JSON), an array means the
	 * server answered and may legitimately be empty. Callers must keep the two
	 * apart — caching or displaying a failure as "this type has no presets" is
	 * what made the editor's preset control vanish for an entire session on a
	 * single network blip.
	 *
	 * @return array<int, array>|null Preset entries, or null on failure.
	 */
	public function fetch_presets_for_type( string $element_type, string $category = '' ): ?array {
		$data = $this->request( '/presets', $element_type, $category );

		if ( null === $data ) {
			return null;
		}

		return $data['presets'] ?? [];
	}

	/**
	 * GET /presets/{id} — single preset with body. Returns null on any
	 * failure (network error, 404, malformed response).
	 */
	public function fetch_single( int $id ): ?array {
		$response = wp_remote_get(
			self::BASE_URL . '/presets/' . $id,
			[ 'timeout' => self::TIMEOUT ]
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'fetch_single(' . $id . ') failed: ' . $response->get_error_message() );
			return null;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * @return array|null Decoded JSON body, or null on any request failure.
	 */
	private function request( string $path, string $element_type, string $category ): ?array {
		$args = [];

		if ( '' !== $element_type ) {
			$args['element_type'] = $element_type;
		}

		if ( '' !== $category ) {
			$args['category'] = $category;
		}

		$url = self::BASE_URL . $path;
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		$response = wp_remote_get( $url, [ 'timeout' => self::TIMEOUT ] );

		if ( is_wp_error( $response ) ) {
			$this->log( 'request(' . $path . ') failed: ' . $response->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$this->log( 'request(' . $path . ') returned HTTP ' . $code );
			return null;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) ) {
			$this->log( 'request(' . $path . ') returned malformed JSON' );
			return null;
		}

		return $decoded;
	}

	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AAE Preset Remote_Client] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
