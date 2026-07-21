<?php
/**
 * AAE Forms — reCAPTCHA v3 key store + verification seam.
 *
 * Mirrors the Integrations (Brevo/Mailchimp) free-shell / pro-capability
 * split: this FREE class owns the global site-key/secret-key store and a
 * verification seam other code calls; PRO backs the seam with a real call
 * to Google's siteverify endpoint via the `aae_form/recaptcha_verify`
 * filter. Free-only (no pro): verification is reported as unavailable
 * rather than silently "passing" — a form that requires reCAPTCHA but has
 * no working verifier should not quietly skip the check.
 *
 * Unlike the Integrations providers, reCAPTCHA has ONE global config (one
 * Google reCAPTCHA v3 site), not one key per form — so this is a flat
 * site-key/secret-key pair, not a provider registry.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Captcha {

	/** Single option holding [ 'site_key' => string, 'secret_key' => string ]. */
	const OPTION_KEYS = 'aae_form_recaptcha_keys';

	/** Google's default "human" threshold for v3 scores (0.0–1.0). */
	const DEFAULT_THRESHOLD = 0.5;

	/** @return array{site_key:string,secret_key:string} */
	private static function keys(): array {
		$keys = get_option( self::OPTION_KEYS, [] );
		$keys = is_array( $keys ) ? $keys : [];

		return [
			'site_key'   => (string) ( $keys['site_key'] ?? '' ),
			'secret_key' => (string) ( $keys['secret_key'] ?? '' ),
		];
	}

	public static function site_key(): string {
		return self::keys()['site_key'];
	}

	public static function secret_key(): string {
		return self::keys()['secret_key'];
	}

	public static function has_keys(): bool {
		$keys = self::keys();

		return '' !== $keys['site_key'] && '' !== $keys['secret_key'];
	}

	/** Store both keys at once. Either can be cleared with an empty string. */
	public static function set_keys( string $site_key, string $secret_key ): void {
		update_option(
			self::OPTION_KEYS,
			[
				'site_key'   => trim( $site_key ),
				'secret_key' => trim( $secret_key ),
			],
			false
		);
	}

	/** Masked secret key for UI display — same shape as Integrations::mask(). */
	public static function mask_secret(): string {
		$secret = self::secret_key();
		$len    = strlen( $secret );

		if ( 0 === $len ) {
			return '';
		}

		$tail = $len > 4 ? substr( $secret, -4 ) : $secret;

		return str_repeat( '•', 8 ) . $tail;
	}

	/**
	 * Verify a v3 token against Google's siteverify endpoint via the pro
	 * filter. Returns a uniform shape whether pro is present or not, so
	 * Rest.php doesn't need to know which side answered.
	 *
	 * @return array{ok:bool,score:float,available:bool,message:string}
	 *   `available=false` means no pro verifier is registered at all (the
	 *   caller must decide the safe default: fail-closed if the form
	 *   requires reCAPTCHA, fail-open only for lower Bot Shield tiers).
	 */
	public static function verify( string $token, string $remote_ip ): array {
		if ( '' === $token || ! self::has_keys() ) {
			return [
				'ok'        => false,
				'score'     => 0.0,
				'available' => false,
				'message'   => 'reCAPTCHA not configured',
			];
		}

		/**
		 * Pro registers a callable here: (string $token, string $secret_key,
		 * string $remote_ip) => array{ok:bool,score:float,message:string}.
		 * Free ships no verifier, so this stays null and `available` is
		 * reported as false — the caller decides what "no verifier" means
		 * for that form (fail-closed vs. skip).
		 */
		$verifier = apply_filters( 'aae_form/recaptcha_verifier', null );

		if ( ! is_callable( $verifier ) ) {
			return [
				'ok'        => false,
				'score'     => 0.0,
				'available' => false,
				'message'   => 'reCAPTCHA verification requires Pro',
			];
		}

		$result = (array) call_user_func( $verifier, $token, self::secret_key(), $remote_ip );

		return [
			'ok'        => (bool) ( $result['ok'] ?? false ),
			'score'     => (float) ( $result['score'] ?? 0.0 ),
			'available' => true,
			'message'   => (string) ( $result['message'] ?? '' ),
		];
	}

	/** The pass/fail score threshold — filterable per the spec's config knobs. */
	public static function threshold(): float {
		return (float) apply_filters( 'aae_form/recaptcha_threshold', self::DEFAULT_THRESHOLD );
	}
}
