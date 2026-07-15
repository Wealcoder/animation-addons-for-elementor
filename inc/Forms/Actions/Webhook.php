<?php
/**
 * AAE Forms — Basic Webhook action (Milestone 10, Free/P1).
 *
 * POSTs the whole submission as JSON to one admin-configured URL. Free-tier
 * scope per spec: URL + all fields, nothing else — custom headers/auth/
 * field-mapping/conditions are the Pro "Advanced Webhook".
 *
 * Success = any 2xx from the receiver; anything else (or a transport
 * error) fails the attempt and rides the Queue's retry schedule. Log
 * snapshots stay privacy-lean: field KEYS are recorded, values are not.
 *
 * Off by default; enabled via actions_json:
 *   { "webhook": { "enabled": true, "url": "https://…" } }
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webhook extends Action_Base {

	const TIMEOUT = 15;

	public static function type(): string {
		return 'webhook';
	}

	public function run( array $payload ): array {
		$settings = (array) ( $payload['settings'] ?? [] );
		$context  = (array) ( $payload['context'] ?? [] );

		$url = esc_url_raw( trim( (string) ( $settings['url'] ?? '' ) ) );

		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			// Config error — retrying can't fix it; Queue fails it for the
			// admin to see (and Retry after fixing the URL in the editor
			// won't help old jobs — their payload is frozen — so be loud).
			return [
				'success'  => false,
				'message'  => 'No valid webhook URL configured',
				'request'  => [ 'url' => $url ],
				'response' => [],
			];
		}

		$body = self::build_body( $context );

		$response = wp_remote_post(
			$url,
			[
				'timeout'   => self::TIMEOUT,
				'headers'   => [ 'Content-Type' => 'application/json; charset=utf-8' ],
				'body'      => wp_json_encode( $body ),
				'sslverify' => apply_filters( 'aae_form/webhook_sslverify', true ),
			]
		);

		$request_snapshot = [
			'url'    => $url,
			// Keys only — no lead PII in the log table.
			'fields' => array_keys( (array) ( $context['fields'] ?? [] ) ),
		];

		if ( is_wp_error( $response ) ) {
			return [
				'success'  => false,
				'message'  => 'Webhook transport error: ' . $response->get_error_message(),
				'request'  => $request_snapshot,
				'response' => [],
			];
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$success = $code >= 200 && $code < 300;

		return [
			'success'  => $success,
			'message'  => $success
				? sprintf( 'Webhook delivered (HTTP %d)', $code )
				: sprintf( 'Webhook receiver answered HTTP %d', $code ),
			'request'  => $request_snapshot,
			'response' => [
				'code' => $code,
				'body' => mb_substr( (string) wp_remote_retrieve_body( $response ), 0, 500 ),
			],
		];
	}

	/** The JSON document the receiver gets — n8n/Zapier/Make-friendly flat shape. */
	public static function build_body( array $context ): array {
		return [
			'form_key'      => (string) ( $context['form_key'] ?? '' ),
			'submission_id' => (int) ( $context['submission_id'] ?? 0 ),
			'submitted_at'  => (string) ( $context['submitted_at'] ?? '' ),
			'page_url'      => (string) ( $context['page_url'] ?? '' ),
			'page_title'    => (string) ( $context['page_title'] ?? '' ),
			'fields'        => (array) ( $context['fields'] ?? [] ),
			'labels'        => (array) ( $context['labels'] ?? [] ),
		];
	}
}
