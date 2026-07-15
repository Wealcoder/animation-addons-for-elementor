<?php
/**
 * AAE Forms — database layer.
 *
 * All six spec tables: identity/schema (Milestone 4) + submissions,
 * submission_values, action_jobs, action_logs (Milestone 6). The job/log
 * tables are created now but only written from Milestone 7+ (email action,
 * queue) — creating them together keeps migrate() append-only.
 *
 * Migration is version-gated on `init` (cheap option read) instead of a
 * plugin-activation hook, so tables also appear after a plugin update or
 * a manual file deploy without re-activation.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Database {

	const DB_VERSION = '2';
	const OPTION_KEY = 'aae_forms_db_version';

	public static function forms_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_forms';
	}

	public static function schemas_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_form_schemas';
	}

	public static function submissions_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_submissions';
	}

	public static function submission_values_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_submission_values';
	}

	public static function action_jobs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_action_jobs';
	}

	public static function action_logs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'aae_action_logs';
	}

	/** Create/upgrade tables when DB_VERSION moves. Hooked on init. */
	public static function migrate(): void {
		if ( get_option( self::OPTION_KEY ) === self::DB_VERSION ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$forms           = self::forms_table();
		$schemas         = self::schemas_table();

		dbDelta(
			"CREATE TABLE {$forms} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_key VARCHAR(64) NOT NULL,
			post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			element_id VARCHAR(32) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY form_key (form_key),
			KEY post_id (post_id)
		) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$schemas} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			version INT UNSIGNED NOT NULL DEFAULT 1,
			schema_hash CHAR(32) NOT NULL,
			schema_json LONGTEXT NOT NULL,
			source VARCHAR(20) NOT NULL DEFAULT 'elements',
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY form_version (form_id, version)
		) {$charset_collate};"
		);

		// --- Milestone 6: submission storage + (M7/M10) action queue. -------

		$submissions = self::submissions_table();
		$values      = self::submission_values_table();
		$jobs        = self::action_jobs_table();
		$logs        = self::action_logs_table();

		dbDelta(
			"CREATE TABLE {$submissions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			form_key VARCHAR(64) NOT NULL,
			schema_version INT UNSIGNED NOT NULL DEFAULT 1,
			status VARCHAR(20) NOT NULL DEFAULT 'new',
			source_url TEXT NULL,
			referrer_url TEXT NULL,
			utm_json TEXT NULL,
			ip_hash CHAR(32) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY form_key (form_key),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$values} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			field_key VARCHAR(191) NOT NULL DEFAULT '',
			field_label TEXT NULL,
			field_type VARCHAR(20) NOT NULL DEFAULT '',
			field_value LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY submission_id (submission_id),
			KEY field_key (field_key)
		) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$jobs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			action_type VARCHAR(40) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts INT UNSIGNED NOT NULL DEFAULT 0,
			next_run_at DATETIME NULL,
			payload_json LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY submission_id (submission_id),
			KEY status_next (status, next_run_at)
		) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			submission_id BIGINT UNSIGNED NOT NULL,
			action_type VARCHAR(40) NOT NULL,
			status VARCHAR(20) NOT NULL,
			message TEXT NULL,
			request_snapshot LONGTEXT NULL,
			response_snapshot LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY job_id (job_id),
			KEY submission_id (submission_id)
		) {$charset_collate};"
		);

		update_option( self::OPTION_KEY, self::DB_VERSION );
	}

	/**
	 * Uninstall cleanup — called from the plugin's uninstall hook.
	 *
	 * Submissions are leads ("never lose a lead"), so the tables are
	 * dropped ONLY when the site owner explicitly opted in beforehand:
	 *
	 *     update_option( 'aae_forms_delete_data_on_uninstall', 1 );
	 *
	 * A plain uninstall clears cron + housekeeping options and keeps
	 * every form, schema, and submission intact for a future reinstall.
	 */
	public static function uninstall(): void {
		// Literal hook names — the Queue class may not be autoloadable
		// in the minimal uninstall context.
		wp_clear_scheduled_hook( 'aae_form/process_queue' );
		wp_clear_scheduled_hook( 'aae_form/process_queue_sweep' );

		if ( get_option( 'aae_forms_delete_data_on_uninstall' ) ) {
			global $wpdb;

			// Children before parents (values/logs reference submissions/jobs).
			$tables = [
				self::submission_values_table(),
				self::action_logs_table(),
				self::action_jobs_table(),
				self::submissions_table(),
				self::schemas_table(),
				self::forms_table(),
			];

			foreach ( $tables as $table ) {
				$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- schema teardown at uninstall; table names come from our own prefix helpers.
			}

			delete_option( 'aae_forms_delete_data_on_uninstall' );
		}

		delete_option( self::OPTION_KEY );
	}
}
