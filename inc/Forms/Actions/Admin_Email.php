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
use WCF_ADDONS\Forms\Uploads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Email extends Action_Base {

	/** Total attachment cap (20 MB) — most mailers reject beyond ~25 MB. */
	const MAX_ATTACH_BYTES = 20971520;

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

		// Attach submitted files (default on; actions_json can set
		// attach_files:false). Oversize files are skipped, not fatal —
		// their names still appear in the body via {{all_fields}}.
		$attachments = [];
		if ( ! isset( $settings['attach_files'] ) || filter_var( $settings['attach_files'], FILTER_VALIDATE_BOOLEAN ) ) {
			$attachments = self::file_attachments( $context );
		}

		$sent = wp_mail( $to, $subject, $body, $headers, $attachments );

		// Store-less ("Email Only") forms: the email WAS the delivery — once
		// it's out, nothing can ever reach these files again (no submission
		// row → no dashboard link), so purge instead of letting personal
		// data sit out the 24h TTL. Failed sends keep the files for retries.
		if ( $sent && (int) ( $context['submission_id'] ?? 0 ) <= 0 ) {
			Uploads::purge_pending( self::file_ids( $context ), (string) ( $context['form_key'] ?? '' ) );
		}

		return [
			'success'  => (bool) $sent,
			'message'  => $sent
				? sprintf( 'Admin email sent to %s', $to )
				: 'wp_mail() failed — check the site mailer/SMTP configuration',
			'request'  => [
				'to'          => $to,
				'subject'     => $subject,
				'reply_to'    => $reply_to,
				'attachments' => array_keys( $attachments ),
			],
			'response' => [ 'sent' => (bool) $sent ],
		];
	}

	/**
	 * Local paths for every submitted file field, keyed by original
	 * filename, capped at MAX_ATTACH_BYTES total.
	 *
	 * @return array<string,string>
	 */
	private static function file_attachments( array $context ): array {
		$ids = self::file_ids( $context );

		if ( empty( $ids ) ) {
			return [];
		}

		$paths = Uploads::attachment_paths(
			$ids,
			(int) ( $context['submission_id'] ?? 0 ),
			(string) ( $context['form_key'] ?? '' )
		);

		$total = 0;
		$out   = [];

		foreach ( $paths as $name => $path ) {
			$size = (int) filesize( $path );
			if ( $total + $size > self::MAX_ATTACH_BYTES ) {
				continue;
			}
			$total        += $size;
			$out[ $name ] = $path;
		}

		return $out;
	}

	/** Attachment row ids referenced by every file field of the submission. */
	private static function file_ids( array $context ): array {
		$ids = [];

		foreach ( (array) ( $context['types'] ?? [] ) as $key => $type ) {
			if ( 'file' !== $type ) {
				continue;
			}
			foreach ( (array) ( $context['fields'][ $key ] ?? [] ) as $ref ) {
				if ( is_array( $ref ) && ! empty( $ref['id'] ) ) {
					$ids[] = (int) $ref['id'];
				}
			}
		}

		return $ids;
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
