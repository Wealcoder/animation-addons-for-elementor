<?php
/**
 * AAE Forms — email-marketing provider registry + global key store.
 *
 * FREE-side framework for third-party subscriber sync. Owns three things,
 * all provider-agnostic:
 *
 *   1. A provider REGISTRY — pro adds concrete providers (Brevo, …) via the
 *      `aae_form/integrations` filter (mirrors Actions\Registry). Free ships
 *      NO providers, so on a free-only site the registry is empty and the
 *      Integrations UI shows every known provider as "Requires Pro".
 *   2. The global API-KEY store — one key per provider id, in a single
 *      option. Keys live here (site-wide, run-time-read) and NEVER in a
 *      form's actions_json, so rotating a key takes effect on every form
 *      instantly and the key never leaks into exported page data.
 *   3. Key MASKING for display — the UI shows `••••abcd`, never the raw key.
 *
 * The list of provider ids the UI should surface (even when no pro provider
 * backs them yet) is the `aae_form/integration_catalog` filter — so the
 * free "Requires Pro" card can appear before pro is installed.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integrations {

	/** Single option holding [ provider_id => api_key ]. */
	const OPTION_KEYS = 'aae_form_integration_keys';

	/**
	 * Concrete providers registered by pro, keyed by id.
	 *
	 * @return Provider[]
	 */
	public static function providers(): array {
		static $providers = null;

		if ( null === $providers ) {
			$registered = apply_filters( 'aae_form/integrations', [] );

			$providers = [];
			foreach ( (array) $registered as $provider ) {
				if ( $provider instanceof Provider ) {
					$providers[ $provider::id() ] = $provider;
				}
			}
		}

		return $providers;
	}

	/** Resolve one registered provider, or null (pro absent / unknown id). */
	public static function get( string $id ): ?Provider {
		return self::providers()[ $id ] ?? null;
	}

	/**
	 * The provider ids the UI should show a card for — the union of the
	 * pro-registered providers and a free "known but pro-gated" catalog, so
	 * the upsell card exists before pro is installed.
	 *
	 * @return array<string,string> [ id => label ]
	 */
	public static function catalog(): array {
		// Providers AAE plans to support; label is a plain string here since
		// no concrete Provider class need exist yet.
		$catalog = apply_filters(
			'aae_form/integration_catalog',
			[
				'brevo'     => 'Brevo',
				'mailchimp' => 'Mailchimp',
			]
		);

		// Pro-registered providers always appear, with their real label.
		foreach ( self::providers() as $id => $provider ) {
			$catalog[ $id ] = $provider::label();
		}

		return array_map( 'strval', (array) $catalog );
	}

	// ------------------------------------------------------------------
	// Global key store
	// ------------------------------------------------------------------

	/** All stored keys, [ provider_id => api_key ]. */
	private static function all_keys(): array {
		$keys = get_option( self::OPTION_KEYS, [] );

		return is_array( $keys ) ? $keys : [];
	}

	/** Raw stored key for one provider (empty string if unset). Server-side only. */
	public static function get_key( string $provider_id ): string {
		return (string) ( self::all_keys()[ $provider_id ] ?? '' );
	}

	/** Store/clear one provider's key. Empty string removes it. */
	public static function set_key( string $provider_id, string $api_key ): void {
		$keys    = self::all_keys();
		$api_key = trim( $api_key );

		if ( '' === $api_key ) {
			unset( $keys[ $provider_id ] );
		} else {
			$keys[ $provider_id ] = $api_key;
		}

		update_option( self::OPTION_KEYS, $keys, false );
	}

	public static function has_key( string $provider_id ): bool {
		return '' !== self::get_key( $provider_id );
	}

	/**
	 * Masked form of a key for UI display: last 4 chars revealed, the rest
	 * bulleted. Never returns enough to reconstruct the key. Empty → ''.
	 */
	public static function mask( string $api_key ): string {
		$api_key = trim( $api_key );
		$len     = strlen( $api_key );

		if ( 0 === $len ) {
			return '';
		}

		$tail = $len > 4 ? substr( $api_key, -4 ) : $api_key;

		return str_repeat( '•', 8 ) . $tail;
	}
}
