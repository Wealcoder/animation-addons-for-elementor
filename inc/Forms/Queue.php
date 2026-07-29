<?php
/**
 * AAE Forms — async action queue (Milestone 7).
 *
 * Custom-table queue over aae_action_jobs + aae_action_logs (the tables
 * Milestone 6 created), WP-Cron for retries. Chosen over Action Scheduler
 * (heavy vendored dependency) and raw WP-Cron-only (no observability) —
 * the job table IS the admin-facing log source for M9/M10.
 *
 * Spec contract implemented exactly:
 *  - states: pending / processing / success / failed / retrying / cancelled
 *  - retry schedule: attempt 1 immediate (Dispatcher runs the queue on
 *    shutdown, after the visitor's response is sent) → +5 min → +30 min →
 *    +2 h → failed, awaiting a manual retry (M9 UI)
 *  - every attempt writes an aae_action_logs row with redacted snapshots
 *  - jobs are self-contained (payload_json carries settings + context), so
 *    they survive form edits and behavior='email' (submission_id = 0)
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

use WCF_ADDONS\Forms\Actions\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- queries interpolate only the internal aae_action_jobs table name; every value goes through $wpdb->prepare().

final class Queue {

	const STATUS_PENDING    = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS    = 'success';
	const STATUS_FAILED     = 'failed';
	const STATUS_RETRYING   = 'retrying';
	const STATUS_CANCELLED  = 'cancelled';

	/** Cron hook that drains due jobs (single events + hourly sweeper). */
	const CRON_HOOK = 'aae_form/process_queue';

	/** Seconds until the NEXT attempt after failed attempt 1, 2, 3. */
	const RETRY_DELAYS = [ 5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 2 * HOUR_IN_SECONDS ];

	/** Attempt 1 + count(RETRY_DELAYS) retries. */
	const MAX_ATTEMPTS = 4;

	/** Jobs drained per process_due() call. */
	const BATCH_SIZE = 20;

	/** Insert a pending job; returns the job id (0 on failure). */
	public static function enqueue( int $submission_id, string $action_type, array $payload ): int {
		global $wpdb;
		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			Database::action_jobs_table(),
			[
				'submission_id' => $submission_id,
				'action_type'   => $action_type,
				'status'        => self::STATUS_PENDING,
				'attempts'      => 0,
				'next_run_at'   => $now,
				'payload_json'  => wp_json_encode( $payload ),
				'created_at'    => $now,
				'updated_at'    => $now,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
		);

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/** Run every due pending/retrying job (bounded batch). Cron + shutdown callback. */
	public static function process_due(): void {
		global $wpdb;
		$table = Database::action_jobs_table();
		$now   = current_time( 'mysql' );

		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
			 WHERE status IN ('pending','retrying') AND (next_run_at IS NULL OR next_run_at <= %s)
			 ORDER BY id ASC LIMIT %d",
				$now,
				self::BATCH_SIZE
			)
		);

		foreach ( (array) $jobs as $job ) {
			self::run_job( $job );
		}
	}

	/** Manual retry (M9 admin UI / admin REST): requeue a failed job now. */
	public static function retry( int $job_id ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			Database::action_jobs_table(),
			[
				'status'      => self::STATUS_RETRYING,
				'next_run_at' => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			],
			[
				'id'     => $job_id,
				'status' => self::STATUS_FAILED,
			],
			[ '%s', '%s', '%s' ],
			[ '%d', '%s' ]
		);

		if ( $updated ) {
			self::schedule_run( time() + 1 );
		}

		return (bool) $updated;
	}

	/** Claim, execute, and settle one job row. */
	private static function run_job( object $job ): void {
		global $wpdb;
		$table = Database::action_jobs_table();

		// Optimistic claim — an UPDATE guarded by the current status; 0 rows
		// means another worker (cron overlapping shutdown) took it.
		$claimed = $wpdb->update(
			$table,
			[
				'status'     => self::STATUS_PROCESSING,
				'updated_at' => current_time( 'mysql' ),
			],
			[
				'id'     => (int) $job->id,
				'status' => (string) $job->status,
			],
			[ '%s', '%s' ],
			[ '%d', '%s' ]
		);

		if ( ! $claimed ) {
			return;
		}

		$payload = json_decode( (string) $job->payload_json, true );
		$action  = Registry::get( (string) $job->action_type );

		if ( null === $action || ! is_array( $payload ) ) {
			// Unrunnable forever — no retry loop for corrupt/unknown jobs.
			self::settle(
				$job,
				self::MAX_ATTEMPTS,
				[
					'success'  => false,
					'message'  => null === $action
						? sprintf( 'Unknown action type "%s"', $job->action_type )
						: 'Corrupt job payload',
					'request'  => [],
					'response' => [],
				]
			);
			return;
		}

		try {
			$result = $action->run( $payload );
		} catch ( \Throwable $e ) {
			$result = [
				'success'  => false,
				'message'  => 'Action threw: ' . $e->getMessage(),
				'request'  => [],
				'response' => [],
			];
		}

		self::settle( $job, (int) $job->attempts + 1, $result );
	}

	/** Write the job's post-attempt state + its log row; arm the retry cron. */
	private static function settle( object $job, int $attempts, array $result ): void {
		global $wpdb;
		$now     = current_time( 'mysql' );
		$success = ! empty( $result['success'] );

		if ( $success ) {
			$status      = self::STATUS_SUCCESS;
			$next_run_at = null;
		} elseif ( $attempts >= self::MAX_ATTEMPTS ) {
			$status      = self::STATUS_FAILED; // manual Retry button from here (M9).
			$next_run_at = null;
		} else {
			$status = self::STATUS_RETRYING;
			// NOT end(self::RETRY_DELAYS): end() takes its argument by
			// reference, so a class constant fatals the moment the fallback
			// branch runs (attempts beyond the delay table).
			$delay       = self::RETRY_DELAYS[ $attempts - 1 ] ?? self::RETRY_DELAYS[ count( self::RETRY_DELAYS ) - 1 ];
			$next_run_at = gmdate( 'Y-m-d H:i:s', strtotime( $now ) + $delay );
			self::schedule_run( time() + $delay );
		}

		$wpdb->update(
			Database::action_jobs_table(),
			[
				'status'      => $status,
				'attempts'    => $attempts,
				'next_run_at' => $next_run_at,
				'updated_at'  => $now,
			],
			[ 'id' => (int) $job->id ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);

		$wpdb->insert(
			Database::action_logs_table(),
			[
				'job_id'            => (int) $job->id,
				'submission_id'     => (int) $job->submission_id,
				'action_type'       => (string) $job->action_type,
				'status'            => $success ? self::STATUS_SUCCESS : $status,
				'message'           => (string) ( $result['message'] ?? '' ),
				'request_snapshot'  => wp_json_encode( $result['request'] ?? [] ),
				'response_snapshot' => wp_json_encode( $result['response'] ?? [] ),
				'created_at'        => $now,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Single cron event at $timestamp. Always requested — WP itself rejects
	 * duplicates of the same hook within ±10 minutes, and distinct retry
	 * horizons (5m/30m/2h) NEED separate events: an earlier pending event
	 * fires, gets consumed, and would leave later retries waiting for the
	 * hourly sweeper.
	 */
	private static function schedule_run( int $timestamp ): void {
		wp_schedule_single_event( $timestamp, self::CRON_HOOK );
	}
}
