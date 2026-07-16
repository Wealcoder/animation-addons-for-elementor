<?php
/**
 * AAE Forms — spam/security log (Milestone 8, Bot Shield).
 *
 * Every blocked submit attempt is recorded with its REAL reason for admins
 * (spec hard rule: never hide security failures — even when the visitor
 * gets a safe generic message, or a deliberate fake success for honeypot
 * trips). Blocked attempts never become submissions ("zero clean
 * submissions, zero action jobs — only a spam/security log entry").
 *
 * Rows live in aae_action_logs with action_type 'bot_shield' and
 * job_id/submission_id 0 — the M9 dashboard filters them out of action
 * history and into the Spam Log view. Snapshots stay privacy-safe: hashed
 * visitor signal, no field values, no raw IP.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Spam_Log {

	const ACTION_TYPE = 'bot_shield';

	/**
	 * @param string $form_key Target form.
	 * @param string $reason   Machine reason: honeypot | rate_limited |
	 *                         token_invalid | token_replay | too_fast | bad_nonce.
	 * @param array  $extra    Additional snapshot keys (source_url, …).
	 */
	public static function record( string $form_key, string $reason, array $extra = [] ): void {
		global $wpdb;

		$snapshot = array_merge(
			[
				'form_key'   => $form_key,
				'ip_hash'    => Token::visitor_hash(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
			],
			$extra
		);

		$wpdb->insert(
			Database::action_logs_table(),
			[
				'job_id'            => 0,
				'submission_id'     => 0,
				'action_type'       => self::ACTION_TYPE,
				'status'            => 'blocked',
				'message'           => $reason,
				'request_snapshot'  => wp_json_encode( $snapshot ),
				'response_snapshot' => '',
				'created_at'        => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}
}
