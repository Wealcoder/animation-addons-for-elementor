<?php
/**
 * AAE Forms â€” file uploads (local private storage ONLY, no cloud adapters).
 *
 * Pre-upload flow:
 *   1. POST /aae/v1/forms/{form_key}/uploads (multipart: file, field_key,
 *      nonce) â€” validates against the ACTIVE schema's file field rules,
 *      stores the file in a private uploads subfolder, inserts a PENDING
 *      aae_attachments row, returns { id, key } (key = claim secret).
 *   2. The submit payload carries [{ id, key }] as the file field's value;
 *      the Validator verifies every ref (id+key+form_key+pending).
 *   3. After the submission row exists (aae_form/submission_saved), the
 *      refs are CLAIMED: submission_id set, status â†’ attached.
 *   4. Un-claimed rows older than PENDING_TTL are deleted (file + row) by
 *      a daily cron â€” abandoned uploads never pile up.
 *
 * Storage is wp-content/uploads/aae-forms/{form_key}/ with a deny-all
 * .htaccess + random filenames; downloads go ONLY through the
 * capability-checked proxy route (GET /aae/v1/attachments/{id}/download).
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

final class Uploads {

	/** Un-claimed uploads are deleted after this long. */
	const PENDING_TTL = DAY_IN_SECONDS;

	/** Upload rate limit per hashed visitor + form. */
	const RATE_LIMIT  = 20;
	const RATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/** Fallbacks when the field sets no rule of its own. */
	const DEFAULT_MAX_MB  = 10;
	const DEFAULT_ACCEPT  = 'pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip';

	/**
	 * Never accepted, whatever the field's accept list says â€” executables,
	 * server-side scripts and XSS vectors (svg/html).
	 */
	const BLOCKED_EXTENSIONS = [
		'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht',
		'exe', 'com', 'scr', 'msi', 'dll', 'bat', 'cmd', 'sh', 'bash',
		'pl', 'py', 'rb', 'cgi', 'asp', 'aspx', 'jsp', 'jspx',
		'js', 'mjs', 'vbs', 'wsf', 'hta', 'svg', 'html', 'htm', 'xhtml', 'xml',
	];

	const CLEANUP_HOOK = 'aae_form/cleanup_uploads';

	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );

		// Claim pending uploads once the submission row exists (never before â€”
		// save-before-actions applies to files too).
		add_action( 'aae_form/submission_saved', [ self::class, 'claim_for_submission' ], 5, 5 );

		// Daily orphan sweep.
		add_action( self::CLEANUP_HOOK, [ self::class, 'cleanup' ] );
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	public static function register_routes(): void {
		register_rest_route(
			Rest::REST_NAMESPACE,
			'/forms/' . Rest::KEY_PATTERN . '/uploads',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_upload' ],
				'permission_callback' => '__return_true', // public; nonce + rate limit + schema rules inside.
			]
		);

		// Admin-only download proxy â€” the ONLY way a stored file is served.
		register_rest_route(
			Rest::REST_NAMESPACE,
			'/attachments/(?P<id>\d+)/download',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'handle_download' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/* ------------------------------------------------------------------ */
	/* Public upload endpoint                                              */
	/* ------------------------------------------------------------------ */

	/** POST /forms/{form_key}/uploads â€” one file per request. */
	public static function handle_upload( WP_REST_Request $request ): WP_REST_Response {
		$form_key = (string) $request['form_key'];

		$schema = Schema_Store::get_active_schema( $form_key );
		if ( null === $schema ) {
			return self::error( 404, 'aae_form_not_found', __( 'Form not found.', 'animation-addons-for-elementor' ) );
		}

		// Same origin proof as submit (nonce rides the multipart body).
		$nonce = (string) $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, Rest::NONCE_ACTION ) ) {
			Spam_Log::record( $form_key, 'upload_bad_nonce' );

			return self::error( 403, 'aae_form_security', __( 'We could not upload the file. Please reload the page and try again.', 'animation-addons-for-elementor' ) );
		}

		if ( Token::over_limit( 'upload|' . $form_key, self::RATE_LIMIT, self::RATE_WINDOW ) ) {
			Spam_Log::record( $form_key, 'upload_rate_limited' );

			return self::error( 429, 'aae_form_rate_limited', __( 'Too many uploads. Please wait a moment and try again.', 'animation-addons-for-elementor' ) );
		}

		// The target must be a real file field of the ACTIVE schema â€” its
		// rules (accept/max size), never client-supplied attributes, decide.
		$field_key = sanitize_text_field( (string) $request->get_param( 'field_key' ) );
		$field     = self::file_field( $schema, $field_key );

		if ( null === $field ) {
			return self::error( 400, 'aae_form_bad_request', __( 'This form does not accept file uploads for that field.', 'animation-addons-for-elementor' ) );
		}

		$files = $request->get_file_params();
		$file  = isset( $files['file'] ) && is_array( $files['file'] ) ? $files['file'] : null;

		if ( null === $file || ! isset( $file['tmp_name'], $file['name'] ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return self::error( 400, 'aae_form_bad_request', __( 'No file was received.', 'animation-addons-for-elementor' ) );
		}

		$size   = (int) ( $file['size'] ?? 0 );
		$max_mb = self::max_mb( $field );

		if ( $size <= 0 || $size > $max_mb * MB_IN_BYTES || $size > wp_max_upload_size() ) {
			return self::error(
				422,
				'aae_form_file_too_large',
				sprintf(
					/* translators: %s: size limit in megabytes */
					__( 'File is too large. Maximum size is %s MB.', 'animation-addons-for-elementor' ),
					$max_mb
				)
			);
		}

		$original = sanitize_file_name( (string) $file['name'] );
		$ext      = strtolower( pathinfo( $original, PATHINFO_EXTENSION ) );

		if ( '' === $ext || ! in_array( $ext, self::allowed_extensions( $field ), true ) ) {
			return self::error( 422, 'aae_form_file_type', __( 'This file type is not allowed.', 'animation-addons-for-elementor' ) );
		}

		// Content sniff: the bytes must match the claimed extension for types
		// WP knows â€” a renamed .exe never gets through on extension alone.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $original );
		if ( empty( $check['ext'] ) || strtolower( (string) $check['ext'] ) !== $ext ) {
			return self::error( 422, 'aae_form_file_type', __( 'This file type is not allowed.', 'animation-addons-for-elementor' ) );
		}

		// --- Store in the private folder under a random name. ---------------
		$dir = self::ensure_dir( $form_key );
		if ( '' === $dir ) {
			return self::error( 500, 'aae_form_storage', __( 'We could not store the file. Please try again.', 'animation-addons-for-elementor' ) );
		}

		$stored_name = wp_generate_password( 24, false, false ) . '.' . $ext;
		$destination = trailingslashit( $dir ) . $stored_name;

		$moved = is_uploaded_file( $file['tmp_name'] )
			? move_uploaded_file( $file['tmp_name'], $destination ) // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions -- private dir, validated above.
			: false;

		if ( ! $moved ) {
			return self::error( 500, 'aae_form_storage', __( 'We could not store the file. Please try again.', 'animation-addons-for-elementor' ) );
		}

		$uploads    = wp_upload_dir();
		$rel_path   = ltrim( str_replace( wp_normalize_path( $uploads['basedir'] ), '', wp_normalize_path( $destination ) ), '/' );
		$upload_key = wp_generate_password( 40, false, false );

		global $wpdb;
		$inserted = $wpdb->insert(
			Database::attachments_table(),
			[
				'form_key'      => $form_key,
				'submission_id' => 0,
				'field_key'     => $field_key,
				'upload_key'    => $upload_key,
				'original_name' => $original,
				'stored_path'   => $rel_path,
				'mime'          => (string) ( $check['type'] ?? '' ),
				'size_bytes'    => $size,
				'status'        => 'pending',
				'ip_hash'       => Token::visitor_hash(),
				'created_at'    => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			wp_delete_file( $destination ); // best-effort rollback.

			return self::error( 500, 'aae_form_storage', __( 'We could not store the file. Please try again.', 'animation-addons-for-elementor' ) );
		}

		$response = new WP_REST_Response(
			[
				'id'   => (int) $wpdb->insert_id,
				'key'  => $upload_key,
				'name' => $original,
				'size' => $size,
			],
			201
		);
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	/* ------------------------------------------------------------------ */
	/* Admin download proxy                                                */
	/* ------------------------------------------------------------------ */

	/** GET /attachments/{id}/download â€” streams the file to an admin. */
	public static function handle_download( WP_REST_Request $request ) {
		global $wpdb;

		$id  = (int) $request['id'];
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . Database::attachments_table() . ' WHERE id = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from our own helper.
				$id
			),
			ARRAY_A
		);

		$path = null !== $row ? self::abs_path( (string) $row['stored_path'] ) : '';

		if ( '' === $path || ! file_exists( $path ) ) {
			return self::error( 404, 'aae_form_not_found', __( 'File not found.', 'animation-addons-for-elementor' ) );
		}

		// Stream directly â€” a JSON response can't carry the bytes.
		nocache_headers();
		header( 'Content-Type: ' . ( '' !== (string) $row['mime'] ? (string) $row['mime'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( (string) $row['original_name'] ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a private file through the auth proxy.
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Submit-pipeline integration                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Verify a posted list of upload refs ([{id,key},â€¦]) against pending rows
	 * of this form. Returns normalized value entries for storage â€”
	 * [ [ 'id' => int, 'name' => string, 'size' => int ], â€¦ ] â€” or null when
	 * ANY ref is unknown/foreign/spent (the whole field then fails validation).
	 */
	public static function verify_refs( array $refs, string $form_key ): ?array {
		global $wpdb;

		$out = [];

		foreach ( $refs as $ref ) {
			$id  = isset( $ref['id'] ) ? (int) $ref['id'] : 0;
			$key = isset( $ref['key'] ) && is_string( $ref['key'] ) ? $ref['key'] : '';

			if ( $id <= 0 || '' === $key || strlen( $key ) > 64 || ! ctype_alnum( $key ) ) {
				return null;
			}

			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, original_name, size_bytes FROM ' . Database::attachments_table() . ' WHERE id = %d AND upload_key = %s AND form_key = %s AND status = %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from our own helper.
					$id,
					$key,
					$form_key,
					'pending'
				),
				ARRAY_A
			);

			if ( null === $row ) {
				return null;
			}

			$out[] = [
				'id'   => (int) $row['id'],
				'name' => (string) $row['original_name'],
				'size' => (int) $row['size_bytes'],
			];
		}

		return $out;
	}

	/**
	 * aae_form/submission_saved: attach the submission's verified uploads.
	 * File-field clean values are arrays of ['id'=>â€¦] entries (see
	 * Validator) â€” collect the ids and claim their pending rows.
	 */
	public static function claim_for_submission( $submission_id, $form_key, $clean, $schema, $meta ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- hook signature.
		$submission_id = (int) $submission_id;
		if ( $submission_id <= 0 || ! is_array( $clean ) ) {
			return; // behavior 'email' stores nothing â€” pending rows expire via cron.
		}

		$file_keys = [];
		foreach ( ( is_array( $schema ) ? $schema['fields'] ?? [] : [] ) as $field ) {
			if ( is_array( $field ) && 'file' === ( $field['type'] ?? '' ) ) {
				$file_keys[] = (string) $field['key'];
			}
		}

		$ids = [];
		foreach ( $file_keys as $key ) {
			foreach ( (array) ( $clean[ $key ] ?? [] ) as $entry ) {
				if ( is_array( $entry ) && isset( $entry['id'] ) ) {
					$ids[] = (int) $entry['id'];
				}
			}
		}

		$ids = array_filter( array_unique( $ids ) );
		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . Database::attachments_table() . " SET submission_id = %d, status = 'attached' WHERE form_key = %s AND status = 'pending' AND id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from our helper, placeholders generated to count.
				array_merge( [ $submission_id, $form_key ], $ids )
			)
		);
	}

	/**
	 * Absolute paths for a submission's uploads, keyed by a unique original
	 * filename (wp_mail since 6.2 uses string keys as attachment names).
	 * Used by the Admin Email action to attach submitted files.
	 *
	 * With a submission id, only that submission's attached rows match; the
	 * form_key fallback covers behavior "email" forms, whose verified uploads
	 * are never claimed (no submission row) and stay pending until the cron.
	 *
	 * @param int[]  $ids           Attachment row ids (from the clean value).
	 * @param int    $submission_id 0 for store-less (email-only) forms.
	 * @param string $form_key      Required when $submission_id is 0.
	 * @return array<string,string> [ filename => absolute path ]
	 */
	public static function attachment_paths( array $ids, int $submission_id, string $form_key = '' ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		if ( $submission_id > 0 ) {
			$sql  = 'SELECT original_name, stored_path FROM ' . Database::attachments_table() . " WHERE submission_id = %d AND status = 'attached' AND id IN ({$placeholders})";
			$args = array_merge( [ $submission_id ], $ids );
		} elseif ( '' !== $form_key ) {
			$sql  = 'SELECT original_name, stored_path FROM ' . Database::attachments_table() . " WHERE form_key = %s AND status = 'pending' AND id IN ({$placeholders})";
			$args = array_merge( [ $form_key ], $ids );
		} else {
			return [];
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $args ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table from our helper, placeholders generated to count.
			ARRAY_A
		);

		$out = [];

		foreach ( (array) $rows as $row ) {
			$path = self::abs_path( (string) $row['stored_path'] );
			if ( '' === $path || ! file_exists( $path ) ) {
				continue;
			}

			$name = sanitize_file_name( (string) $row['original_name'] );
			if ( '' === $name ) {
				$name = basename( $path );
			}

			// De-dupe filenames so two "cv.pdf" uploads don't collide as keys.
			$candidate = $name;
			$suffix    = 2;
			while ( isset( $out[ $candidate ] ) ) {
				$dot       = strrpos( $name, '.' );
				$candidate = false === $dot
					? $name . '-' . $suffix
					: substr( $name, 0, $dot ) . '-' . $suffix . substr( $name, $dot );
				$suffix++;
			}

			$out[ $candidate ] = $path;
		}

		return $out;
	}

	/**
	 * Delete still-pending upload rows + files of a form. Used by the Admin
	 * Email action after a successful send on a store-less ("Email Only")
	 * form: the email was the delivery, nothing else can ever reach these
	 * files (no submission row â†’ no dashboard link), so don't let personal
	 * data linger for the 24h TTL.
	 *
	 * @param int[] $ids Attachment row ids.
	 */
	public static function purge_pending( array $ids, string $form_key ): void {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) || '' === $form_key ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, stored_path FROM ' . Database::attachments_table() . " WHERE form_key = %s AND status = 'pending' AND id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from our helper, placeholders generated to count.
				array_merge( [ $form_key ], $ids )
			),
			ARRAY_A
		);

		foreach ( (array) $rows as $row ) {
			$path = self::abs_path( (string) $row['stored_path'] );
			if ( '' !== $path && file_exists( $path ) ) {
				wp_delete_file( $path ); // best-effort sweep.
			}

			$wpdb->delete( Database::attachments_table(), [ 'id' => (int) $row['id'] ], [ '%d' ] );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Cleanup                                                             */
	/* ------------------------------------------------------------------ */

	/** Daily cron: delete never-claimed uploads (row + file) past the TTL. */
	public static function cleanup(): void {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::PENDING_TTL );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, stored_path FROM ' . Database::attachments_table() . " WHERE status = 'pending' AND created_at < %s LIMIT 500", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from our own helper.
				$cutoff
			),
			ARRAY_A
		);

		foreach ( (array) $rows as $row ) {
			$path = self::abs_path( (string) $row['stored_path'] );
			if ( '' !== $path && file_exists( $path ) ) {
				wp_delete_file( $path ); // best-effort sweep.
			}

			$wpdb->delete( Database::attachments_table(), [ 'id' => (int) $row['id'] ], [ '%d' ] );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	/** The schema's file field with this key, or null. */
	private static function file_field( array $schema, string $field_key ): ?array {
		if ( '' === $field_key ) {
			return null;
		}

		foreach ( $schema['fields'] ?? [] as $field ) {
			if ( is_array( $field ) && 'file' === ( $field['type'] ?? '' ) && $field_key === (string) ( $field['key'] ?? '' ) ) {
				return $field;
			}
		}

		return null;
	}

	/** Field max size in MB (falls back to the plugin default). */
	public static function max_mb( array $field ): float {
		$mb = (float) ( $field['max_size'] ?? 0 );

		return $mb > 0 ? $mb : (float) self::DEFAULT_MAX_MB;
	}

	/**
	 * Lower-case extension whitelist for a field: its accept list (".pdf,
	 * .jpg" or "pdf,jpg") intersected with sanity, minus the hard blacklist.
	 */
	public static function allowed_extensions( array $field ): array {
		$accept = strtolower( trim( (string) ( $field['accept'] ?? '' ) ) );
		$raw    = '' !== $accept ? $accept : self::DEFAULT_ACCEPT;

		$extensions = [];
		foreach ( explode( ',', $raw ) as $part ) {
			$part = trim( $part, " \t.*" );
			if ( '' !== $part && preg_match( '/^[a-z0-9]{1,10}$/', $part ) ) {
				$extensions[] = $part;
			}
		}

		return array_values( array_diff( array_unique( $extensions ), self::BLOCKED_EXTENSIONS ) );
	}

	/** Private storage dir for a form; creates + hardens it. '' on failure. */
	private static function ensure_dir( string $form_key ): string {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return '';
		}

		$dir = trailingslashit( $uploads['basedir'] ) . 'aae-forms/' . sanitize_file_name( $form_key );

		if ( ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		// Deny direct HTTP access (Apache 2.2 + 2.4); nginx users must map
		// the folder to `deny all` â€” documented limitation of htaccess-only.
		$htaccess = trailingslashit( $uploads['basedir'] ) . 'aae-forms/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			@file_put_contents( $htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- one-time hardening file.
		}

		$index = trailingslashit( $uploads['basedir'] ) . 'aae-forms/index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- listing guard.
		}

		return $dir;
	}

	/** Absolute path for a stored_path (validated to stay inside uploads). */
	private static function abs_path( string $stored_path ): string {
		if ( '' === $stored_path || false !== strpos( $stored_path, '..' ) ) {
			return '';
		}

		$uploads = wp_upload_dir();

		return trailingslashit( wp_normalize_path( $uploads['basedir'] ) ) . ltrim( wp_normalize_path( $stored_path ), '/' );
	}

	private static function error( int $status, string $code, string $message ): WP_REST_Response {
		$response = new WP_REST_Response(
			[
				'success' => false,
				'code'    => $code,
				'message' => $message,
			],
			$status
		);
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}
}
