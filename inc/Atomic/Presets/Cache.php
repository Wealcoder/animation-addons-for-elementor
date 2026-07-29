<?php
namespace WCF_ADDONS\Atomic\Presets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the transient cache for remote presets and merges them with local
 * bundled presets (Local_Fallback) — merge, not fallback-only: if local
 * files exist for a type, they show up in the SAME dropdown as remote
 * presets. Local presets tag `source: 'local'` and get no thumbnail_url
 * (client renders the placeholder for these); remote presets tag
 * `source: 'remote'`.
 *
 * IDs never collide between the two sets: local presets keep their
 * existing string-slug id scheme (sanitize_key(basename($file,'.json'))),
 * remote presets get 'remote-' . $numeric_id — so plain concatenation is
 * safe with no dedup step needed.
 *
 * Once every local .json file is eventually deleted (planned), Local_Fallback
 * naturally returns [] for every type and this class's merge degrades to
 * remote-only automatically — no code change needed at that point.
 */
final class Cache {

	const TYPE_TRANSIENT_PREFIX = 'aae_preset_type_';
	const MANIFEST_OPTION       = 'aae_preset_manifest_cache';

	private Remote_Client $remote;
	private Local_Fallback $local;

	public function __construct( ?Remote_Client $remote = null, ?Local_Fallback $local = null ) {
		$this->remote = $remote ?? new Remote_Client();
		$this->local  = $local ?? new Local_Fallback();
	}

	private static function ttl(): int {
		/**
		 * Filters the remote preset cache TTL, in seconds.
		 *
		 * @param int $ttl Default 12 hours.
		 */
		return (int) apply_filters( 'aae_preset_cache_ttl', 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Presets for one element type — remote (cached, manifest-diffed) merged
	 * with local (always read fresh, cheap glob). Never errors; an unknown
	 * type or a total remote outage still returns whatever local has (which
	 * may itself be []).
	 *
	 * @return array<int, array>
	 */
	public function get_presets_for_type( string $type, string $category, bool $is_dev ): array {
		$local = $this->local->get_presets_for_type( $type );

		$remote = $is_dev
			? $this->fetch_remote_fresh( $type, $category )
			: $this->get_remote_cached_or_fetch( $type, $category );

		return array_merge( $this->tag_remote( $remote ), $local );
	}

	private function tag_remote( array $entries ): array {
		return array_map(
			static function ( array $entry ): array {
				$entry['id']     = 'remote-' . ( $entry['id'] ?? '' );
				$entry['source'] = 'remote';

				return $entry;
			},
			$entries
		);
	}

	private function get_remote_cached_or_fetch( string $type, string $category ): array {
		$transient_key = self::TYPE_TRANSIENT_PREFIX . $type;
		$cached        = get_transient( $transient_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$this->maybe_refresh_via_manifest( $type, $category, $cached, $transient_key );

			return get_transient( $transient_key ) ?: $cached;
		}

		return $this->fetch_remote_fresh( $type, $category, $transient_key );
	}

	/**
	 * Fetches the full bulk list and caches it (even if empty, so a type
	 * with legitimately nothing on the server doesn't get re-requested on
	 * every single page load). On dev/local environments, still returns the
	 * fresh result but never touches the transient.
	 */
	private function fetch_remote_fresh( string $type, string $category, ?string $cache_key = null ): array {
		$entries = $this->remote->fetch_presets_for_type( $type, $category );

		if ( null !== $cache_key ) {
			set_transient( $cache_key, $entries, self::ttl() );
			$this->update_manifest_option( $type, $entries );
		}

		return $entries;
	}

	/**
	 * Manifest-diff optimization: hit the cheap /manifest endpoint (no
	 * bodies) and only re-fetch full bodies for entries whose hash actually
	 * changed since the last cache write, or whose id is new/removed.
	 * Avoids re-downloading unchanged preset JSON on every cache-refresh
	 * cycle. If the manifest call itself fails, the existing (possibly
	 * stale) cached array is left untouched rather than cleared.
	 */
	private function maybe_refresh_via_manifest( string $type, string $category, array $cached, string $transient_key ): void {
		$manifest = $this->remote->fetch_manifest( $type, $category );

		if ( empty( $manifest ) && ! empty( $cached ) ) {
			// Could be a genuine "nothing on server" or a transient network
			// hiccup — either way, don't destroy a working cache over an
			// ambiguous empty manifest response; next natural TTL expiry
			// will re-attempt a full fetch.
			return;
		}

		$manifest_hashes = [];

		foreach ( $manifest as $entry ) {
			if ( isset( $entry['id'], $entry['hash'] ) ) {
				$manifest_hashes[ (int) $entry['id'] ] = (string) $entry['hash'];
			}
		}

		$stored_option = get_option( self::MANIFEST_OPTION, [] );
		$stored_hashes = $stored_option[ $type ] ?? [];

		$changed_ids = [];
		foreach ( $manifest_hashes as $id => $hash ) {
			if ( ( $stored_hashes[ $id ] ?? null ) !== $hash ) {
				$changed_ids[] = $id;
			}
		}

		$removed_ids = array_diff( array_keys( $stored_hashes ), array_keys( $manifest_hashes ) );

		if ( empty( $changed_ids ) && empty( $removed_ids ) ) {
			// Nothing changed — just extend the existing cache's life.
			set_transient( $transient_key, $cached, self::ttl() );
			return;
		}

		// Re-index cached entries by their numeric remote id for easy patching.
		$by_id = [];
		foreach ( $cached as $entry ) {
			$raw_id = isset( $entry['id'] ) ? (string) $entry['id'] : '';
			$numeric_id = (int) str_replace( 'remote-', '', $raw_id );
			$by_id[ $numeric_id ] = $entry;
		}

		foreach ( $removed_ids as $removed_id ) {
			unset( $by_id[ (int) $removed_id ] );
		}

		foreach ( $changed_ids as $changed_id ) {
			$fresh = $this->remote->fetch_single( (int) $changed_id );

			if ( null !== $fresh ) {
				$by_id[ (int) $changed_id ] = $fresh;
			}
		}

		$refreshed = array_values( $by_id );

		set_transient( $transient_key, $refreshed, self::ttl() );
		$this->update_manifest_option( $type, $refreshed );
	}

	private function update_manifest_option( string $type, array $entries ): void {
		$option = get_option( self::MANIFEST_OPTION, [] );

		$hashes = [];
		foreach ( $entries as $entry ) {
			$raw_id     = isset( $entry['id'] ) ? (string) $entry['id'] : '';
			$numeric_id = (int) str_replace( 'remote-', '', $raw_id );

			if ( $numeric_id > 0 && isset( $entry['hash'] ) ) {
				$hashes[ $numeric_id ] = (string) $entry['hash'];
			}
		}

		$option[ $type ] = $hashes;

		update_option( self::MANIFEST_OPTION, $option, false );
	}
}
