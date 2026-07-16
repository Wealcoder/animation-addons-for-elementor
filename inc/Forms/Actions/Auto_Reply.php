<?php
/**
 * AAE Forms — Auto Reply action (Milestone 7, Free/P1).
 *
 * Confirmation email to the visitor. Per spec it only fires when a valid
 * email-type field exists in the submission — no email field is a clean
 * skip (success, logged as such), never a retry loop. Optional
 * include_copy appends the submitted values.
 *
 * Off by default; enabled via actions_json (see Dispatcher).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Actions;

use WCF_ADDONS\Forms\Smart_Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Auto_Reply extends Action_Base {

	public static function type(): string {
		return 'auto_reply';
	}

	public function run( array $payload ): array {
		$settings = (array) ( $payload['settings'] ?? [] );
		$context  = (array) ( $payload['context'] ?? [] );

		$to = Admin_Email::first_email_field( $context );

		if ( '' === $to ) {
			return [
				'success'  => true, // nothing to do ≠ failure — don't retry.
				'message'  => 'Skipped: submission has no email field to reply to',
				'request'  => [],
				'response' => [ 'skipped' => true ],
			];
		}

		$subject = Smart_Tags::resolve(
			'' !== trim( (string) ( $settings['subject'] ?? '' ) )
				? (string) $settings['subject']
				: __( 'Thanks — we received your message', 'animation-addons-for-elementor' ),
			$context,
			'text'
		);

		$body_template = '' !== trim( (string) ( $settings['body'] ?? '' ) )
			? (string) $settings['body']
			: __( 'Hi,<br><br>Thanks for reaching out to {{site.title}}. We received your message and will get back to you soon.', 'animation-addons-for-elementor' );

		if ( ! empty( $settings['include_copy'] ) ) {
			$body_template .= '<br><br>' . __( 'Your submission:', 'animation-addons-for-elementor' ) . '<br>{{all_fields}}';
		}

		$body = Smart_Tags::resolve( $body_template, $context, 'html' );

		$sent = wp_mail( $to, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );

		return [
			'success'  => (bool) $sent,
			'message'  => $sent
				? sprintf( 'Auto reply sent to %s', $to )
				: 'wp_mail() failed — check the site mailer/SMTP configuration',
			'request'  => [
				'to'      => $to,
				'subject' => $subject,
			],
			'response' => [ 'sent' => (bool) $sent ],
		];
	}
}
