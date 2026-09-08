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

final class Admin_Rest {

	const CAP       = 'manage_options';
	const PER_PAGE  = 20;
	const CSV_LIMIT = 5000;

	/**
	 * Every filter query_submissions() understands.
	 *
	 * Both readers are driven from this list so that no caller can hand over a
	 * partial set. The CSV export used to build its own five-key array while
	 * the query read seven keys, which raised two "Undefined array key"
	 * warnings on every download and silently dropped the field-wise filter --
	 * so an export ran wider than the list it was started from.
	 */
	const FILTER_KEYS = [ 'form_key', 'status', 'from', 'to', 's', 'field_key', 'field_value' ];

	/**
	 * "%d,%d,…" for an id list, one placeholder per element.
	 *
	 * $wpdb->prepare() has no placeholder for a variable-length IN () list, so
	 * the run of %d has to be generated and interpolated. The ids themselves
	 * still travel as prepare() arguments — never in the SQL string.
	 */
	private static function in_placeholders( array $ids ): string {
		return implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	}

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

		// Distinct field keys/labels for ONE form — feeds the Submissions
		// filter bar's "Field" dropdown once a form is selected.
		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/form-fields',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'form_fields' ],
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

		// reCAPTCHA v3 — same free-shell/pro-capability split: free stores
		// the site-key/secret-key pair and reports connection state; the
		// real Google siteverify call lives in pro (Captcha::verify()).
		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/recaptcha',
			$admin + [
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ self::class, 'get_recaptcha' ],
			]
		);

		register_rest_route(
			Rest::REST_NAMESPACE,
			'/admin/recaptcha/keys',
			$admin + [
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ self::class, 'save_recaptcha_keys' ],
			]
		);
	}

	// ------------------------------------------------------------------
	// Submissions
	// ------------------------------------------------------------------

	/** Sanitized list filters from the request. */
	private static function read_filters( WP_REST_Request $request ): array {
		$filters = [];

		foreach ( self::FILTER_KEYS as $key ) {
			$filters[ $key ] = sanitize_text_field( (string) $request->get_param( $key ) );
		}

		return $filters;
	}

	/** @return array [ 'rows' => object[], 'total' => int ] */
	private static function query_submissions( array $filters, int $limit, int $offset ): array {
		global $wpdb;

		// Fill anything the caller left out. Union keeps the caller's values;
		// only absent keys take the empty default, and an empty filter is a
		// filter that is not applied.
		$filters += array_fill_keys( self::FILTER_KEYS, '' );

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
			$where[] = 'id IN ( SELECT submission_id FROM %i WHERE field_value LIKE %s )';
			$args[]  = $values;
			$args[]  = '%' . $wpdb->esc_like( $filters['s'] ) . '%';
		}
		if ( '' !== $filters['field_key'] ) {
			// Field-wise filter: ONE field key (e.g. "experience") plus the
			// value to match within it — unlike `s` above, which searches
			// every field's value with no key filter. An empty field_value
			// with a field_key set still narrows to submissions that HAVE
			// that field at all.
			if ( '' !== $filters['field_value'] ) {
				$where[] = 'id IN ( SELECT submission_id FROM %i WHERE field_key = %s AND field_value LIKE %s )';
				$args[]  = $values;
				$args[]  = $filters['field_key'];
				$args[]  = '%' . $wpdb->esc_like( $filters['field_value'] ) . '%';
			} else {
				$where[] = 'id IN ( SELECT submission_id FROM %i WHERE field_key = %s )';
				$args[]  = $values;
				$args[]  = $filters['field_key'];
			}
		}

		// Every fragment above is a literal written in this method; the only
		// things that vary are the VALUES, and they are all in $args, in
		// placeholder order. $where_sql itself never carries request data.
		$where_sql = implode( ' AND ', $where );

		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_sql is assembled from the literal fragments above; every value is a prepare() argument.
			$wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$where_sql}", array_merge( [ $table ], $args ) )
		);

		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- same $where_sql, same argument order, plus the paging pair; the sniff cannot count through array_merge().
			$wpdb->prepare( "SELECT * FROM %i WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( [ $table ], $args, [ $limit, $offset ] ) )
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
			$ids          = array_map( static fn( $r ) => (int) $r->id, $result['rows'] );
			$placeholders = self::in_placeholders( $ids );
			$value_rows   = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is a generated run of %d (see in_placeholders()); the ids are prepare() arguments.
				$wpdb->prepare( "SELECT submission_id, field_value FROM %i WHERE submission_id IN ({$placeholders}) ORDER BY id", array_merge( [ Database::submission_values_table() ], $ids ) )
			);
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
			$wpdb->prepare(
				'SELECT f.form_key, f.post_id, COUNT(s.id) AS submissions'
				. ' FROM %i f'
				. ' LEFT JOIN %i s ON s.form_key = f.form_key'
				. ' GROUP BY f.id, f.form_key, f.post_id ORDER BY f.id',
				Database::forms_table(),
				Database::submissions_table()
			)
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

	/**
	 * Distinct field keys (+ their most recent label) submitted for ONE
	 * form — feeds the Submissions filter bar's "Field" dropdown. Reads the
	 * MAX(id)-per-key row so a renamed field (label changed after some
	 * submissions) shows its current label, not a stale one.
	 *
	 * @return array<int,array{key:string,label:string}>
	 */
	public static function form_fields( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$form_key = sanitize_text_field( (string) $request->get_param( 'form_key' ) );
		if ( '' === $form_key ) {
			return new WP_REST_Response( [ 'fields' => [] ], 200 );
		}

		$values      = Database::submission_values_table();
		$submissions = Database::submissions_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT v.field_key, v.field_label FROM %i v'
				. ' INNER JOIN %i s ON s.id = v.submission_id'
				. ' INNER JOIN ( SELECT field_key, MAX(id) AS max_id FROM %i'
				. ' WHERE submission_id IN ( SELECT id FROM %i WHERE form_key = %s )'
				. ' GROUP BY field_key ) latest ON latest.max_id = v.id'
				. ' WHERE s.form_key = %s'
				. ' ORDER BY v.field_key',
				$values,
				$submissions,
				$values,
				$submissions,
				$form_key,
				$form_key
			)
		);

		$fields = array_map(
			static fn( $row ) => [
				'key'   => (string) $row->field_key,
				'label' => '' !== (string) $row->field_label ? (string) $row->field_label : (string) $row->field_key,
			],
			(array) $rows
		);

		return new WP_REST_Response( [ 'fields' => $fields ], 200 );
	}

	public static function get_submission( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$id = (int) $request['id'];

		$submission = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Database::submissions_table(), $id ) );

		if ( ! $submission ) {
			return new WP_REST_Response( [ 'message' => __( 'Submission not found.', 'animation-addons-for-elementor' ) ], 404 );
		}

		$values = $wpdb->get_results( $wpdb->prepare( 'SELECT field_key, field_label, field_type, field_value FROM %i WHERE submission_id = %d ORDER BY id', Database::submission_values_table(), $id ) );
		$logs   = $wpdb->get_results( $wpdb->prepare( 'SELECT action_type, status, message, created_at FROM %i WHERE submission_id = %d ORDER BY id', Database::action_logs_table(), $id ) );

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

		$ids          = array_values( $ids );
		$placeholders = self::in_placeholders( $ids );

		$deleted = (int) $wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is a generated run of %d (see in_placeholders()); the ids are prepare() arguments.
			$wpdb->prepare( "DELETE FROM %i WHERE id IN ({$placeholders})", array_merge( [ Database::submissions_table() ], $ids ) )
		);
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- same list, same reason.
			$wpdb->prepare( "DELETE FROM %i WHERE submission_id IN ({$placeholders})", array_merge( [ Database::submission_values_table() ], $ids ) )
		);

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

		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE action_type = 'bot_shield'", $table ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, message, request_snapshot, created_at FROM %i WHERE action_type = 'bot_shield' ORDER BY id DESC LIMIT %d OFFSET %d",
				$table,
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

		$total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, submission_id, action_type, status, attempts, next_run_at, updated_at FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
				$table,
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

		$forms = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id', Database::forms_table() ) );

		$out = [];
		foreach ( (array) $forms as $form ) {
			$active = Schema_Store::get_active( (string) $form->form_key );

			$submissions = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_key = %s', Database::submissions_table(), $form->form_key ) );
			$last        = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT created_at FROM %i WHERE form_key = %s ORDER BY id DESC LIMIT 1', Database::submissions_table(), $form->form_key ) );
			$failed      = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i j INNER JOIN %i s ON s.id = j.submission_id WHERE s.form_key = %s AND j.status = %s',
					Database::action_jobs_table(),
					Database::submissions_table(),
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
				// "Where do I get this key?" text + link for the connect card.
				'help'       => Integrations::help( $id ),
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
	// reCAPTCHA v3
	// ------------------------------------------------------------------

	/**
	 * Current reCAPTCHA connection state for the dashboard connect card.
	 * `pro` mirrors the Integrations pattern: true only when a real
	 * verifier is registered (Captcha::verify() reports `available`).
	 */
	public static function get_recaptcha(): WP_REST_Response {
		$has_verifier = is_callable( apply_filters( 'aae_form/recaptcha_verifier', null ) );

		return new WP_REST_Response(
			[
				'pro'         => $has_verifier,
				'connected'   => Captcha::has_keys(),
				'site_key'    => Captcha::site_key(),
				'secret_mask' => Captcha::mask_secret(),
				'help'        => [
					'text' => __( 'Create a reCAPTCHA v3 site in the Google admin console, then paste both keys here.', 'animation-addons-for-elementor' ),
					'url'  => 'https://www.google.com/recaptcha/admin/create',
				],
			],
			200
		);
	}

	/**
	 * Save (or clear) the site-key/secret-key pair. Free stores keys
	 * unverified (no network call available); pro's verifier is only
	 * exercised at actual submit time, not here — Google's siteverify API
	 * has no "check this secret is valid" call without a real token.
	 */
	public static function save_recaptcha_keys( WP_REST_Request $request ): WP_REST_Response {
		$site_key   = trim( (string) $request->get_param( 'site_key' ) );
		$secret_key = trim( (string) $request->get_param( 'secret_key' ) );

		Captcha::set_keys( $site_key, $secret_key );

		return new WP_REST_Response(
			[
				'connected'   => Captcha::has_keys(),
				'site_key'    => Captcha::site_key(),
				'secret_mask' => Captcha::mask_secret(),
				'message'     => Captcha::has_keys()
					? __( 'Saved.', 'animation-addons-for-elementor' )
					: __( 'Cleared.', 'animation-addons-for-elementor' ),
			],
			200
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

		// Read straight after check_admin_referer() above, and every value is
		// unslashed and sanitised here. Driven from FILTER_KEYS so this stays
		// in step with the REST reader.
		$filters = [];

		foreach ( self::FILTER_KEYS as $key ) {
			$filters[ $key ] = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		}

		$rows = self::query_submissions( $filters, self::CSV_LIMIT, 0 )['rows'];

		$values_by_submission = [];
		$columns              = []; // field_key => header label.
		if ( $rows ) {
			$ids          = array_map( static fn( $r ) => (int) $r->id, $rows );
			$placeholders = self::in_placeholders( $ids );
			$values       = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is a generated run of %d (see in_placeholders()); the ids are prepare() arguments.
				$wpdb->prepare( "SELECT submission_id, field_key, field_label, field_value FROM %i WHERE submission_id IN ({$placeholders}) ORDER BY id", array_merge( [ Database::submission_values_table() ], $ids ) )
			);

			foreach ( $values as $value ) {
				$values_by_submission[ (int) $value->submission_id ][ $value->field_key ] = $value->field_value;
				if ( ! isset( $columns[ $value->field_key ] ) ) {
					$columns[ $value->field_key ] = '' !== (string) $value->field_label ? (string) $value->field_label : (string) $value->field_key;
				}
			}
		}

		// A clean byte stream: drop anything a notice/warning already printed
		// (it would land INSIDE the .csv) and keep further ones off-stream.
		//
		// This opens no buffer of its own. It closes the ones already open,
		// which a file download has to do or the CSV is corrupted, and the
		// request ends at the exit below -- so nothing runs afterwards that a
		// changed buffer stack could affect. ob_end_clean() returns false for
		// a buffer PHP will not let us delete, and the loop stops there rather
		// than spinning.
		while ( ob_get_level() > 0 && ob_end_clean() ) {
			continue;
		}

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
