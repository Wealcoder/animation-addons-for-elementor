<?php
/**
 * AAE Forms — public REST API (Milestone 5).
 *
 *   POST /aae/v1/forms/{form_key}/token   fresh single-use submit token
 *                                         (no-store, itself rate-limited)
 *   POST /aae/v1/forms/{form_key}/submit  token + nonce + validation, then
 *                                         hands the clean submission to the
 *                                         `aae_form/submission_validated`
 *                                         action (Milestone 6 persists it)
 *
 * Submissions go through REST ONLY — never public admin-ajax (spec pipeline
 * step 2). Response codes follow the SSR table the frontend must handle
 * distinctly: 200 / 400 / 403 / 404 / 409 / 422 / 429 / 500.
 *
 * Admin routes (submissions list, test-email, retry…) are LATER milestones.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rest {

	const REST_NAMESPACE = 'aae/v1';
	const NONCE_ACTION   = 'aae_form_submit';

	/** Route regex for form keys ('aaef_' + hex normally, but tolerant). */
	const KEY_PATTERN = '(?P<form_key>[A-Za-z0-9_\-]{1,64})';

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/forms/' . self::KEY_PATTERN . '/token',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_token' ],
				'permission_callback' => '__return_true', // public; abuse-limited inside.
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/forms/' . self::KEY_PATTERN . '/submit',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_submit' ],
				'permission_callback' => '__return_true', // public; token+nonce checked inside.
			]
		);

		// Admin: verify the mailer works before trusting it with leads.
		// Cookie auth + X-WP-Nonce (standard REST) + capability.
		register_rest_route(
			self::REST_NAMESPACE,
			'/actions/test-email',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_test_email' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		// Admin: fire a sample payload at a webhook URL before enabling it.
		register_rest_route(
			self::REST_NAMESPACE,
			'/actions/test-webhook',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_test_webhook' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/** POST /actions/test-webhook — { url } → delivers a sample submission payload. */
	public static function handle_test_webhook( WP_REST_Request $request ): WP_REST_Response {
		$params = (array) $request->get_json_params();
		$url    = isset( $params['url'] ) && is_string( $params['url'] ) ? trim( $params['url'] ) : '';

		if ( '' === $url ) {
			return self::error( 400, 'aae_form_bad_request', __( 'A webhook URL is required.', 'animation-addons-for-elementor' ) );
		}

		$action = new \WCF_ADDONS\Forms\Actions\Webhook();
		$result = $action->run(
			[
				'settings' => [ 'url' => $url ],
				'context'  => [
					'form_key'      => 'aaef_test',
					'submission_id' => 0,
					'submitted_at'  => current_time( 'mysql' ),
					'page_url'      => home_url( '/' ),
					'page_title'    => get_bloginfo( 'name' ),
					'fields'        => [
						'name'    => 'Test Person',
						'email'   => 'test@example.com',
						'message' => 'This is a test webhook delivery from the AAE Form Builder.',
					],
					'labels'        => [
						'name'    => 'Name',
						'email'   => 'Email',
						'message' => 'Message',
					],
				],
			]
		);

		return new WP_REST_Response(
			[
				'success'  => (bool) $result['success'],
				'message'  => (string) $result['message'],
				'response' => $result['response'],
			],
			$result['success'] ? 200 : 502
		);
	}

	/** POST /actions/test-email — { to?, subject?, body? } */
	public static function handle_test_email( WP_REST_Request $request ): WP_REST_Response {
		$params = (array) $request->get_json_params();

		$to = isset( $params['to'] ) && is_string( $params['to'] ) && '' !== trim( $params['to'] )
			? sanitize_text_field( $params['to'] )
			: (string) get_option( 'admin_email' );

		$subject = isset( $params['subject'] ) && is_string( $params['subject'] ) && '' !== trim( $params['subject'] )
			? sanitize_text_field( $params['subject'] )
			: __( 'AAE Forms — test email', 'animation-addons-for-elementor' );

		$body = isset( $params['body'] ) && is_string( $params['body'] ) && '' !== trim( $params['body'] )
			? wp_kses_post( $params['body'] )
			: __( 'This is a test email from the AAE Form Builder. If you can read this, your site can deliver form notifications.', 'animation-addons-for-elementor' );

		$sent = wp_mail( $to, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );

		if ( ! $sent ) {
			return self::error( 500, 'aae_form_mail_failed', __( 'wp_mail() failed — check your SMTP/mailer configuration.', 'animation-addons-for-elementor' ) );
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => sprintf(
					/* translators: %s: recipient email address */
					__( 'Test email sent to %s.', 'animation-addons-for-elementor' ),
					$to
				),
			],
			200
		);
	}

	/** POST /forms/{form_key}/token */
	public static function handle_token( WP_REST_Request $request ): WP_REST_Response {
		$form_key = (string) $request['form_key'];

		if ( null === Schema_Store::get_active_schema( $form_key ) ) {
			return self::error( 404, 'aae_form_not_found', __( 'Form not found.', 'animation-addons-for-elementor' ) );
		}

		$issued = Token::issue( $form_key );

		if ( null === $issued ) {
			$response = self::error( 429, 'aae_form_rate_limited', __( 'Too many attempts. Please wait a moment and try again.', 'animation-addons-for-elementor' ) );
			$response->header( 'Retry-After', (string) Token::RATE_WINDOW );

			return $response;
		}

		$response = new WP_REST_Response(
			[
				'token'      => $issued['token'],
				'expires_in' => $issued['expires_in'],
				// Fresh nonce next to the fresh token — both fetched no-store,
				// so page/CDN caches can never serve a stale pair (the classic
				// "cache plugin breaks forms" failure).
				'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
			],
			200
		);

		return self::no_store( $response );
	}

	/** POST /forms/{form_key}/submit — spec pipeline steps 3–9. */
	public static function handle_submit( WP_REST_Request $request ): WP_REST_Response {
		$form_key = (string) $request['form_key'];

		$active = Schema_Store::get_active( $form_key );
		if ( null === $active ) {
			return self::error( 404, 'aae_form_not_found', __( 'Form not found.', 'animation-addons-for-elementor' ) );
		}
		$schema = $active['schema'];

		$params = $request->get_json_params();
		if ( ! is_array( $params ) || ! isset( $params['fields'] ) || ! is_array( $params['fields'] ) ) {
			return self::error( 400, 'aae_form_bad_request', __( 'Malformed request.', 'animation-addons-for-elementor' ) );
		}

		// --- Step 3: request origin (nonce) + single-use token. -------------
		// Every block below answers the VISITOR with a safe generic message
		// (spec step 6) while the REAL reason goes to the Spam_Log for
		// admins — never a clean submission, never an action job.
		$nonce = isset( $params['nonce'] ) && is_string( $params['nonce'] ) ? $params['nonce'] : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			Spam_Log::record( $form_key, 'bad_nonce' );

			return self::error( 403, 'aae_form_security', self::generic_block_message() );
		}

		// --- Step 4: Bot Shield. ---------------------------------------------
		// Submit-attempt rate limit, per hashed visitor + form (counts every
		// attempt, valid or not — that's the point).
		$rate = apply_filters(
			'aae_form/submit_rate_limit',
			[
				'limit'  => 5,
				'window' => 5 * MINUTE_IN_SECONDS,
			],
			$form_key
		);

		if ( Token::over_limit( 'submit|' . $form_key, (int) $rate['limit'], (int) $rate['window'] ) ) {
			Spam_Log::record( $form_key, 'rate_limited' );

			$response = self::error( 429, 'aae_form_rate_limited', __( 'Too many attempts. Please wait a moment and try again.', 'animation-addons-for-elementor' ) );
			$response->header( 'Retry-After', (string) (int) $rate['window'] );

			return $response;
		}

		$token    = isset( $params['token'] ) && is_string( $params['token'] ) ? $params['token'] : '';
		$consumed = Token::consume( $token, $form_key );

		if ( ! $consumed['ok'] ) {
			if ( 'replay' === $consumed['reason'] ) {
				Spam_Log::record( $form_key, 'token_replay' );

				return self::error( 409, 'aae_form_duplicate', __( 'This form was already submitted.', 'animation-addons-for-elementor' ) );
			}

			Spam_Log::record( $form_key, 'token_invalid' );

			return self::error( 403, 'aae_form_security', self::generic_block_message() );
		}

		// Minimum submit time, measured from token issue (prefetched on the
		// visitor's first interaction — so this is real filling time).
		$min_seconds = (int) ( $schema['settings']['spam_min_seconds'] ?? 0 );
		if ( $min_seconds > 0 && ( time() - (int) $consumed['issued'] ) < $min_seconds ) {
			Spam_Log::record( $form_key, 'too_fast' );

			return self::error( 403, 'aae_form_security', self::generic_block_message() );
		}

		// Honeypot: the twig renders a visually-hidden aae_hp input; humans
		// never fill it. A filled honeypot gets a FAKE success — the bot
		// learns nothing, the admin sees the truth in the spam log.
		if ( ! empty( $schema['settings']['spam_honeypot'] ) && ! empty( $params['fields']['aae_hp'] ) ) {
			Spam_Log::record( $form_key, 'honeypot' );

			return self::no_store(
				new WP_REST_Response(
					[
						'success' => true,
						'message' => self::success_message( $schema ),
					],
					200
				)
			);
		}

		// --- Step 5: validate against the active schema snapshot. -----------
		$result = Validator::validate( $schema, $params['fields'] );

		if ( ! empty( $result['errors'] ) ) {
			$response = self::error( 422, 'aae_form_validation', __( 'Please correct the highlighted fields.', 'animation-addons-for-elementor' ) );
			$response->set_data( array_merge( $response->get_data(), [ 'errors' => $result['errors'] ] ) );

			return $response;
		}

		$meta = [
			'form_key'       => $form_key,
			'schema_version' => $active['version'],
			'source_url'     => isset( $params['source_url'] ) && is_string( $params['source_url'] ) ? esc_url_raw( $params['source_url'] ) : '',
			'referrer_url'   => isset( $params['referrer_url'] ) && is_string( $params['referrer_url'] ) ? esc_url_raw( $params['referrer_url'] ) : '',
			'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
			'ip_hash'        => Token::visitor_hash(),
		];

		// Observability seam — fires whether or not storage is enabled.
		do_action( 'aae_form/submission_validated', $form_key, $result['clean'], $schema, $meta );

		// --- Step 7: save FIRST ("never lose a lead"). Storage failing is a
		// real 500 — success is never faked over a lost lead. Behavior
		// 'email' explicitly opts out of storage (admin's choice).
		$submission_id = 0;
		$behavior      = (string) ( $schema['settings']['behavior'] ?? 'store_email' );

		if ( 'email' !== $behavior ) {
			$submission_id = Submissions::save( $active['form_id'], $active['version'], $form_key, $result['clean'], $schema, $meta );

			if ( $submission_id <= 0 ) {
				return self::error( 500, 'aae_form_storage', __( 'We could not submit the form. Please try again.', 'animation-addons-for-elementor' ) );
			}
		}

		// --- Step 8 seam: Milestone 7+ hooks here to enqueue action jobs
		// (email/webhook/…). Nothing external runs inside this request.
		do_action( 'aae_form/submission_saved', $submission_id, $form_key, $result['clean'], $schema, $meta );

		// --- Step 9: success AFTER save, never after third-party actions.
		// Redirect is the one immediate UX action (spec): it rides the
		// success response and must NOT wait for queued email/webhook jobs.
		$response_data = [
			'success' => true,
			'message' => self::success_message( $schema ),
		];

		$redirect_url = self::redirect_url( $schema );
		if ( '' !== $redirect_url ) {
			$response_data['redirect_url'] = $redirect_url;
		}

		return self::no_store( new WP_REST_Response( $response_data, 200 ) );
	}

	/** The configured after-submit redirect URL, or '' when off/invalid. */
	private static function redirect_url( array $schema ): string {
		$config = json_decode( (string) ( $schema['settings']['actions_json'] ?? '' ), true );

		if ( ! is_array( $config ) || ! is_array( $config['redirect'] ?? null ) || empty( $config['redirect']['enabled'] ) ) {
			return '';
		}

		$url = esc_url_raw( trim( (string) ( $config['redirect']['url'] ?? '' ) ) );

		return preg_match( '#^https?://#i', $url ) ? $url : '';
	}

	/** The editor-authored success message, or the stock fallback. */
	private static function success_message( array $schema ): string {
		$message = trim( (string) ( $schema['messages']['success'] ?? '' ) );

		return '' !== $message
			? $message
			: __( 'Thank you! Your message has been sent.', 'animation-addons-for-elementor' );
	}

	/** Visitor-facing copy for every security block; real reason stays in `code`. */
	private static function generic_block_message(): string {
		return __( 'We could not submit the form. Please reload the page and try again.', 'animation-addons-for-elementor' );
	}

	private static function error( int $status, string $code, string $message ): WP_REST_Response {
		return self::no_store(
			new WP_REST_Response(
				[
					'success' => false,
					'code'    => $code,
					'message' => $message,
				],
				$status
			)
		);
	}

	private static function no_store( WP_REST_Response $response ): WP_REST_Response {
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}
}
