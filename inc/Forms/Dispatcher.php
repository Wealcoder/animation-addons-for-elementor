<?php
/**
 * AAE Forms — after-submit action dispatcher (Milestone 7).
 *
 * Bridges the submit pipeline to the Queue: on `aae_form/submission_saved`
 * (fired AFTER storage — save-before-actions) it decides which actions the
 * form wants, enqueues one self-contained job each, and drains the queue on
 * `shutdown` — attempt 1 runs in this request but after the visitor's 200
 * is already generated, retries ride WP-Cron.
 *
 * Which actions run (registry-driven — every type in Actions\Registry,
 * including pro/filter-added ones like Brevo, is enqueueable):
 *  - admin_email: on by default whenever behavior includes email
 *    ('store_email' / 'email') — the spec's never-lose-a-lead default.
 *  - every other registered action: off unless actions_json enables it.
 *  - actions_json (hidden form prop, JSON object keyed by action type,
 *    each value `{ "enabled": bool, ...settings }`) overrides the defaults
 *    and carries per-action settings (to/cc/bcc/reply_to/subject/body/…).
 *    Enable flags are honored only for types the registry knows.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

use WCF_ADDONS\Forms\Actions\Admin_Email;
use WCF_ADDONS\Forms\Actions\Webhook;
use WCF_ADDONS\Forms\Actions\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dispatcher {

	public static function init(): void {
		add_action( 'aae_form/submission_saved', [ self::class, 'on_submission_saved' ], 10, 5 );
		add_action( Queue::CRON_HOOK, [ Queue::class, 'process_due' ] );
		add_action( 'init', [ self::class, 'ensure_sweeper' ], 20 );
	}

	/** Hourly safety net for retries whose single cron event got lost. */
	public static function ensure_sweeper(): void {
		if ( ! wp_next_scheduled( Queue::CRON_HOOK . '_sweep' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', Queue::CRON_HOOK . '_sweep' );
		}
		add_action( Queue::CRON_HOOK . '_sweep', [ Queue::class, 'process_due' ] );
	}

	/** `aae_form/submission_saved` callback — enqueue this form's actions. */
	public static function on_submission_saved( $submission_id, $form_key, $clean, $schema, $meta ): void {
		if ( ! is_array( $clean ) || ! is_array( $schema ) ) {
			return;
		}

		$jobs = self::build_jobs( (int) $submission_id, (string) $form_key, $clean, $schema, (array) $meta );

		if ( ! $jobs ) {
			return;
		}

		foreach ( $jobs as $job ) {
			Queue::enqueue( (int) $submission_id, $job['type'], $job['payload'] );
		}

		self::arm_immediate_run();
	}

	/**
	 * @return array[] [ [ 'type' => string, 'payload' => array ], … ]
	 */
	public static function build_jobs( int $submission_id, string $form_key, array $clean, array $schema, array $meta ): array {
		$behavior = (string) ( $schema['settings']['behavior'] ?? 'store_email' );
		$config   = self::parse_actions_json( (string) ( $schema['settings']['actions_json'] ?? '' ) );

		// Registry-driven so pro/filter-added actions (Brevo, Mailchimp, …)
		// are enqueueable too — NOT a hardcoded core-three list. Every
		// registered type starts OFF except the two core defaults below;
		// actions_json then opts any of them in/out.
		$enabled = array_fill_keys( array_keys( Registry::all() ), false );

		// Default ON when behavior includes email; actions_json can veto.
		$enabled[ Admin_Email::type() ] = in_array( $behavior, [ 'store_email', 'email' ], true );

		foreach ( $config as $type => $settings ) {
			// Only honor enable flags for types the registry actually knows —
			// a stale/unknown actions_json key must not mint an unrunnable job.
			if ( isset( $settings['enabled'] ) && array_key_exists( $type, $enabled ) ) {
				$enabled[ $type ] = (bool) $settings['enabled'];
			}
		}

		// A webhook without a URL can never succeed — don't mint doomed jobs.
		if ( ! empty( $enabled[ Webhook::type() ] ) && '' === trim( (string) ( $config[ Webhook::type() ]['url'] ?? '' ) ) ) {
			$enabled[ Webhook::type() ] = false;
		}

		$context = self::build_context( $submission_id, $form_key, $clean, $schema, $meta );
		$jobs    = [];

		foreach ( $enabled as $type => $on ) {
			if ( ! $on ) {
				continue;
			}

			$settings = (array) ( $config[ $type ] ?? [] );
			unset( $settings['enabled'] );

			$jobs[] = [
				'type'    => $type,
				'payload' => [
					'settings' => $settings,
					'context'  => $context,
				],
			];
		}

		return $jobs;
	}

	/** The Smart_Tags context — everything a job needs, frozen at submit time. */
	private static function build_context( int $submission_id, string $form_key, array $clean, array $schema, array $meta ): array {
		$labels = [];
		$types  = [];

		foreach ( (array) ( $schema['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) || '' === (string) ( $field['key'] ?? '' ) ) {
				continue;
			}
			$key = (string) $field['key'];
			if ( ! isset( $labels[ $key ] ) ) {
				$labels[ $key ] = (string) ( $field['label'] ?? '' );
				$types[ $key ]  = (string) ( $field['type'] ?? '' );
			}
		}

		return [
			'submission_id' => $submission_id,
			'form_key'      => $form_key,
			'fields'        => $clean,
			'labels'        => $labels,
			'types'         => $types,
			'page_url'      => (string) ( $meta['source_url'] ?? '' ),
			'page_title'    => self::page_title( (string) ( $meta['source_url'] ?? '' ) ),
			'submitted_at'  => current_time( 'mysql' ),
		];
	}

	private static function page_title( string $source_url ): string {
		if ( '' === $source_url || ! function_exists( 'url_to_postid' ) ) {
			return '';
		}

		$post_id = url_to_postid( $source_url );

		return $post_id > 0 ? (string) get_the_title( $post_id ) : '';
	}

	/** Parse actions_json → [ type => settings ], defensively. */
	private static function parse_actions_json( string $actions_json ): array {
		if ( '' === trim( $actions_json ) ) {
			return [];
		}

		$decoded = json_decode( $actions_json, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$config = [];
		foreach ( $decoded as $type => $settings ) {
			if ( is_string( $type ) && is_array( $settings ) ) {
				$config[ $type ] = $settings;
			}
		}

		return $config;
	}

	/** Drain the queue once, after the visitor's response (attempt 1 immediate). */
	private static function arm_immediate_run(): void {
		static $armed = false;

		if ( $armed ) {
			return;
		}
		$armed = true;

		add_action( 'shutdown', [ Queue::class, 'process_due' ], 100 );
	}
}
