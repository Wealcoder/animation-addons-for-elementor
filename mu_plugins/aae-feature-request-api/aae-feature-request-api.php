<?php

/**
 * Plugin Name: AAE Feature Request API
 * Description: Receives "Request New Feature" submissions posted from the Animation Addons for Elementor dashboard (https://animation-addons.com/api/request-new-feature) and stores each one as a Feature Request post.
 * Version: 1.0.0
 * Author: Animation Addons
 * Text Domain: aae-feature-request-api
 *
 * INSTALL THIS ON animation-addons.com ONLY — it is the receiving end. The
 * sending end is the `wcf_request_new_feature` AJAX handler inside the
 * Animation Addons for Elementor plugin, which forwards each submission here
 * server-to-server.
 *
 * ON THE SHARED KEY: the client plugin ships to wordpress.org, so whatever key
 * it carries is extractable from a public zip. AAEFR_API_KEY keeps casual
 * traffic out; it is NOT authentication. The rate limit and the duplicate
 * guard below are what actually protect this endpoint, which is why they are
 * here and not optional.
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Override in wp-config.php to rotate without touching the plugin:
 *
 *     define( 'AAEFR_API_KEY', '…' );
 *
 * Rotating means updating the same constant in the client plugin.
 */
if (! defined('AAEFR_API_KEY')) {
	define('AAEFR_API_KEY', '0700c72d204521236f5af03011cb0cbb4f6229a6bbdc2ef041d76184e9a795b7');
}

const AAEFR_POST_TYPE = 'aae_feature_request';
const AAEFR_QUERY_VAR = 'aaefr_feature_request';
const AAEFR_ROUTE     = 'api/request-new-feature';

/*
 * Submissions allowed per requester per window, and the window in seconds.
 *
 * Counts FILED requests only — a rejected call never consumes allowance, see
 * aaefr_rate_limit_record(). The window slides from the most recent accepted
 * request, so nobody can be locked out permanently.
 *
 * Overridable from wp-config.php, so the live site can be tuned from the
 * server without editing (and later re-editing) plugin code:
 *
 *     define( 'AAEFR_RATE_LIMIT', 20 );
 *
 * NOTE on proxies: the bucket is keyed on REMOTE_ADDR. Behind Cloudflare or a
 * load balancer that can be the PROXY's address for every visitor, collapsing
 * everyone into one bucket — i.e. this becomes a site-wide cap. Confirm what
 * REMOTE_ADDR actually holds on the live host before trusting the per-
 * requester behaviour.
 */
if (! defined('AAEFR_RATE_LIMIT')) {
	define('AAEFR_RATE_LIMIT', 20);
}

if (! defined('AAEFR_RATE_WINDOW')) {
	define('AAEFR_RATE_WINDOW', HOUR_IN_SECONDS);
}

// Hard caps, applied after sanitising. A request over the cap is truncated
// rather than rejected — the submitter cannot fix a limit they were never told.
const AAEFR_MAX_NAME    = 100;
const AAEFR_MAX_FEATURE = 5000;

/**
 * Register the "Feature Request" CPT if it isn't already registered.
 *
 * @return void
 */
function aaefr_register_cpt()
{
	if (post_type_exists(AAEFR_POST_TYPE)) {
		return;
	}

	register_post_type(
		AAEFR_POST_TYPE,
		array(
			'labels'            => array(
				'name'               => __('Feature Requests', 'aae-feature-request-api'),
				'singular_name'      => __('Feature Request', 'aae-feature-request-api'),
				'menu_name'          => __('Feature Requests', 'aae-feature-request-api'),
				'all_items'          => __('All Requests', 'aae-feature-request-api'),
				'view_item'          => __('View Request', 'aae-feature-request-api'),
				'search_items'       => __('Search Requests', 'aae-feature-request-api'),
				'not_found'          => __('No feature requests found.', 'aae-feature-request-api'),
				'not_found_in_trash' => __('No feature requests found in Trash.', 'aae-feature-request-api'),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_admin_bar' => false,
			'show_in_rest'      => false,
			'menu_icon'         => 'dashicons-lightbulb',
			'capability_type'   => 'post',
			'map_meta_cap'      => true,
			'hierarchical'      => false,
			'supports'          => array('title', 'editor'),
			'has_archive'       => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);
}
add_action('init', 'aaefr_register_cpt');

/**
 * Add the custom rewrite rule for the clean /api/request-new-feature URL.
 *
 * @return void
 */
function aaefr_add_rewrite_rule()
{
	add_rewrite_rule('^' . AAEFR_ROUTE . '/?$', 'index.php?' . AAEFR_QUERY_VAR . '=1', 'top');
}
add_action('init', 'aaefr_add_rewrite_rule');

/**
 * A permalink-independent second door onto the same handler.
 *
 * The pretty route depends on rewrite rules, which a permalink flush, a
 * migration, or a plain-permalink site can lose. This one cannot break that
 * way, so the client always has somewhere to fall back to.
 *
 * @return void
 */
function aaefr_register_rest_route()
{
	register_rest_route(
		'aae/v1',
		'/request-new-feature',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true', // Authenticated by the API key below.
			'callback'            => function (WP_REST_Request $request) {
				// Take the body from WP_REST_Request, never php://input — the
				// REST server has already consumed that stream.
				$result = aaefr_process_submission($request->get_json_params());
				return new WP_REST_Response($result['body'], $result['status']);
			},
		)
	);
}
add_action('rest_api_init', 'aaefr_register_rest_route');

/**
 * WordPress' own canonical-redirect logic runs on template_redirect
 * before our handler (it already skips non-GET requests, which is why
 * POST reaches aaefr_handle_request() untouched). Disable it for our
 * query var too, so a stray GET/HEAD gets our 405 instead of a redirect.
 *
 * @param string $redirect_url
 * @return string|false
 */
function aaefr_bypass_canonical_redirect($redirect_url)
{
	if (get_query_var(AAEFR_QUERY_VAR)) {
		return false;
	}
	return $redirect_url;
}
add_filter('redirect_canonical', 'aaefr_bypass_canonical_redirect');

/**
 * Register the query var the rewrite rule maps to.
 *
 * @param array $vars
 * @return array
 */
function aaefr_query_vars($vars)
{
	$vars[] = AAEFR_QUERY_VAR;
	return $vars;
}
add_filter('query_vars', 'aaefr_query_vars');

/**
 * On activation: make sure the CPT + rewrite rule exist in this request
 * before flushing, so the clean URL works immediately without needing a
 * manual visit to Settings > Permalinks.
 *
 * @return void
 */
function aaefr_activate()
{
	aaefr_register_cpt();
	aaefr_add_rewrite_rule();
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'aaefr_activate');

/**
 * Flush rewrite rules on deactivation so the custom route is cleanly
 * removed rather than left dangling.
 *
 * @return void
 */
function aaefr_deactivate()
{
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'aaefr_deactivate');

/**
 * Send a JSON response and stop execution, matching how the rest of
 * WordPress' own AJAX/REST handlers behave.
 *
 * @param array $payload
 * @param int   $status_code
 * @return void
 */
function aaefr_json_response($payload, $status_code = 200)
{
	status_header($status_code);
	nocache_headers(); // A page cache replaying a 201 would swallow real requests.
	header('Content-Type: application/json; charset=utf-8');
	echo wp_json_encode($payload);
	exit;
}

/**
 * A stable, non-reversible per-requester key for rate limiting.
 *
 * Hashed with the site's own salt so no raw IP is ever written to the
 * options table, and truncated because collisions across 32 hex chars are
 * not a concern for a counter.
 *
 * @return string
 */
function aaefr_requester_hash()
{
	$ip = isset($_SERVER['REMOTE_ADDR'])
		? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
		: 'unknown';

	return substr(wp_hash($ip), 0, 32);
}

/**
 * @return string
 */
function aaefr_rate_limit_key()
{
	return 'aaefr_rl_' . aaefr_requester_hash();
}

/**
 * Has this requester used up their allowance?
 *
 * Read-only on purpose — see aaefr_rate_limit_record().
 *
 * @return bool True when the request should be refused.
 */
function aaefr_rate_limit_exceeded()
{
	return (int) get_transient(aaefr_rate_limit_key()) >= AAEFR_RATE_LIMIT;
}

/**
 * Count one FILED request against the allowance.
 *
 * Deliberately separate from the check, and called only once a request has
 * actually been stored. Counting rejections instead would mean five malformed
 * posts — a buggy client, a mid-rollout schema change — could exhaust a real
 * user's quota before their first valid submission ever arrived. The limit is
 * on requests filed, not on HTTP calls made; a rejected call is refused before
 * it touches the database, so it is cheap enough not to need metering.
 *
 * set_transient() resets the TTL, so the window slides from the most recent
 * accepted request. That can never lock anyone out permanently.
 *
 * @return void
 */
function aaefr_rate_limit_record()
{
	$key = aaefr_rate_limit_key();
	set_transient($key, (int) get_transient($key) + 1, AAEFR_RATE_WINDOW);
}

/**
 * Validate and store one submission.
 *
 * Returns rather than exits so both the pretty route and the REST route can
 * share it — there is exactly one copy of these rules.
 *
 * @param array|null $data Decoded JSON body, supplied by the caller so each
 *                         route reads the body the way its own stack expects.
 * @return array{status:int, body:array}
 */
function aaefr_process_submission($data)
{
	$provided_key = isset($_SERVER['HTTP_X_API_KEY'])
		? trim(wp_unslash($_SERVER['HTTP_X_API_KEY']))
		: '';

	if ('' === $provided_key || ! hash_equals(AAEFR_API_KEY, $provided_key)) {
		return array(
			'status' => 401,
			'body'   => array(
				'success' => false,
				'message' => __('Invalid or missing API key.', 'aae-feature-request-api'),
			),
		);
	}

	if (aaefr_rate_limit_exceeded()) {
		return array(
			'status' => 429,
			'body'   => array(
				'success' => false,
				'message' => __('Too many requests. Please try again later.', 'aae-feature-request-api'),
			),
		);
	}

	if (! is_array($data)) {
		return array(
			'status' => 400,
			'body'   => array(
				'success' => false,
				'message' => __('Invalid JSON body.', 'aae-feature-request-api'),
			),
		);
	}

	$name    = isset($data['name']) ? sanitize_text_field($data['name']) : '';
	$email   = isset($data['email']) ? sanitize_email($data['email']) : '';
	$feature = isset($data['feature']) ? sanitize_textarea_field($data['feature']) : '';
	$site    = isset($data['site']) ? esc_url_raw($data['site']) : '';
	$version = isset($data['version']) ? sanitize_text_field($data['version']) : '';

	if ('' === $name || '' === $feature || '' === $email || ! is_email($email)) {
		return array(
			'status' => 400,
			'body'   => array(
				'success' => false,
				'message' => __('Missing or invalid fields. Name, a valid email, and a feature description are required.', 'aae-feature-request-api'),
			),
		);
	}

	// Truncate on multibyte boundaries so a clipped request is still readable.
	$name    = function_exists('mb_substr') ? mb_substr($name, 0, AAEFR_MAX_NAME) : substr($name, 0, AAEFR_MAX_NAME);
	$feature = function_exists('mb_substr') ? mb_substr($feature, 0, AAEFR_MAX_FEATURE) : substr($feature, 0, AAEFR_MAX_FEATURE);

	/*
	 * A double-clicked Submit, or the client retrying after a timeout it saw
	 * but the server did not, must not create a second post. Same email plus
	 * same text inside the window is answered as a success without storing
	 * anything, so the submitter still sees confirmation.
	 */
	$fingerprint = 'aaefr_fp_' . md5(strtolower($email) . '|' . $feature);
	$existing_id = get_transient($fingerprint);

	if ($existing_id) {
		return array(
			'status' => 200,
			'body'   => array(
				'success'   => true,
				'message'   => __('Feature request received.', 'aae-feature-request-api'),
				'id'        => (int) $existing_id,
				'duplicate' => true,
			),
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => AAEFR_POST_TYPE,
			'post_title'   => wp_trim_words($feature, 10, '…'),
			'post_content' => $feature,
			'post_status'  => 'publish',
		),
		true
	);

	if (is_wp_error($post_id)) {
		return array(
			'status' => 500,
			'body'   => array(
				'success' => false,
				'message' => __('Could not save the request.', 'aae-feature-request-api'),
			),
		);
	}

	update_post_meta($post_id, '_aaefr_requester_name', $name);
	update_post_meta($post_id, '_aaefr_requester_email', $email);
	update_post_meta($post_id, '_aaefr_requester_site', $site);
	update_post_meta($post_id, '_aaefr_plugin_version', $version);

	set_transient($fingerprint, $post_id, DAY_IN_SECONDS);
	aaefr_rate_limit_record();

	return array(
		'status' => 201,
		'body'   => array(
			'success' => true,
			'message' => __('Feature request received.', 'aae-feature-request-api'),
			'id'      => $post_id,
		),
	);
}

/**
 * Handle POST requests to /api/request-new-feature.
 *
 * @return void
 */
function aaefr_handle_request()
{
	if (! get_query_var(AAEFR_QUERY_VAR)) {
		return;
	}

	if (! isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
		header('Allow: POST');
		aaefr_json_response(
			array(
				'success' => false,
				'message' => __('Method not allowed.', 'aae-feature-request-api'),
			),
			405
		);
	}

	$result = aaefr_process_submission(
		json_decode(file_get_contents('php://input'), true)
	);

	aaefr_json_response($result['body'], $result['status']);
}
add_action('template_redirect', 'aaefr_handle_request');

/**
 * Add Requester / Email / Site / Version columns to the list table.
 *
 * @param array $columns
 * @return array
 */
function aaefr_admin_columns($columns)
{
	$date = $columns['date'];
	unset($columns['date']);

	$columns['aaefr_requester'] = __('Requester', 'aae-feature-request-api');
	$columns['aaefr_email']     = __('Email', 'aae-feature-request-api');
	$columns['aaefr_site']      = __('Site', 'aae-feature-request-api');
	$columns['aaefr_version']   = __('Plugin Ver.', 'aae-feature-request-api');
	$columns['date']            = $date;

	return $columns;
}
add_filter('manage_' . AAEFR_POST_TYPE . '_posts_columns', 'aaefr_admin_columns');

/**
 * Render the custom Feature Requests list table columns.
 *
 * @param string $column
 * @param int    $post_id
 * @return void
 */
function aaefr_admin_column_content($column, $post_id)
{
	switch ($column) {
		case 'aaefr_requester':
			echo esc_html(get_post_meta($post_id, '_aaefr_requester_name', true));
			break;

		case 'aaefr_email':
			$email = get_post_meta($post_id, '_aaefr_requester_email', true);
			if ($email) {
				echo '<a href="' . esc_url('mailto:' . $email) . '">' . esc_html($email) . '</a>';
			}
			break;

		case 'aaefr_site':
			$site = get_post_meta($post_id, '_aaefr_requester_site', true);
			if ($site) {
				echo '<a href="' . esc_url($site) . '" target="_blank" rel="noopener noreferrer">' . esc_html($site) . '</a>';
			}
			break;

		case 'aaefr_version':
			echo esc_html(get_post_meta($post_id, '_aaefr_plugin_version', true));
			break;
	}
}
add_action('manage_' . AAEFR_POST_TYPE . '_posts_custom_column', 'aaefr_admin_column_content', 10, 2);
