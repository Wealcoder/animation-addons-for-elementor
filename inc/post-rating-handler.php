<?php
/**
 * Post Rating Handler
 *
 * @package AnimationAddons
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */

if (! defined('ABSPATH')) {
	exit;
}

if (function_exists('aaeaddon_register_post_rating_cpt')) {
	return;
}

// Only register Post Rating CPT and AJAX handler if the widget feature is enabled
if (function_exists('wcf_addons_get_settings') && wcf_addons_get_settings('wcf_save_widgets', 'post-rating-form')) {
	add_action('init', 'aaeaddonlite_register_post_rating_cpt');
	add_action('wp_ajax_aaeaddon_submit_post_review_rating', 'handle_lite_post_rating_submission');
}

// Register Post Rating CPT
function aaeaddonlite_register_post_rating_cpt()
{
	if (function_exists('aaeaddon_register_post_rating_cpt')) {
		return;
	}
	if (!function_exists('wcf_addons_get_settings') || !wcf_addons_get_settings('wcf_save_widgets', 'post-rating-form')) {
		return;
	}
	register_post_type('aaeaddon_post_rating', [
		'labels'    => [
			'name'          => esc_html__('Post Ratings', 'animation-addons-for-elementor'),
			'singular_name' => esc_html__('Post Rating', 'animation-addons-for-elementor'),
		],
		'public'    => false,
		'show_ui'   => true,
		'menu_icon' => 'dashicons-star-filled',
		'supports'  => ['title'],
	]);
}

// Remove "Add New" from admin menu
add_action('admin_menu', function () {
	remove_submenu_page('edit.php?post_type=aaeaddon_post_rating', 'post-new.php?post_type=aaeaddon_post_rating');
});

// Admin Columns
function aaeaddon_lite_post_rating_columns($columns)
{
	return [
		'cb'                 => '<input type="checkbox" />',
		'title'              => esc_html__('Post Title', 'animation-addons-for-elementor'),
		'reviewed_post_type' => esc_html__('Post Type', 'animation-addons-for-elementor'),
		'name'               => esc_html__('Author', 'animation-addons-for-elementor'),
		'rating'             => esc_html__('Rating', 'animation-addons-for-elementor'),
		'review'             => esc_html__('Review', 'animation-addons-for-elementor'),
		'date'               => esc_html__('Date', 'animation-addons-for-elementor'),
	];
}

add_filter('manage_aaeaddon_post_rating_posts_columns', 'aaeaddon_lite_post_rating_columns');

function aaeaddon_lite_post_rating_custom_column_content($column, $post_id)
{
	switch ($column) {
		case 'reviewed_post_type':
			$type = get_post_meta($post_id, 'reviewed_post_type', true);
			echo $type ? esc_html($type) : 'N/A';
			break;

		case 'name':
			$user_id = get_post_meta($post_id, 'user_id', true);
			if ($user_id) {
				$name = get_the_author_meta('display_name', $user_id);
			} else {
				$name = get_post_meta($post_id, 'name', true);
			}
			echo esc_html($name ?: 'Anonymous');
			break;

		case 'rating':
			echo intval(get_post_meta($post_id, 'rating', true)) ?: 'N/A';
			break;

		case 'review':
			echo esc_html(get_post_meta($post_id, 'review', true)) ?: 'N/A';
			break;
	}
}

add_action('manage_aaeaddon_post_rating_posts_custom_column', 'aaeaddon_lite_post_rating_custom_column_content', 10, 2);

// Admin Meta Box for Editing Fields
function aaeaddon_lite_add_review_meta_boxes()
{
	add_meta_box('aaeaddon_review_details', esc_html__('Review Details', 'animation-addons-for-elementor'), 'aaeaddon_lite_review_meta_box_callback', 'aaeaddon_post_rating', 'normal', 'default');
}

add_action('add_meta_boxes', 'aaeaddon_lite_add_review_meta_boxes');

function aaeaddon_lite_review_meta_box_callback($post)
{
	$user_id = get_post_meta($post->ID, 'user_id', true);
	$name    = get_post_meta($post->ID, 'name', true);
	$email   = get_post_meta($post->ID, 'email', true);
	$rating  = get_post_meta($post->ID, 'rating', true);
	$review  = get_post_meta($post->ID, 'review', true);

	wp_nonce_field('aaeaddon_review_meta_box', 'aaeaddon_review_meta_box_nonce');

?>
	<p>
		<label><strong>Name:</strong></label><br>
		<input type="text" name="aae_name"
			value="<?php echo esc_attr($user_id ? get_the_author_meta('display_name', $user_id) : $name); ?>"
			<?php echo $user_id ? 'readonly' : ''; ?> class="widefat" />
	</p>
	<p>
		<label><strong><?php echo esc_html__('Email:', 'animation-addons-for-elementor') ?></strong></label><br>
		<input type="email" name="aae_email"
			value="<?php echo esc_attr($user_id ? get_the_author_meta('user_email', $user_id) : $email); ?>"
			<?php echo $user_id ? 'readonly' : ''; ?> class="widefat" />
	</p>
	<p>
		<label><strong><?php echo esc_html__('Rating (1-5):', 'animation-addons-for-elementor')  ?></strong></label><br>
		<input type="number" name="aae_rating" value="<?php echo esc_attr($rating); ?>" min="1" max="5"
			class="small-text" />
	</p>
	<p>
		<label><strong><?php echo esc_html__('Review:', 'animation-addons-for-elementor') ?> </strong></label><br>
		<textarea name="aae_review" rows="5" class="widefat"><?php echo esc_textarea($review); ?></textarea>
	</p>
<?php
}

function aaeaddon_lite_save_review_meta_box($post_id)
{
	if (function_exists('aaeaddon_register_post_rating_cpt')) {
		return;
	}
	
	$nonce = isset($_POST['aaeaddon_review_meta_box_nonce']) ? sanitize_text_field(wp_unslash($_POST['aaeaddon_review_meta_box_nonce'])) : '';

	if (! $nonce || ! wp_verify_nonce($nonce, 'aaeaddon_review_meta_box')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (! current_user_can('edit_post', $post_id)) {
		return;
	}

	if (get_post_type($post_id) !== 'aaeaddon_post_rating') {
		return;
	}

	if (isset($_POST['aae_rating'])) {
		$rating = max(1, min(5, intval($_POST['aae_rating'])));
		update_post_meta($post_id, 'rating', $rating);
	}

	if (isset($_POST['aae_review'])) {
		update_post_meta($post_id, 'review', sanitize_textarea_field(wp_unslash($_POST['aae_review'])));
	}

	$user_id = get_post_meta($post_id, 'user_id', true);
	if (! $user_id) {
		if (isset($_POST['aae_name'])) {
			update_post_meta($post_id, 'name', sanitize_text_field(wp_unslash($_POST['aae_name'])));
		}
		if (isset($_POST['aae_email'])) {
			update_post_meta($post_id, 'email', sanitize_email(wp_unslash($_POST['aae_email'])));
		}
	}

	$target_post_id = get_post_meta($post_id, 'post_id', true);
	if ($target_post_id) {
		aaeaddon_sync_post_review_count((int) $target_post_id);
	}
}

add_action('save_post', 'aaeaddon_lite_save_review_meta_box');

/**
 * Synchronize post review count based strictly on published ratings.
 *
 * @param int $post_id Target post ID.
 * @return int Review count.
 */
function aaeaddon_sync_post_review_count($post_id)
{
	$post_id = absint($post_id);
	if (! $post_id) {
		return 0;
	}

	$query = new WP_Query([
		'post_type'      => 'aaeaddon_post_rating',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => 'post_id',
				'value' => $post_id,
			],
		],
		'no_found_rows'  => false,
	]);

	$count = (int) $query->found_posts;
	update_post_meta($post_id, 'review_count', $count);

	return $count;
}

/**
 * Keep review count in sync when rating post status changes.
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ($post && 'aaeaddon_post_rating' === $post->post_type && $new_status !== $old_status) {
		$target_post_id = get_post_meta($post->ID, 'post_id', true);
		if ($target_post_id) {
			aaeaddon_sync_post_review_count((int) $target_post_id);
		}
	}
}, 10, 3);

/**
 * Keep review count in sync when rating post is deleted.
 */
add_action('deleted_post', function ($post_id) {
	$post = get_post($post_id);
	if ($post && 'aaeaddon_post_rating' === $post->post_type) {
		$target_post_id = get_post_meta($post_id, 'post_id', true);
		if ($target_post_id) {
			aaeaddon_sync_post_review_count((int) $target_post_id);
		}
	}
});

// AJAX Handler for Rating Submissions
function handle_lite_post_rating_submission()
{
	if (function_exists('aaeaddon_register_post_rating_cpt')) {
		return;
	}

	// 1. Feature enablement check
	if (!function_exists('wcf_addons_get_settings') || !wcf_addons_get_settings('wcf_save_widgets', 'post-rating-form')) {
		wp_send_json_error(['message' => esc_html__('Post rating feature is disabled.', 'animation-addons-for-elementor')], 403);
	}

	// 2. Nonce verification
	$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
	if (! $nonce || ! wp_verify_nonce($nonce, 'wcf-addons-frontend')) {
		wp_send_json_error(['message' => esc_html__('Security check failed.', 'animation-addons-for-elementor')], 403);
	}

	// 3. Validate required POST inputs
	if (! isset($_POST['post_id'], $_POST['rating'])) {
		wp_send_json_error(['message' => esc_html__('Invalid submission data.', 'animation-addons-for-elementor')]);
	}

	$post_id = absint(sanitize_text_field(wp_unslash($_POST['post_id'])));
	if (! $post_id) {
		wp_send_json_error(['message' => esc_html__('Invalid target post ID.', 'animation-addons-for-elementor')]);
	}

	$target_post = get_post($post_id);
	if (! $target_post || 'publish' !== $target_post->post_status || in_array($target_post->post_type, ['aaeaddon_post_rating', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'], true)) {
		wp_send_json_error(['message' => esc_html__('Target post not found or not eligible for ratings.', 'animation-addons-for-elementor')]);
	}

	// 4. Validate rating range (1-5)
	$rating = intval(sanitize_text_field(wp_unslash($_POST['rating'])));
	if ($rating < 1 || $rating > 5) {
		wp_send_json_error(['message' => esc_html__('Rating must be an integer between 1 and 5.', 'animation-addons-for-elementor')]);
	}

	// 5. Sanitize review text
	$review_text = isset($_POST['review']) ? sanitize_textarea_field(wp_unslash($_POST['review'])) : '';

	// 6. User authentication check (Strictly Logged-in users only)
	if (! is_user_logged_in()) {
		wp_send_json_error(['message' => esc_html__('Only logged-in users can submit a review.', 'animation-addons-for-elementor')], 403);
	}

	$user_id = get_current_user_id();
	$user    = get_userdata($user_id);
	$name    = $user ? $user->display_name : '';
	$email   = $user ? $user->user_email : '';

	// 7. Prevent rapid duplicate submissions (Rate limiting)
	$rate_lock_key = 'aae_rate_lock_' . md5('user_' . $user_id . '_' . $post_id);

	if (get_transient($rate_lock_key)) {
		wp_send_json_error(['message' => esc_html__('You have submitted a review recently. Please wait a moment before trying again.', 'animation-addons-for-elementor')]);
	}

	// 8. Moderation status:
	// Check server-side widget setting (if available) or default to requiring approval ('pending').
	$require_approval = true;
	if (isset($widget_settings['require_approval'])) {
		$require_approval = ('yes' === $widget_settings['require_approval']);
	}

	$post_status = $require_approval ? 'pending' : 'publish';

	// 9. Insert rating post
	$rating_post_id = wp_insert_post([
		'post_type'   => 'aaeaddon_post_rating',
		'post_title'  => wp_strip_all_tags($target_post->post_title),
		'post_status' => $post_status,
		'meta_input'  => [
			'post_id'            => $post_id,
			'user_id'            => $user_id,
			'name'               => $name,
			'email'              => $email,
			'rating'             => $rating,
			'review'             => $review_text,
			'reviewed_post_type' => $target_post->post_type,
		],
	], true);

	if (is_wp_error($rating_post_id) || ! $rating_post_id) {
		wp_send_json_error(['message' => esc_html__('Failed to save review. Please try again.', 'animation-addons-for-elementor')]);
	}

	// Set transient lock for 30 seconds
	set_transient($rate_lock_key, 1, 30);

	// Sync published review count
	aaeaddon_sync_post_review_count($post_id);

	wp_send_json_success([
		'message' => $require_approval
			? esc_html__('Review submitted for approval.', 'animation-addons-for-elementor')
			: esc_html__('Review submitted successfully!', 'animation-addons-for-elementor'),
	]);
}

function aaeaddon_lite_disable_post_rating_title_field($hook)
{
	$screen = get_current_screen();
	if (! $screen || $screen->post_type !== 'aaeaddon_post_rating') {
		return;
	}

	wp_enqueue_script(
		'admin-post-rating',
		WCF_ADDONS_URL . 'assets/js/admin-post-rating.js',
		[],
		WCF_ADDONS_VERSION,
		true
	);
}
add_action('admin_enqueue_scripts', 'aaeaddon_lite_disable_post_rating_title_field');
