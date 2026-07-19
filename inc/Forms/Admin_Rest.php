<?php
/**
 * AAE Forms — admin data API (Milestone 9).
 *
 * Serves the "Form Submissions" tab of the Animation Addon React dashboard
 * (admin.php?page=wcf_addons_settings&tab=submissions). Cookie auth +
 * X-WP-Nonce (wp_rest) + manage_options on every route:
 *
 *   GET  /aae/v1/admin/submissions        list (filters + pagination)
 *   GET  /aae/v1/admin/submissions/{id}   values + meta + action logs
 *   POST /aae/v1/admin/submissions/delete bulk delete {ids:[]}
 *   GET  /aae/v1/admin/spam-log           Bot Shield blocks
 *   GET  /aae/v1/admin/jobs               action queue
 *   POST /aae/v1/admin/jobs/{id}/retry    manual retry of a failed job
 *   GET  /aae/v1/admin/health             per-form health check
 *
 * CSV export stays a classic admin-post download (streams a file):
 *   admin-post.php?action=aae_form_csv&…filters&_wpnonce
 *
 * Config for the React side is localized as AAE_FORMS_ADMIN onto the
 * existing 'wcf-admin' dashboard bundle.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

use WCF_ADDONS\Forms\Integrations\Integrations;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders -- this file queries the six custom aae_* tables only: every VALUE goes through $wpdb->prepare(); interpolated fragments are internal table-name constants, int-cast id lists, or %-placeholder WHERE clauses whose args are built alongside them (the sniff cannot count placeholders in dynamic SQL).

final class Admin_Rest {

	const CAP       = 'manage_options';
	const PER_PAGE  = 20;
	const CSV_LIMIT = 5000;

	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
		add_action( 'admin_post_aae_form_csv', [ self::class, 'handle_csv_export' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'localize' ], 30 );

		// "Submissions" as its OWN item under the Animation Addon menu (like
		// Page Import / CPT Builder), after the dashboard registers at 25.
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 27 );
		add_filter( 'submenu_file', [ self::class, 'highlight_menu' ] );
	}

	/**
	 * The submenu slug is a full admin URL into the dashboard app with the
	 * tab query preset — the page itself is the wcf_addons_settings React
	 * app, which routes ?tab=submissions to the Submissions view. A slug
	 * containing "admin.php?" is used by WP as the href VERBATIM (the same
	 * mechanism as core's "edit.php?post_type=…" submenus); a bare
	 * "slug&tab=…" would get urlencoded into page=slug%26tab=… and 404.
	 * Keeps one bundle, one screen id (the dashboard's own asset gating
	 * keeps working) and the dashboard theme.
	 */
	const MENU_SLUG = 'admin.php?page=wcf_addons_settings&tab=submissions';

	public static function register_menu(): void {
		add_submenu_page(
			'wcf_addons_page',
			esc_html__( 'Form Submissions', 'animation-addons-for-elementor' ),
			esc_html__( 'Submissions', 'animation-addons-for-elementor' ),
			self::CAP,
			self::MENU_SLUG,
			'',
			1 // right after Settings.
		);
	}

	/** Keep OUR submenu item highlighted (not Settings) when the tab is open. */
	public static function highlight_menu( $submenu_file ) {
		if ( isset( $_GET['page'], $_GET['tab'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'wcf_addons_settings' === $_GET['page'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'submissions' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::MENU_SLUG;
		}

		return $submenu_file;
	}

	/** AAE_FORMS_ADMIN on the dashboard bundle — REST base + nonces. */
	public static function localize(): void {
		if ( ! wp_script_is( 'wcf-admin', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'wcf-admin',
			'AAE_FORMS_ADMIN',
			[
				'restUrl' => esc_url_raw( rest_url( Rest::REST_NAMESPACE . '/admin/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				// NOT wp_nonce_url(): it esc_html()s the URL, so the "&" before
				// _wpnonce becomes "&#038;" — the browser reads the "#" as a
				// fragment start and silently drops the nonce AND every filter
				// appended after it.
				'csvUrl'  => add_query_arg(
					[
						'action'   => 'aae_form_csv',
						'_wpnonce' => wp_create_nonce( 'aae_form_csv' ),
					],
					admin_url( 'admin-post.php' )
				),
			]
		);
	}

	public static function register_routes(): void {
		$admin = [
			'permission_callback' => static function () {
				return current_user_can( self::CAP );
			},
		];

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/submissions',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'list_submissions' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/submissions/(?P<id>\d+)',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'get_submission' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/submissions/delete',
			$admin + [
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ self::class, 'delete_submissions' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/spam-log',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'list_spam' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/jobs',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'list_jobs' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/jobs/(?P<id>\d+)/retry',
			$admin + [
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ self::class, 'retry_job' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/health',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'form_health' ],
			]
		);

		// Email-marketing integrations (Brevo, …). Free ships the key store +
		// UI; the concrete provider (real API calls) is pro — routes that
		// need the network delegate to Integrations::get() and return a
		// "requires pro" state when no provider backs the id.
		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/integrations',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'list_integrations' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/integrations/(?P<id>[a-z0-9_-]+)/key',
			$admin + [
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ self::class, 'save_integration_key' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/integrations/(?P<id>[a-z0-9_-]+)/lists',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'integration_lists' ],
			]
		);
	}

	// ------------------------------------------------------------------
	// Submissions
	// ------------------------------------------------------------------

	/** Sanitized list filters from the request. */
	private static function read_filters( WP_REST_Request $request ): array {
		return [
			'form_key' => sanitize_text_field( (string) $request->get_param( 'form_key' ) ),
			'status'   => sanitize_text_field( (string) $request->get_param( 'status' ) ),
			'from'     => sanitize_text_field( (string) $request->get_param( 'from' ) ),
			'to'       => sanitize_text_field( (string) $request->get_param( 'to' ) ),
			's'        => sanitize_text_field( (string) $request->get_param( 's' ) ),
		];
	}

	/** @return array [ 'rows' => object[], 'total' => int ] */
	private static function query_submissions( array $filters, int $limit, int $offset ): array {
		global $wpdb;
		$table  = Database::submissions_table();
		$values = Database::submission_values_table();

		$where = [ '1=1' ];
		$args  = [];

		if ( '' !== $filters['form_key'] ) {
			$where[] = 'form_key = %s';
			$args[]  = $filters['form_key'];
		}
		if ( '' !== $filters['status'] ) {
			$where[] = 'status = %s';
			$args[]  = $filters['status'];
		}
		if ( '' !== $filters['from'] ) {
			$where[] = 'created_at >= %s';
			$args[]  = $filters['from'] . ' 00:00:00';
		}
		if ( '' !== $filters['to'] ) {
			$where[] = 'created_at <= %s';
			$args[]  = $filters['to'] . ' 23:59:59';
		}
		if ( '' !== $filters['s'] ) {
			// Find-by-value — incl. find-by-email for DSAR/support requests.
			$where[] = "id IN ( SELECT submission_id FROM {$values} WHERE field_value LIKE %s )";
			$args[]  = '%' . $wpdb->esc_like( $filters['s'] ) . '%';
		}

		$where_sql = implode( ' AND ', $where );

		$total = (int) $wpdb->get_var(
			$args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $args, [ $limit, $offset ] ) )
		);

		return [
			'rows'  => (array) $rows,
			'total' => $total,
		];
	}

	public static function list_submissions( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$filters  = self::read_filters( $request );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = min( 100, max( 1, $per_page > 0 ? $per_page : self::PER_PAGE ) );
		$result   = self::query_submissions( $filters, $per_page, ( $page - 1 ) * $per_page );

		// One query for all previews: first two values per listed submission.
		$previews = [];
		if ( $result['rows'] ) {
			$ids = implode( ',', array_map( static fn( $r ) => (int) $r->id, $result['rows'] ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids are ints.
			$value_rows = $wpdb->get_results( 'SELECT submission_id, field_value FROM ' . Database::submission_values_table() . " WHERE submission_id IN ({$ids}) ORDER BY id" );
			foreach ( $value_rows as $value ) {
				$sid = (int) $value->submission_id;
				if ( count( $previews[ $sid ] ?? [] ) < 2 ) {
					$previews[ $sid ][] = self::preview_value( (string) $value->field_value );
				}
			}
		}

		$rows = array_map(
			static function ( $row ) use ( $previews ) {
				$preview = implode( ' — ', $previews[ (int) $row->id ] ?? [] );

				return [
					'id'         => (int) $row->id,
					'form_key'   => (string) $row->form_key,
					'status'     => (string) $row->status,
					'created_at' => (string) $row->created_at,
					'preview'    => mb_strlen( $preview ) > 60 ? mb_substr( $preview, 0, 57 ) . '…' : $preview,
				];
			},
			$result['rows']
		);

		return new WP_REST_Response(
			[
				'rows'  => $rows,
				'total' => $result['total'],
				'forms' => self::forms_for_filter(),
			],
			200
		);
	}

	/**
	 * Form-filter options: human label (page title) + total lead count per
	 * form, so the dropdown reads "Contact page (12)" instead of a raw key.
	 *
	 * @return array<int, array{key:string,label:string,count:int}>
	 */
	private static function forms_for_filter(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			'SELECT f.form_key, f.post_id, COUNT(s.id) AS submissions'
			. ' FROM ' . Database::forms_table() . ' f'
			. ' LEFT JOIN ' . Database::submissions_table() . ' s ON s.form_key = f.form_key'
			. ' GROUP BY f.id, f.form_key, f.post_id ORDER BY f.id'
		);

		return array_map(
			static function ( $row ) {
				$post_id = (int) $row->post_id;
				$title   = $post_id ? get_the_title( $post_id ) : '';

				return [
					'key'   => (string) $row->form_key,
					'label' => '' !== $title ? $title : (string) $row->form_key,
					'count' => (int) $row->submissions,
				];
			},
			(array) $rows
		);
	}

	public static function get_submission( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$id = (int) $request['id'];

		$submission = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Database::submissions_table() . ' WHERE id = %d', $id ) );

		if ( ! $submission ) {
			return new WP_REST_Response( [ 'message' => __( 'Submission not found.', 'animation-addons-for-elementor' ) ], 404 );
		}

		$values = $wpdb->get_results( $wpdb->prepare( 'SELECT field_key, field_label, field_type, field_value FROM ' . Database::submission_values_table() . ' WHERE submission_id = %d ORDER BY id', $id ) );
		$logs   = $wpdb->get_results( $wpdb->prepare( 'SELECT action_type, status, message, created_at FROM ' . Database::action_logs_table() . ' WHERE submission_id = %d ORDER BY id', $id ) );

		return new WP_REST_Response(
			[
				'submission' => [
					'id'             => (int) $submission->id,
					'form_key'       => (string) $submission->form_key,
					'schema_version' => (int) $submission->schema_version,
					'status'         => (string) $submission->status,
					'created_at'     => (string) $submission->created_at,
					'source_url'     => (string) $submission->source_url,
					'referrer_url'   => (string) $submission->referrer_url,
					'utm_json'       => (string) $submission->utm_json,
					'user_agent'     => (string) $submission->user_agent,
					'ip_hash'        => (string) $submission->ip_hash,
				],
				'values'     => array_map(
					static function ( $v ) {
						$entry = [
							'key'   => (string) $v->field_key,
							'label' => '' !== (string) $v->field_label ? (string) $v->field_label : (string) $v->field_key,
							'type'  => (string) $v->field_type,
							'value' => (string) $v->field_value,
						];

						// File fields store [{id,name,size},…] — resolve each to
						// a download link through the auth proxy.
						if ( 'file' === (string) $v->field_type ) {
							$entry['files'] = self::file_links( (string) $v->field_value );
						}

						return $entry;
					},
					(array) $values
				),
				'logs'       => array_map(
					static fn( $l ) => [
						'action_type' => (string) $l->action_type,
						'status'      => (string) $l->status,
						'message'     => (string) $l->message,
						'created_at'  => (string) $l->created_at,
					],
					(array) $logs
				),
			],
			200
		);
	}

	/**
	 * List-preview form of a stored value: file-field JSON ([{id,name},…])
	 * reads as the file names, everything else passes through as-is.
	 */
	private static function preview_value( string $value ): string {
		if ( '' === $value || '[' !== $value[0] ) {
			return $value;
		}

		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return $value;
		}

		$names = [];
		foreach ( $decoded as $entry ) {
			if ( is_array( $entry ) && isset( $entry['name'] ) ) {
				$names[] = (string) $entry['name'];
			} elseif ( is_scalar( $entry ) ) {
				$names[] = (string) $entry; // multi-select JSON arrays too.
			}
		}

		return $names ? implode( ', ', $names ) : $value;
	}

	/**
	 * File-field value JSON ([{id,name,size},…]) → download links for the
	 * dashboard. The download proxy is a REST route (cookie auth), so a plain
	 * <a href> must carry the wp_rest nonce as the _wpnonce query arg.
	 *
	 * @return array<int, array{id:int,name:string,size:int,url:string}>
	 */
	private static function file_links( string $value ): array {
		$entries = json_decode( $value, true );
		if ( ! is_array( $entries ) ) {
			return [];
		}

		$links = [];
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['id'] ) ) {
				continue;
			}

			$id = (int) $entry['id'];

			$links[] = [
				'id'   => $id,
				'name' => (string) ( $entry['name'] ?? ( 'file-' . $id ) ),
				'size' => (int) ( $entry['size'] ?? 0 ),
				'url'  => add_query_arg(
					'_wpnonce',
					wp_create_nonce( 'wp_rest' ),
					rest_url( Rest::REST_NAMESPACE . '/attachments/' . $id . '/download' )
				),
			];
		}

		return $links;
	}

	public static function delete_submissions( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$params = (array) $request->get_json_params();
		$ids    = array_filter( array_map( 'intval', (array) ( $params['ids'] ?? [] ) ) );

		if ( ! $ids ) {
			return new WP_REST_Response( [ 'deleted' => 0 ], 200 );
		}

		$in      = implode( ',', $ids );
		$deleted = (int) $wpdb->query( 'DELETE FROM ' . Database::submissions_table() . " WHERE id IN ({$in})" );
		$wpdb->query( 'DELETE FROM ' . Database::submission_values_table() . " WHERE submission_id IN ({$in})" );

		return new WP_REST_Response( [ 'deleted' => $deleted ], 200 );
	}

	// ------------------------------------------------------------------
	// Spam log / jobs / health
	// ------------------------------------------------------------------

	/** Bot Shield block log (action_type = bot_shield rows). */
	public static function list_spam( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table  = Database::action_logs_table();
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$offset = ( $page - 1 ) * self::PER_PAGE;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE action_type = 'bot_shield'" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, message, request_snapshot, created_at FROM {$table} WHERE action_type = 'bot_shield' ORDER BY id DESC LIMIT %d OFFSET %d",
				self::PER_PAGE,
				$offset
			)
		);

		return new WP_REST_Response(
			[
				'rows'  => array_map(
					static function ( $row ) {
						$snapshot = json_decode( (string) $row->request_snapshot, true );
						$snapshot = is_array( $snapshot ) ? $snapshot : [];

						return [
							'id'         => (int) $row->id,
							'reason'     => (string) $row->message,
							'form_key'   => (string) ( $snapshot['form_key'] ?? '' ),
							'ip_hash'    => substr( (string) ( $snapshot['ip_hash'] ?? '' ), 0, 12 ),
							'user_agent' => mb_substr( (string) ( $snapshot['user_agent'] ?? '' ), 0, 80 ),
							'created_at' => (string) $row->created_at,
						];
					},
					(array) $rows
				),
				'total' => $total,
			],
			200
		);
	}

	public static function list_jobs( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table  = Database::action_jobs_table();
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$offset = ( $page - 1 ) * self::PER_PAGE;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, submission_id, action_type, status, attempts, next_run_at, updated_at FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				self::PER_PAGE,
				$offset
			)
		);

		return new WP_REST_Response(
			[
				'rows'  => array_map(
					static fn( $row ) => [
						'id'            => (int) $row->id,
						'submission_id' => (int) $row->submission_id,
						'action_type'   => (string) $row->action_type,
						'status'        => (string) $row->status,
						'attempts'      => (int) $row->attempts,
						'next_run_at'   => null !== $row->next_run_at ? (string) $row->next_run_at : '',
						'updated_at'    => (string) $row->updated_at,
					],
					(array) $rows
				),
				'total' => $total,
			],
			200
		);
	}

	public static function retry_job( WP_REST_Request $request ): WP_REST_Response {
		$ok = Queue::retry( (int) $request['id'] );

		if ( $ok ) {
			Queue::process_due(); // the admin is watching — run it now.
		}

		return new WP_REST_Response(
			[
				'success' => $ok,
				'message' => $ok
					? __( 'Job requeued and run.', 'animation-addons-for-elementor' )
					: __( 'Only failed jobs can be retried.', 'animation-addons-for-elementor' ),
			],
			$ok ? 200 : 409
		);
	}

	public static function form_health(): WP_REST_Response {
		global $wpdb;

		$forms = $wpdb->get_results( 'SELECT * FROM ' . Database::forms_table() . ' ORDER BY id' );

		$out = [];
		foreach ( (array) $forms as $form ) {
			$active = Schema_Store::get_active( (string) $form->form_key );

			$submissions = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::submissions_table() . ' WHERE form_key = %s', $form->form_key ) );
			$last        = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT created_at FROM ' . Database::submissions_table() . ' WHERE form_key = %s ORDER BY id DESC LIMIT 1', $form->form_key ) );
			$failed      = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . Database::action_jobs_table() . ' j INNER JOIN ' . Database::submissions_table() . ' s ON s.id = j.submission_id WHERE s.form_key = %s AND j.status = %s',
					$form->form_key,
					Queue::STATUS_FAILED
				)
			);

			$post   = get_post( (int) $form->post_id );
			$issues = [];
			if ( ! $active ) {
				$issues[] = 'no_active_schema';
			}
			if ( ! $post || 'trash' === $post->post_status ) {
				$issues[] = 'page_missing';
			}
			if ( $failed > 0 ) {
				$issues[] = 'failed_jobs';
			}

			$out[] = [
				'form_key'       => (string) $form->form_key,
				'post_id'        => (int) $form->post_id,
				'post_title'     => $post ? ( '' !== get_the_title( $post ) ? get_the_title( $post ) : '#' . $post->ID ) : '',
				'edit_url'       => $post ? (string) get_edit_post_link( $post->ID, 'raw' ) : '',
				'schema_version' => $active ? (int) $active['version'] : 0,
				'submissions'    => $submissions,
				'last_at'        => $last,
				'failed_jobs'    => $failed,
				'issues'         => $issues,
			];
		}

		return new WP_REST_Response(
			[
				'forms'  => $out,
				'server' => self::server_health(),
			],
			200
		);
	}

	/**
	 * Server-level checks the dashboard surfaces above the per-form table.
	 * The uploads dir ships a deny-all .htaccess, which nginx ignores — on
	 * nginx the admin must add a location block, so say so with the snippet.
	 */
	private static function server_health(): array {
		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : '';
		$is_nginx = false !== strpos( $software, 'nginx' );

		$uploads = wp_upload_dir();
		$dir     = trailingslashit( (string) $uploads['basedir'] ) . 'aae-forms';

		return [
			'software'           => $software,
			'uploads_protection' => $is_nginx ? 'nginx_config_needed' : 'htaccess',
			'has_uploads'        => is_dir( $dir ),
			'nginx_snippet'      => 'location ^~ /wp-content/uploads/aae-forms/ { deny all; }',
		];
	}

	// ------------------------------------------------------------------
	// Email-marketing integrations (Brevo, …)
	// ------------------------------------------------------------------

	/**
	 * List every catalog provider with its connection state. `pro` is true
	 * when a concrete Provider backs the id (real validate/lists available);
	 * `connected` means a key is stored; `key_mask` is the safe display form.
	 */
	public static function list_integrations(): WP_REST_Response {
		$out = [];

		foreach ( Integrations::catalog() as $id => $label ) {
			$provider = Integrations::get( $id );

			$out[] = [
				'id'         => $id,
				'label'      => $label,
				'pro'        => null !== $provider,
				'connected'  => Integrations::has_key( $id ),
				'key_mask'   => Integrations::mask( Integrations::get_key( $id ) ),
				// Attributes for the editor mapping UI (empty until pro backs
				// the id — the editor then shows the "requires Pro" state).
				'attributes' => null !== $provider ? $provider::attributes() : [],
			];
		}

		return new WP_REST_Response( [ 'integrations' => $out ], 200 );
	}

	/**
	 * Save (or clear, with an empty string) a provider's global API key.
	 * When a pro provider backs the id AND a non-empty key is sent, validate
	 * it first — a bad key is rejected without being stored so the UI never
	 * shows a green "connected" over a dead key. Free-only (no provider):
	 * the key is stored as-is and reported unverified.
	 */
	public static function save_integration_key( WP_REST_Request $request ): WP_REST_Response {
		$id      = sanitize_key( (string) $request['id'] );
		$api_key = trim( (string) $request->get_param( 'api_key' ) );

		if ( ! array_key_exists( $id, Integrations::catalog() ) ) {
			return new WP_REST_Response( [ 'message' => __( 'Unknown integration.', 'animation-addons-for-elementor' ) ], 404 );
		}

		// Clearing the key: always allowed.
		if ( '' === $api_key ) {
			Integrations::set_key( $id, '' );
			return new WP_REST_Response(
				[
					'connected' => false,
					'key_mask'  => '',
					'message'   => __( 'Disconnected.', 'animation-addons-for-elementor' ),
				],
				200
			);
		}

		$provider = Integrations::get( $id );

		if ( null !== $provider ) {
			$check = $provider->validate_key( $api_key );
			if ( empty( $check['ok'] ) ) {
				return new WP_REST_Response(
					[
						'connected' => false,
						'message'   => (string) ( $check['message'] ?? __( 'Could not verify the API key.', 'animation-addons-for-elementor' ) ),
					],
					422
				);
			}
		}

		Integrations::set_key( $id, $api_key );

		return new WP_REST_Response(
			[
				'connected' => true,
				'verified'  => null !== $provider,
				'key_mask'  => Integrations::mask( $api_key ),
				'account'   => null !== $provider ? (string) ( $check['account'] ?? '' ) : '',
				'message'   => null !== $provider
					? __( 'Connected.', 'animation-addons-for-elementor' )
					: __( 'Saved. Install the Pro add-on to verify and sync.', 'animation-addons-for-elementor' ),
			],
			200
		);
	}

	/**
	 * Fetch a provider's subscriber lists for the per-form list picker.
	 * Requires the pro provider (real network call) and a stored key.
	 */
	public static function integration_lists( WP_REST_Request $request ): WP_REST_Response {
		$id       = sanitize_key( (string) $request['id'] );
		$provider = Integrations::get( $id );

		if ( null === $provider ) {
			return new WP_REST_Response(
				[
					'requires_pro' => true,
					'lists'        => [],
					'message'      => __( 'This integration needs the Pro add-on.', 'animation-addons-for-elementor' ),
				],
				200
			);
		}

		$api_key = Integrations::get_key( $id );
		if ( '' === $api_key ) {
			return new WP_REST_Response(
				[
					'lists'   => [],
					'message' => __( 'Connect the integration first.', 'animation-addons-for-elementor' ),
				],
				200
			);
		}

		$result = $provider->fetch_lists( $api_key );

		return new WP_REST_Response(
			[
				'lists'   => (array) ( $result['lists'] ?? [] ),
				'message' => (string) ( $result['message'] ?? '' ),
			],
			empty( $result['ok'] ) ? 502 : 200
		);
	}

	// ------------------------------------------------------------------
	// CSV export (admin-post download)
	// ------------------------------------------------------------------

	/** Stream a filtered CSV of submissions (admin-post, nonce-checked). */
	public static function handle_csv_export(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'animation-addons-for-elementor' ) );
		}
		check_admin_referer( 'aae_form_csv' );

		global $wpdb;

		$filters = [
			'form_key' => isset( $_GET['form_key'] ) ? sanitize_text_field( wp_unslash( $_GET['form_key'] ) ) : '',
			'status'   => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'from'     => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			'to'       => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
			's'        => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		];

		$rows = self::query_submissions( $filters, self::CSV_LIMIT, 0 )['rows'];

		$values_by_submission = [];
		$columns              = []; // field_key => header label.
		if ( $rows ) {
			$ids = implode( ',', array_map( static fn( $r ) => (int) $r->id, $rows ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids are ints.
			$values = $wpdb->get_results( 'SELECT submission_id, field_key, field_label, field_value FROM ' . Database::submission_values_table() . " WHERE submission_id IN ({$ids}) ORDER BY id" );

			foreach ( $values as $value ) {
				$values_by_submission[ (int) $value->submission_id ][ $value->field_key ] = $value->field_value;
				if ( ! isset( $columns[ $value->field_key ] ) ) {
					$columns[ $value->field_key ] = '' !== (string) $value->field_label ? (string) $value->field_label : (string) $value->field_key;
				}
			}
		}

		// A clean byte stream: drop anything a notice/warning already printed
		// (it would land INSIDE the .csv) and keep further ones off-stream.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		@ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.display_errors_Disallowed -- file download; errors still go to the log.

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=aae-form-submissions-' . gmdate( 'Ymd-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		// The explicit escape argument silences the PHP 8.1+ "$escape must be
		// provided" deprecation (its default changes in a future PHP).
		fputcsv( $out, array_merge( [ 'ID', 'Form', 'Date', 'Status', 'Source URL' ], array_values( $columns ) ), ',', '"', '\\' );

		foreach ( $rows as $row ) {
			$line = [ $row->id, $row->form_key, $row->created_at, $row->status, $row->source_url ];
			foreach ( array_keys( $columns ) as $key ) {
				// Same humanizing as the list preview: file-field JSON → file
				// names, multi-select JSON → joined values, plain text as-is.
				$line[] = self::preview_value( (string) ( $values_by_submission[ (int) $row->id ][ $key ] ?? '' ) );
			}
			fputcsv( $out, $line, ',', '"', '\\' );
		}

		exit;
	}
}
