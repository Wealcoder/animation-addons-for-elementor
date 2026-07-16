<?php
/**
 * AAE Forms — Admin Email action (Milestone 7, Free/P0).
 *
 * Notifies the site admin about a submission. Settings (all optional —
 * blank falls back): to (default site admin_email), cc, bcc, reply_to
 * (default: the first submitted email-type field, so "Reply" answers the
 * visitor), subject, body. Subject/body run through Smart_Tags; the body is
 * sent text/html.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Actions;

use WCF_ADDONS\Forms\Smart_Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Email extends Action_Base {

	public static function type(): string {
		return 'admin_email';
	}

	public function run( array $payload ): array {
		$settings = (array) ( $payload['settings'] ?? [] );
		$context  = (array) ( $payload['context'] ?? [] );

		$to = trim( (string) ( $settings['to'] ?? '' ) );
		if ( '' === $to ) {
			$to = (string) get_option( 'admin_email' );
		}

		$subject = Smart_Tags::resolve(
			'' !== trim( (string) ( $settings['subject'] ?? '' ) )
				? (string) $settings['subject']
				: __( 'New form submission — {{site.title}}', 'animation-addons-for-elementor' ),
			$context,
			'text'
		);

		$body = Smart_Tags::resolve(
			'' !== trim( (string) ( $settings['body'] ?? '' ) )
				? (string) $settings['body']
				: '{{all_fields}}<br>&mdash;<br>' . __( 'Submitted on', 'animation-addons-for-elementor' ) . ' {{page.url}}',
			$context,
			'html'
		);

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		foreach ( [
			'cc'  => 'Cc',
			'bcc' => 'Bcc',
		] as $key => $header ) {
			$value = trim( (string) ( $settings[ $key ] ?? '' ) );
			if ( '' !== $value ) {
				$headers[] = $header . ': ' . $value;
			}
		}

		$reply_to = trim( (string) ( $settings['reply_to'] ?? '' ) );
		if ( '' === $reply_to ) {
			$reply_to = self::first_email_field( $context );
		}
		if ( '' !== $reply_to ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$sent = wp_mail( $to, $subject, $body, $headers );

		return [
			'success'  => (bool) $sent,
			'message'  => $sent
				? sprintf( 'Admin email sent to %s', $to )
				: 'wp_mail() failed — check the site mailer/SMTP configuration',
			'request'  => [
				'to'       => $to,
				'subject'  => $subject,
				'reply_to' => $reply_to,
			],
			'response' => [ 'sent' => (bool) $sent ],
		];
	}

	/** Value of the first email-type field in the submission, or ''. */
	public static function first_email_field( array $context ): string {
		foreach ( (array) ( $context['types'] ?? [] ) as $key => $type ) {
			if ( 'email' === $type && ! empty( $context['fields'][ $key ] ) && is_string( $context['fields'][ $key ] ) ) {
				return $context['fields'][ $key ];
			}
		}

		return '';
	}
}
