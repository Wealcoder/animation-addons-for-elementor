<?php
/**
 * AAE Forms — action registry (Milestone 7).
 *
 * Single lookup used by the Queue to resolve a job's action_type into a
 * runnable Action_Base. Later milestones (webhook, Sheets, Telegram, …)
 * and the pro plugin add theirs via the `aae_form/actions` filter instead
 * of hardcoding — the spec's "adapters, not one-off code" rule.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Registry {

	/** @return Action_Base[] keyed by action type. */
	public static function all(): array {
		static $actions = null;

		if ( null === $actions ) {
			$actions = apply_filters(
				'aae_form/actions',
				[
					Admin_Email::type() => new Admin_Email(),
					Auto_Reply::type()  => new Auto_Reply(),
					Webhook::type()     => new Webhook(),
				]
			);
		}

		return $actions;
	}

	public static function get( string $type ): ?Action_Base {
		$action = self::all()[ $type ] ?? null;

		return $action instanceof Action_Base ? $action : null;
	}
}
