<?php
if (! defined('ABSPATH')) {
	exit;
}

if (function_exists('aaeaddon_register_post_rating_cpt')) {
	return;
}

// Register Post Rating CPT
function aaeaddonlite_register_post_rating_cpt()
{
	if (function_exists('aaeaddon_register_post_rating_cpt')) {
		return;
	}
	if (!wcf_addons_get_settings('wcf_save_widgets', 'post-rating-form')) {
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
		'supports'  => ['title']		
	]);
}

add_action('init', 'aaeaddonlite_register_post_rating_cpt');

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

	if (get_post_type($post_id) !== 'aaeaddon_post_rating') {
		return;
	}

	if (isset($_POST['aae_rating'])) {
		update_post_meta($post_id, 'rating', intval($_POST['aae_rating']));
	}

	if (isset($_POST['aae_review'])) {
		update_post_meta($post_id, 'review', sanitize_text_field(wp_unslash($_POST['aae_review'])));
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
}

add_action('save_post', 'aaeaddon_lite_save_review_meta_box');

// AJAX Handler
add_action('wp_ajax_aaeaddon_submit_post_review_rating', 'handle_lite_post_rating_submission');
add_action('wp_ajax_nopriv_aaeaddon_submit_post_review_rating', 'handle_lite_post_rating_submission');

function handle_lite_post_rating_submission()
{
	if (! isset($_REQUEST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'wcf-addons-frontend')) {
		wp_send_json_error(['message' => 'Security check failed.']);
	}


	if (function_exists('aaeaddon_register_post_rating_cpt')) {
		return;
	}

	if (! isset($_POST['post_id'], $_POST['rating'], $_POST['review'])) {
		wp_send_json_error(['message' => 'Invalid data.']);
	}

	$post_id     = sanitize_text_field(wp_unslash($_POST['post_id']));
	$rating      = sanitize_text_field(wp_unslash($_POST['rating']));
	$review_text = sanitize_text_field(wp_unslash($_POST['review']));

	$user_id     = get_current_user_id();

	$name  = '';
	$email = '';

	if ($user_id) {
		$name  = get_the_author_meta('display_name', $user_id);
		$email = get_the_author_meta('user_email', $user_id);
	} else {
		$name  = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
	}


	$require_approval = isset($_POST['require_approval']) && $_POST['require_approval'] === 'yes';
	$post_status      = $require_approval ? 'pending' : 'publish';

	$post_title = get_the_title($post_id);
	$post_type  = get_post_type($post_id);

	$rating_post_id = wp_insert_post([
		'post_type'   => 'aaeaddon_post_rating',
		'post_title'  => $post_title,
		'post_status' => $post_status,
		'meta_input'  => [
			'post_id'            => $post_id,
			'user_id'            => $user_id,
			'name'               => $name,
			'email'              => $email,
			'rating'             => $rating,
			'review'             => $review_text,
			'reviewed_post_type' => $post_type,
		]
	]);

	$existing_count = (int) get_post_meta($post_id, 'review_count', true);
	update_post_meta($post_id, 'review_count', $existing_count + 1);

	if ($rating_post_id) {
		wp_send_json_success([
			'message' => $require_approval
				? __('Review submitted for approval.', 'animation-addons-for-elementor')
				: __('Review submitted successfully!', 'animation-addons-for-elementor')
		]);
	} else {
		wp_send_json_error([
			'message' => __('Failed to save review.', 'animation-addons-for-elementor')
		]);
	}
}

function aaeaddon_lite_disable_post_rating_title_field($hook)
{
	global $post;

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
