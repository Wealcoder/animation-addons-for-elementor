<?php
/**
 * AAE Forms — action interface (Milestone 7).
 *
 * The spec's action registry contract: every after-submit action (admin
 * email, auto reply, webhook, …) implements this one shape and runs through
 * the async Queue — never inline in the submit request. run() receives the
 * job's self-contained payload and reports success plus loggable snapshots;
 * the Queue owns attempts/retries/logging, actions own only their work.
 *
 * Register additional actions via the `aae_form/actions` filter (see
 * Registry).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Action_Base {

	/** Unique action type id — stored in aae_action_jobs.action_type. */
	abstract public static function type(): string;

	/**
	 * Execute against a job payload:
	 *   [ 'settings' => per-action config, 'context' => submission context
	 *     (fields/labels/types/submission_id/page_title/page_url/submitted_at) ]
	 *
	 * @return array [
	 *   'success'  => bool,
	 *   'message'  => string  human-readable outcome for the action log,
	 *   'request'  => array   redacted request snapshot for the log,
	 *   'response' => array   response snapshot for the log,
	 * ]
	 */
	abstract public function run( array $payload ): array;
}
