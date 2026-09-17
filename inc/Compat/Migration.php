<?php
/**
 * Migration — the consent-driven copy of every renamed option from its
 * pre-4.2 name to its `aaeaddon_` name, and the screen that reports it.
 *
 * Nothing here runs by itself except one write on the version bump. The
 * sequence on an existing site:
 *
 *   1. The update lands. `Key_Bridge` is in OLD_LIVE: the new code reads and
 *      writes the old rows through the redirect. The site works as before.
 *   2. The first request after the update (`wp_loaded`, the version-bump
 *      block in the main file) calls `on_version_change()`, which writes
 *      `aaeaddon_migration_state = { status: awaiting_consent }`. ONE option
 *      write; no table scan, no count.
 *   3. The Migration screen (`?page=aaeaddon_settings&tab=migration`, also
 *      its own submenu item) and a notice on the plugin's own screens tell
 *      the administrator what would be copied. The counts are taken when
 *      that PAGE is opened, never on a visitor's request.
 *   4. "Start migration" (`aaeaddon_migration_start`) copies every old row
 *      that exists to its new name — one request, ~60 rows — records the
 *      outcome per key, flips the state to `complete` and the bridge to
 *      NEW_LIVE in the same process. From then on the old rows are
 *      write-through mirrors: current, never deleted, the rollback point.
 *
 * A FRESH install (no pre-4.2 row anywhere) is `complete` from its activation
 * hook and never sees the screen's consent state or the notice; the submenu
 * item still exists and opens the page in its finished state, so the backup
 * and the log are always reachable.
 *
 * `on_version_change()` also runs the copy again — silently — whenever the
 * state is already `complete`: a site that was downgraded, wrote rows under
 * the old names while downgraded, and updated again gets those rows brought
 * forward without a second consent (the old row is the truth in that window,
 * exactly as it was in OLD_LIVE).
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.0
 */

namespace Wealcoder\AnimationAddons\Compat;

use Wealcoder\AnimationAddons\Nonce;
defined( 'ABSPATH' ) || exit;

final class Migration {

	const STATE_OPTION = Key_Bridge::STATE_OPTION;
	const LOG_OPTION   = 'aaeaddon_migration_log';
	const LOG_CAP      = 500;

	/**
	 * The paid add-on release that carries its half of the 4.2 compatibility
	 * layer (namespace mirror, renamed AJAX actions/handles). Older Pro
	 * builds still run — free's aliases answer every name they use — but
	 * the Migration screen and the post-migration notice ask for this one.
	 */
	const PRO_COMPAT_VERSION = '4.3.0';

	const NONCE = Nonce::ADMIN;
	const CAP   = 'manage_options';

	const PAGE_URL = 'admin.php?page=aaeaddon_settings&tab=migration';

	/** Old rows whose presence proves this site ran a pre-4.2 release. */
	const SENTINELS = array(
		'wcf_addons_version',
		'wcf_save_widgets',
		'wcf_addons_setup_wizard',
		'aae_atomic_widgets',
		'aae_animation_settings',
		'aae_installed',
	);

	/** @var array|null */
	private static $state = null;

	/** @var \stdClass|null a unique "absent" marker for raw reads */
	private static $missing = null;

	private static function missing() {
		if ( null === self::$missing ) {
			self::$missing = new \stdClass();
		}
		return self::$missing;
	}

	/** Hook everything the feature needs. Called once from the plugin bootstrap. */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 27 );
		add_filter( 'submenu_file', array( __CLASS__, 'highlight_menu' ) );
		add_filter( 'wcf_addons_dashboard_config', array( __CLASS__, 'inject_dashboard_config' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		add_action( 'admin_init', array( __CLASS__, 'register_notices' ), 11 );
		add_action( 'after_plugin_row_' . AAEADDON_BASE, array( __CLASS__, 'plugin_row' ), 10, 1 );
		if ( defined( 'WCF_ADDONS_PRO_BASE' ) ) {
			add_action( 'after_plugin_row_' . WCF_ADDONS_PRO_BASE, array( __CLASS__, 'plugin_row' ), 10, 1 );
		}

		foreach ( array( 'start', 'status', 'export', 'import', 'report' ) as $action ) {
			add_action( 'wp_ajax_aaeaddon_migration_' . $action, array( __CLASS__, 'ajax_' . $action ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* State                                                                */
	/* ------------------------------------------------------------------ */

	public static function state() {
		if ( null === self::$state ) {
			$state       = Key_Bridge::raw_get( self::STATE_OPTION );
			self::$state = is_array( $state ) ? $state : array();
		}
		return self::$state;
	}

	/**
	 * Drop the per-process memo of the state option. The bridge calls this on
	 * `switch_blog`: the state is per site, the memo was for the previous one.
	 */
	public static function forget_state() {
		self::$state = null;
	}

	public static function status() {
		$state = self::state();
		return isset( $state['status'] ) ? $state['status'] : '';
	}

	/** Forget the memoised state (a test that rewrites the option by hand). */
	public static function reset_cache() {
		self::$state = null;
		Key_Bridge::set_phase( Key_Bridge::phase_from_state( Key_Bridge::raw_get( self::STATE_OPTION ) ) );
	}

	public static function is_complete() {
		return 'complete' === self::status();
	}

	private static function save_state( array $state ) {
		$state['updated_at'] = time();
		self::$state         = $state;
		// Autoloaded on purpose: read on every request by the bridge, ~1 KB.
		Key_Bridge::raw_update( self::STATE_OPTION, $state, true );
		Key_Bridge::set_phase( Key_Bridge::phase_from_state( $state ) );
	}

	private static function log( $line ) {
		$log = Key_Bridge::raw_get( self::LOG_OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = array( time(), (string) $line );
		if ( count( $log ) > self::LOG_CAP ) {
			$log = array_slice( $log, -self::LOG_CAP );
		}
		Key_Bridge::raw_update( self::LOG_OPTION, $log, false );
	}

	public static function get_log() {
		$log = Key_Bridge::raw_get( self::LOG_OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Does this database carry a pre-4.2 row? Raw reads — the bridge must not
	 * answer, and every sentinel is autoloaded on a site that has one.
	 */
	public static function existing_site( $previous_version = '' ) {
		if ( '' !== (string) $previous_version ) {
			return true;
		}
		foreach ( self::SENTINELS as $name ) {
			if ( false !== Key_Bridge::raw_get( $name ) ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Deciding                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * The version-bump hook. $previous is what the version option held
	 * before this request rewrote it ('' on a fresh install).
	 */
	public static function on_version_change( $previous ) {
		$state = self::state();
		$map   = Key_Bridge::map();

		if ( empty( $state ) ) {
			if ( self::existing_site( $previous ) ) {
				self::await_consent( $previous );
			} else {
				self::complete_fresh();
			}
			return;
		}

		// Already decided. A complete site re-runs the copy for anything the
		// old rows hold that the new ones do not (downgrade window, or a map
		// version this site has not seen).
		if ( 'complete' === $state['status'] ) {
			$result = self::copy();
			if ( $result['copied'] > 0 || (int) $state['map_version'] !== (int) $map['map_version'] ) {
				$state['map_version'] = $map['map_version'];
				$state['categories']  = self::merge_categories( $state['categories'], $result['categories'] );
				self::save_state( $state );
				self::log( sprintf( 'Version %s: %d row(s) brought forward from the old names.', AAEADDON_VERSION, $result['copied'] ) );
			}
		}
	}

	/** The activation hook: decide BEFORE the hook writes its first option. */
	public static function on_activation() {
		if ( ! empty( self::state() ) ) {
			return;
		}
		if ( self::existing_site() ) {
			self::await_consent( (string) Key_Bridge::raw_get( 'wcf_addons_version', '' ) );
		} else {
			self::complete_fresh();
		}
	}

	/**
	 * Run `$callback` once per site of the network when the activation was
	 * network-wide, else once for the current site. WordPress fires the
	 * activation hook ONCE, on the main site, for a network activation; the
	 * migration state is per site, so every site has to be decided here or
	 * an existing sub-site would wait for its first request to be noticed.
	 * A site created later needs nothing: its first request runs the version
	 * bump, finds no old rows and completes as fresh.
	 *
	 * @param callable $callback Per-site work; runs with that site current.
	 * @param bool     $network_wide The activation hook's own argument.
	 */
	public static function for_each_site( $callback, $network_wide ) {
		if ( ! $network_wide || ! is_multisite() ) {
			$callback();
			return;
		}
		foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
			switch_to_blog( (int) $site_id );
			$callback();
			restore_current_blog();
		}
	}

	private static function await_consent( $previous ) {
		$map = Key_Bridge::map();
		self::save_state( array(
			'map_version' => $map['map_version'],
			'status'      => 'awaiting_consent',
			'site'        => 'existing',
			'from'        => (string) $previous,
			'decided_at'  => time(),
			'finished_at' => null,
			'consent'     => null,
			'backup'      => array( 'exported_at' => null ),
			'categories'  => self::empty_categories(),
		) );
		self::log( sprintf( 'Updated from %s: this site has data under the pre-4.2 names. Waiting for an administrator to start the migration.', $previous ?: 'an earlier version' ) );
	}

	private static function complete_fresh() {
		$map    = Key_Bridge::map();
		$result = self::copy(); // normally 0 rows — closes the window before the hook ran.
		self::save_state( array(
			'map_version' => $map['map_version'],
			'status'      => 'complete',
			'site'        => 'fresh',
			'from'        => '',
			'decided_at'  => time(),
			'finished_at' => time(),
			'consent'     => null,
			'backup'      => array( 'exported_at' => null ),
			'categories'  => $result['categories'],
		) );
		self::log( 'Fresh install: nothing to migrate. Storage names are current.' );
	}

	/* ------------------------------------------------------------------ */
	/* The copy                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Copy every old row that exists to its new name. Old rows are never
	 * changed or removed. Where the old row exists it is the truth and
	 * overwrites a stale new row (the downgrade window); where it is absent
	 * the new name is left as it is.
	 *
	 * @return array{copied:int, categories:array}
	 */
	public static function copy() {
		global $wpdb;
		$map        = Key_Bridge::map();
		$categories = self::empty_categories();
		$copied     = 0;

		// The exact autoload flag of every old row, one query: WordPress 6.6
		// stores on/off/auto/auto-on/auto-off and older rows still say yes/no.
		// A boolean from wp_load_alloptions() would rewrite them all to `on`.
		$old_names = array_merge( array_keys( $map['options'] ), array_keys( $map['options_pro'] ) );
		$autoloads = array();
		if ( $old_names ) {
			$placeholders = implode( ',', array_fill( 0, count( $old_names ), '%s' ) );
			foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT option_name, autoload FROM {$wpdb->options} WHERE option_name IN ($placeholders)", $old_names ) ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only.
				$autoloads[ $row->option_name ] = $row->autoload;
			}
		}

		foreach ( array( 'options', 'options_pro' ) as $group ) {
			$cat = &$categories[ $group ];
			foreach ( $map[ $group ] as $old => $new ) {
				$cat['total']++;
				$value = Key_Bridge::raw_get( $old, self::missing() );
				if ( self::missing() === $value ) {
					$cat['items'][ $old ] = 'absent (kept absent)';
					$cat['skipped']++;
					continue;
				}
				$autoload = isset( $autoloads[ $old ] ) ? $autoloads[ $old ] : null;
				$existing = Key_Bridge::raw_get( $new, self::missing() );
				$same     = self::missing() !== $existing && maybe_serialize( $existing ) === maybe_serialize( $value );
				if ( $same ) {
					$cat['items'][ $old ] = 'already current';
					$cat['done']++;
					continue;
				}
				$ok = Key_Bridge::raw_update( $new, $value, $autoload );
				if ( $ok ) {
					$cat['items'][ $old ] = self::missing() === $existing ? 'copied' : 'copied (replaced a stale copy)';
					$cat['done']++;
					$copied++;
				} else {
					$cat['items'][ $old ] = 'error: could not write ' . $new;
					$cat['errors']++;
				}
			}
			$cat['status'] = $cat['errors'] ? 'error' : 'done';
			unset( $cat );
		}

		// Prefixed rows (one per menu id) are found by a LIKE on the old prefix.
		global $wpdb;
		$cat = &$categories['prefixes'];
		foreach ( $map['prefixes'] as $old_prefix => $new_prefix ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, autoload FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $old_prefix ) . '%' ) );
			foreach ( (array) $rows as $row ) {
				$cat['total']++;
				$new   = $new_prefix . substr( $row->option_name, strlen( $old_prefix ) );
				$value = Key_Bridge::raw_get( $row->option_name );
				$ok    = Key_Bridge::raw_update( $new, $value, $row->autoload );
				$cat['items'][ $row->option_name ] = $ok ? 'copied' : 'already current';
				$cat['done']++;
				$copied += $ok ? 1 : 0;
			}
		}
		$cat['status'] = 'done';
		unset( $cat );

		return array( 'copied' => $copied, 'categories' => $categories );
	}

	/**
	 * Consent: copy, then flip. Returns the new state or a WP_Error.
	 */
	public static function start( $user_id ) {
		$state = self::state();
		if ( 'complete' === self::status() ) {
			return $state;
		}
		$map    = Key_Bridge::map();
		$result = self::copy();
		$errors = 0;
		foreach ( $result['categories'] as $cat ) {
			$errors += (int) $cat['errors'];
		}

		$state['map_version'] = $map['map_version'];
		$state['consent']     = array(
			'user_id'     => (int) $user_id,
			'time'        => time(),
			'map_version' => $map['map_version'],
			'pro_version' => defined( 'WCF_ADDONS_PRO_VERSION' ) ? WCF_ADDONS_PRO_VERSION : '',
		);
		$state['categories'] = $result['categories'];
		$user                = get_userdata( $user_id );
		self::log( sprintf( 'Migration started by %s (user #%d), Pro %s.', $user ? $user->user_login : 'unknown', $user_id, $state['consent']['pro_version'] ?: 'not installed' ) );

		if ( $errors ) {
			$state['status'] = 'needs_action';
			self::save_state( $state );
			self::log( sprintf( '%d row(s) could not be copied. Nothing was switched; the old names stay live. Press Retry.', $errors ) );
			return new \WP_Error( 'aaeaddon_migration_errors', sprintf( '%d row(s) could not be copied.', $errors ), $state );
		}

		$state['status']      = 'complete';
		$state['finished_at'] = time();
		self::save_state( $state );
		self::log( sprintf( 'Done: %d row(s) copied to the new storage names. The old rows are kept as a live copy.', $result['copied'] ) );

		// Caches that key on the old rows.
		delete_transient( 'aaeaddon_v3_usage' );
		delete_transient( 'aaeaddon_atomic_usage' );
		delete_transient( 'aaeaddon_atomic_usage_count' );

		return $state;
	}

	/* ------------------------------------------------------------------ */
	/* Reporting                                                            */
	/* ------------------------------------------------------------------ */

	private static function empty_categories() {
		return array(
			'options'     => array( 'label' => __( 'Settings', 'animation-addons-for-elementor' ), 'total' => 0, 'done' => 0, 'skipped' => 0, 'errors' => 0, 'status' => 'waiting', 'items' => array() ),
			'options_pro' => array( 'label' => __( 'Pro settings', 'animation-addons-for-elementor' ), 'total' => 0, 'done' => 0, 'skipped' => 0, 'errors' => 0, 'status' => 'waiting', 'items' => array() ),
			'prefixes'    => array( 'label' => __( 'Menu settings', 'animation-addons-for-elementor' ), 'total' => 0, 'done' => 0, 'skipped' => 0, 'errors' => 0, 'status' => 'waiting', 'items' => array() ),
		);
	}

	private static function merge_categories( $stored, $fresh ) {
		foreach ( $fresh as $key => $cat ) {
			if ( ! isset( $stored[ $key ] ) ) {
				$stored[ $key ] = $cat;
				continue;
			}
			foreach ( $cat['items'] as $name => $outcome ) {
				if ( 0 === strpos( $outcome, 'copied' ) || 0 === strpos( $outcome, 'error' ) ) {
					$stored[ $key ]['items'][ $name ] = $outcome;
				}
			}
			$stored[ $key ]['errors'] = $cat['errors'];
			$stored[ $key ]['status'] = $cat['status'];
		}
		return $stored;
	}

	/**
	 * How many old rows exist per group — taken when the page is opened
	 * (awaiting_consent), never on a normal request.
	 */
	public static function counts() {
		$map    = Key_Bridge::map();
		$counts = array();
		foreach ( array( 'options', 'options_pro' ) as $group ) {
			$n = 0;
			foreach ( array_keys( $map[ $group ] ) as $old ) {
				if ( false !== Key_Bridge::raw_get( $old ) ) {
					$n++;
				}
			}
			$counts[ $group ] = $n;
		}
		global $wpdb;
		$n = 0;
		foreach ( array_keys( $map['prefixes'] ) as $old_prefix ) {
			$n += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $old_prefix ) . '%' ) );
		}
		$counts['prefixes'] = $n;
		return $counts;
	}

	public static function pro_info() {
		$installed = defined( 'WCF_ADDONS_PRO_VERSION' );
		return array(
			'installed' => $installed,
			'version'   => $installed ? WCF_ADDONS_PRO_VERSION : '',
			'min'       => self::PRO_COMPAT_VERSION,
			'ok'        => ! $installed || version_compare( WCF_ADDONS_PRO_VERSION, self::PRO_COMPAT_VERSION, '>=' ),
			'licensed'  => 'valid' === Key_Bridge::raw_get( 'wcf_addon_sl_license_status' ),
		);
	}

	/** Everything the Migration screen renders. */
	public static function payload( $with_counts = false ) {
		$state = self::state();
		$map   = Key_Bridge::map();
		$out   = array(
			'status'      => self::status(),
			'site'        => isset( $state['site'] ) ? $state['site'] : '',
			'from'        => isset( $state['from'] ) ? $state['from'] : '',
			'map_version' => $map['map_version'],
			'version'     => AAEADDON_VERSION,
			'decided_at'  => isset( $state['decided_at'] ) ? $state['decided_at'] : null,
			'finished_at' => isset( $state['finished_at'] ) ? $state['finished_at'] : null,
			'consent'     => isset( $state['consent'] ) ? $state['consent'] : null,
			'backup'      => isset( $state['backup'] ) ? $state['backup'] : array( 'exported_at' => null ),
			'categories'  => isset( $state['categories'] ) ? $state['categories'] : self::empty_categories(),
			'kept'        => array(
				'postmeta'   => count( $map['postmeta'] ),
				'termmeta'   => count( $map['termmeta'] ),
				'usermeta'   => count( $map['usermeta'] ),
				'transients' => count( $map['transients'] ),
				'cron'       => count( $map['cron'] ),
				'tables'     => count( $map['tables'] ),
			),
			'pro'         => self::pro_info(),
			'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'page_url'    => admin_url( self::PAGE_URL ),
			'plugins_url' => admin_url( 'plugins.php' ),
			'log'         => $with_counts ? self::get_log() : array(),
		);
		if ( $with_counts && 'awaiting_consent' === $out['status'] ) {
			$out['found'] = self::counts();
		}
		return $out;
	}

	/** The plain-text report a support ticket starts with. */
	public static function report() {
		$state = self::state();
		$lines = array();
		$lines[] = 'Animation Addons — storage-name migration report';
		$lines[] = 'Site: ' . home_url();
		$lines[] = 'Generated: ' . gmdate( 'c' );
		$lines[] = 'Animation Addons: ' . AAEADDON_VERSION . ' · Pro: ' . ( defined( 'WCF_ADDONS_PRO_VERSION' ) ? WCF_ADDONS_PRO_VERSION : 'not installed' ) . ' · WordPress: ' . get_bloginfo( 'version' ) . ' · PHP: ' . PHP_VERSION;
		$lines[] = 'Status: ' . self::status() . ( isset( $state['site'] ) ? ' (' . $state['site'] . ' site' . ( ! empty( $state['from'] ) ? ', updated from ' . $state['from'] : '' ) . ')' : '' );
		if ( ! empty( $state['consent'] ) ) {
			$c = $state['consent'];
			$lines[] = sprintf( 'Consent: user #%d at %s, map version %s, Pro %s', $c['user_id'], gmdate( 'c', $c['time'] ), $c['map_version'], $c['pro_version'] ?: 'none' );
		}
		$lines[] = '';
		if ( ! empty( $state['categories'] ) ) {
			foreach ( $state['categories'] as $key => $cat ) {
				$lines[] = sprintf( '[%s] %s — %d/%d done, %d skipped, %d errors, status %s', $key, $cat['label'], $cat['done'], $cat['total'], $cat['skipped'], $cat['errors'], $cat['status'] );
				foreach ( $cat['items'] as $name => $outcome ) {
					$lines[] = '    ' . $name . ' => ' . $outcome;
				}
			}
			$lines[] = '';
		}
		$lines[] = 'Log:';
		foreach ( self::get_log() as $entry ) {
			$lines[] = '  ' . gmdate( 'c', $entry[0] ) . '  ' . $entry[1];
		}
		return implode( "\n", $lines ) . "\n";
	}

	/** The backup: the map plus the current value of every OLD row on this site. */
	public static function export() {
		$map  = Key_Bridge::map();
		$rows = array();
		foreach ( array( 'options', 'options_pro' ) as $group ) {
			foreach ( $map[ $group ] as $old => $new ) {
				$value = Key_Bridge::raw_get( $old, self::missing() );
				if ( self::missing() !== $value ) {
					$rows[ $old ] = $value;
				}
			}
		}
		global $wpdb;
		foreach ( array_keys( $map['prefixes'] ) as $old_prefix ) {
			$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $old_prefix ) . '%' ) );
			foreach ( (array) $names as $name ) {
				$rows[ $name ] = Key_Bridge::raw_get( $name );
			}
		}
		return array(
			'format'      => 'aaeaddon-key-backup',
			'map_version' => $map['map_version'],
			'exported_at' => gmdate( 'c' ),
			'site'        => home_url(),
			'version'     => AAEADDON_VERSION,
			'options'     => $rows,
		);
	}

	/**
	 * Restore a backup: every key in it that the map knows is written to the
	 * LIVE row (and mirrored). Keys the map does not know are ignored.
	 *
	 * @return array{restored:int, ignored:int}|\WP_Error
	 */
	public static function import( $data ) {
		if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) || ( isset( $data['format'] ) && 'aaeaddon-key-backup' !== $data['format'] ) ) {
			return new \WP_Error( 'aaeaddon_bad_backup', __( 'That file is not an Animation Addons backup.', 'animation-addons-for-elementor' ) );
		}
		$restored = 0;
		$ignored  = 0;
		foreach ( $data['options'] as $name => $value ) {
			if ( ! is_string( $name ) || ! Key_Bridge::is_mapped( $name ) ) {
				$ignored++;
				continue;
			}
			Key_Bridge::update_option( $name, $value );
			$restored++;
		}
		self::log( sprintf( 'Backup restored by user #%d: %d key(s) written, %d ignored.', get_current_user_id(), $restored, $ignored ) );
		return array( 'restored' => $restored, 'ignored' => $ignored );
	}

	/* ------------------------------------------------------------------ */
	/* AJAX                                                                 */
	/* ------------------------------------------------------------------ */

	private static function guard() {
		Nonce::check_ajax( self::NONCE, 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'animation-addons-for-elementor' ) ), 403 );
		}
	}

	public static function ajax_status() {
		self::guard();
		wp_send_json_success( self::payload( true ) );
	}

	public static function ajax_start() {
		self::guard();
		$result = self::start( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message(), 'migration' => self::payload( true ) ) );
		}
		wp_send_json_success( self::payload( true ) );
	}

	public static function ajax_export() {
		self::guard();
		$state = self::state();
		if ( ! empty( $state ) ) {
			$state['backup'] = array( 'exported_at' => time() );
			self::save_state( $state );
		}
		self::log( sprintf( 'Backup downloaded by user #%d.', get_current_user_id() ) );
		wp_send_json_success( array(
			'filename' => 'animation-addons-backup-' . gmdate( 'Ymd-His' ) . '.json',
			'backup'   => self::export(),
		) );
	}

	public static function ajax_import() {
		self::guard();
		$raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, decoded and validated below.
		if ( ! is_string( $raw ) || strlen( $raw ) > 2 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'The backup file is too large or unreadable.', 'animation-addons-for-elementor' ) ) );
		}
		$data   = json_decode( $raw, true );
		$result = self::import( $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array_merge( $result, array( 'migration' => self::payload( true ) ) ) );
	}

	public static function ajax_report() {
		self::guard();
		wp_send_json_success( array(
			'filename' => 'animation-addons-migration-report-' . gmdate( 'Ymd-His' ) . '.txt',
			'report'   => self::report(),
		) );
	}

	/* ------------------------------------------------------------------ */
	/* Menu, notices, plugin rows                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Always registered — a person restoring an old database backup months
	 * later, or installing an old Pro, must be able to reach the tool
	 * without knowing a URL. Only the NOTICE is gated on the status.
	 */
	public static function register_menu() {
		add_submenu_page(
			'aaeaddon_page',
			esc_html__( 'Storage Migration', 'animation-addons-for-elementor' ),
			esc_html__( 'Migration', 'animation-addons-for-elementor' ),
			self::CAP,
			self::PAGE_URL,
			'',
			2
		);
	}

	public static function highlight_menu( $submenu_file ) {
		if ( isset( $_GET['page'], $_GET['tab'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'aaeaddon_settings' === $_GET['page'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'migration' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::PAGE_URL;
		}
		return $submenu_file;
	}

	public static function inject_dashboard_config( $configs ) {
		if ( is_array( $configs ) ) {
			$configs['migration'] = self::payload( true );
		}
		return $configs;
	}

	/**
	 * Two notices, never both:
	 *  - while the migration is pending: "…items are waiting" → the page;
	 *  - once complete, while Pro predates the compat release: update Pro.
	 * Through the Notices framework, which limits non-error notices to the
	 * plugin's own screens (WordPress.org guideline 11); the Plugins screen
	 * gets the plugin-row line instead.
	 */
	public static function register_notices() {
		if ( ! class_exists( '\Wealcoder\AnimationAddons\Admin\Notices\Notices' ) ) {
			return;
		}
		$notices = \Wealcoder\AnimationAddons\Admin\Notices\Notices::instance();
		$status  = self::status();

		if ( 'awaiting_consent' === $status || 'needs_action' === $status ) {
			$notices->add( array(
				'notice_id'   => 'aaeaddon_migration_pending',
				'type'        => 'warning',
				'dismissible' => false,
				'capability'  => self::CAP,
				'message'     => sprintf(
					'<p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a> <a href="#" class="button-link" data-snooze="%5$d" style="margin-left:8px">%6$s</a></p>',
					esc_html__( 'Animation Addons moved its settings to new storage names.', 'animation-addons-for-elementor' ),
					'needs_action' === $status
						? esc_html__( 'Your site is working normally. One step needs your attention on the Migration screen.', 'animation-addons-for-elementor' )
						: esc_html__( 'Your site is working normally. Nothing is copied until you start the migration; your current data is never changed or deleted.', 'animation-addons-for-elementor' ),
					esc_url( admin_url( self::PAGE_URL ) ),
					esc_html__( 'Open migration', 'animation-addons-for-elementor' ),
					DAY_IN_SECONDS,
					esc_html__( 'Remind me tomorrow', 'animation-addons-for-elementor' )
				),
			) );
			return;
		}

		$pro = self::pro_info();
		if ( 'complete' === $status && $pro['installed'] && ! $pro['ok'] && current_user_can( 'update_plugins' ) ) {
			$target = $pro['licensed'] ? admin_url( 'plugins.php' ) : admin_url( 'admin.php?page=aaeaddon_settings&tab=dashboard' );
			$notices->add( array(
				'notice_id'   => 'aaeaddon_pro_update_' . $pro['version'],
				'type'        => 'info',
				'dismissible' => false,
				'capability'  => 'update_plugins',
				'message'     => sprintf(
					'<p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a> <a href="#" class="button-link" data-snooze="%5$d" style="margin-left:8px">%6$s</a></p>',
					sprintf(
						/* translators: 1: installed Pro version, 2: required Pro version */
						esc_html__( 'Animation Addons Pro %1$s is older than this version of Animation Addons expects (%2$s).', 'animation-addons-for-elementor' ),
						esc_html( $pro['version'] ),
						esc_html( self::PRO_COMPAT_VERSION )
					),
					$pro['licensed']
						? esc_html__( 'Your site works, but Pro is running through a compatibility layer. Update Pro to finish the move.', 'animation-addons-for-elementor' )
						: esc_html__( 'Your site works, but Pro is running through a compatibility layer. Activate your Pro licence to receive the update.', 'animation-addons-for-elementor' ),
					esc_url( $target ),
					$pro['licensed'] ? esc_html__( 'Update now', 'animation-addons-for-elementor' ) : esc_html__( 'Open licence settings', 'animation-addons-for-elementor' ),
					7 * DAY_IN_SECONDS,
					esc_html__( 'Remind me in a week', 'animation-addons-for-elementor' )
				),
			) );
		}
	}

	/**
	 * The line under the plugin on the Plugins screen — the WordPress-native
	 * "you just updated this, here is the next step". Shown while pending.
	 */
	public static function plugin_row( $plugin_file ) {
		// The Network Admin's plugin list is one screen for every site, and the
		// migration state is per site: a row there could only report the main
		// site's. Say so instead of implying the network is done or pending.
		if ( is_multisite() && is_network_admin() ) {
			self::network_plugin_row();
			return;
		}
		$status = self::status();
		if ( 'awaiting_consent' !== $status && 'needs_action' !== $status ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$colspan = 4;
		printf(
			'<tr class="plugin-update-tr active aaeaddon-migration-row" data-plugin="%1$s"><td colspan="%2$d" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>%3$s <a href="%4$s">%5$s</a></p></div></td></tr>',
			esc_attr( $plugin_file ),
			(int) $colspan,
			'needs_action' === $status
				? esc_html__( 'Animation Addons: the storage-name migration needs your attention.', 'animation-addons-for-elementor' )
				: esc_html__( 'Animation Addons: settings are ready to move to new storage names. Nothing changes until you start it.', 'animation-addons-for-elementor' ),
			esc_url( admin_url( self::PAGE_URL ) ),
			esc_html__( 'Open migration', 'animation-addons-for-elementor' )
		);
	}

	/**
	 * The Network Admin variant of plugin_row(): one line pointing at the
	 * per-site screens, shown only while at least one site is still pending.
	 */
	private static function network_plugin_row() {
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}
		$pending = 0;
		foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
			switch_to_blog( (int) $site_id );
			$status = self::status();
			restore_current_blog();
			if ( in_array( $status, array( 'awaiting_consent', 'needs_action' ), true ) ) {
				$pending++;
			}
		}
		if ( 0 === $pending ) {
			return;
		}
		printf(
			'<tr class="plugin-update-tr"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice inline notice-info notice-alt"><p>%s</p></div></td></tr>',
			esc_html(
				sprintf(
					/* translators: %d: number of sites */
					_n(
						'Animation Addons: %d site still has its storage-name migration pending. It runs per site -- open that site\'s Animation Addons &rsaquo; Migration screen.',
						'Animation Addons: %d sites still have their storage-name migration pending. It runs per site -- open each site\'s Animation Addons &rsaquo; Migration screen.',
						$pending,
						'animation-addons-for-elementor'
					),
					$pending
				)
			)
		);
	}
}


