<?php
/**
 * AAE Forms — single-use submit tokens (Milestone 5).
 *
 * The cache-proofing layer from the spec's duplicate-submit prevention:
 * pages may be served from CDN/page cache, so nothing security-relevant is
 * baked into the HTML. The runtime fetches a fresh token (no-store) when
 * the visitor starts interacting, and the submit endpoint consumes it —
 * a reused token is a replay (409), an unknown one a security failure (403).
 *
 * Storage is transients (single-request lifecycle, auto-expiring). Token
 * issuance is itself rate-limited per hashed-visitor + form_key — raw IPs
 * are never stored, only an md5 keyed with a WP salt.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Token {

	/** Seconds a token stays valid after issue. */
	const TTL = 20 * MINUTE_IN_SECONDS;

	/** How long a consumed token is remembered, to answer replays with 409. */
	const USED_TTL = HOUR_IN_SECONDS;

	/** Token-issue rate limit: at most LIMIT issues per WINDOW per visitor+form. */
	const RATE_LIMIT  = 30;
	const RATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/**
	 * Issue a fresh token for a form, or null when the visitor is over the
	 * issue rate limit.
	 *
	 * @return array|null [ 'token' => string, 'expires_in' => int ]
	 */
	public static function issue( string $form_key ): ?array {
		if ( ! self::within_rate_limit( $form_key ) ) {
			return null;
		}

		$token = wp_generate_password( 40, false, false ); // alnum only.

		set_transient(
			self::key( $token ),
			[
				'form_key' => $form_key,
				'issued'   => time(),
			],
			self::TTL
		);

		return [
			'token'      => $token,
			'expires_in' => self::TTL,
		];
	}

	/**
	 * Consume a token (single use). Exactly one of these outcomes:
	 *
	 *   [ 'ok' => true, 'issued' => <unix ts> ]   — valid, now spent
	 *   [ 'ok' => false, 'reason' => 'replay' ]   — was valid, already spent (409)
	 *   [ 'ok' => false, 'reason' => 'invalid' ]  — unknown/expired/wrong form (403)
	 */
	public static function consume( string $token, string $form_key ): array {
		if ( '' === $token || strlen( $token ) > 64 || ! ctype_alnum( $token ) ) {
			return [
				'ok'     => false,
				'reason' => 'invalid',
			];
		}

		$data = get_transient( self::key( $token ) );

		if ( ! is_array( $data ) ) {
			// Never issued/expired — or already spent (replay gets its own answer).
			$was_used = get_transient( self::used_key( $token ) );

			return [
				'ok'     => false,
				'reason' => $was_used ? 'replay' : 'invalid',
			];
		}

		if ( ( $data['form_key'] ?? '' ) !== $form_key ) {
			return [
				'ok'     => false,
				'reason' => 'invalid',
			];
		}

		delete_transient( self::key( $token ) );
		set_transient( self::used_key( $token ), 1, self::USED_TTL );

		return [
			'ok'     => true,
			'issued' => (int) ( $data['issued'] ?? 0 ),
		];
	}

	/**
	 * Privacy-safe visitor signal: salted hash, never the raw IP (spec: raw
	 * IP storage is opt-in and independent of rate limiting).
	 */
	public static function visitor_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return md5( wp_salt( 'auth' ) . '|' . $ip );
	}

	/**
	 * Shared visitor-scoped rate limiter (Bot Shield): counts hits in a
	 * named bucket and reports whether THIS hit exceeds the cap. Buckets
	 * are keyed by salted visitor hash + bucket name, never raw IP.
	 */
	public static function over_limit( string $bucket, int $limit, int $window ): bool {
		$key   = 'aae_frl_' . md5( self::visitor_hash() . '|' . $bucket );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return true;
		}

		// Window starts at the first hit; count resets when it lapses.
		set_transient( $key, $count + 1, $window );

		return false;
	}

	/** Sliding counter per visitor+form; true while under the cap. */
	private static function within_rate_limit( string $form_key ): bool {
		return ! self::over_limit( 'token|' . $form_key, self::RATE_LIMIT, self::RATE_WINDOW );
	}

	private static function key( string $token ): string {
		return 'aae_ftok_' . md5( $token );
	}

	private static function used_key( string $token ): string {
		return 'aae_ftok_used_' . md5( $token );
	}
}
