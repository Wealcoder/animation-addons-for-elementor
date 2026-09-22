<?php

namespace Wealcoder\AnimationAddons\Extensions;

use Wealcoder\AnimationAddons\Nonce;
use ZipArchive;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * The Custom Icon extension's wp-admin half.
 *
 * Split out of class-custom-icon.php. Everything below can only fire in
 * wp-admin (the icon-set menu/metaboxes/columns, the four wp_ajax_ writers, the
 * Elementor icon-picker tab), so CustomIcons_Lite requires this file under
 * is_admin() and a visitor's request never parses it. admin-ajax IS is_admin(),
 * so the writers still answer. The front-end CPT registration and icon-CSS
 * enqueue, plus the shared createUniqueSlug() helper, stay on CustomIcons_Lite.
 *
 * Deliberately NOT PSR-4 named: it sits beside the class it was cut from and the
 * one require_once in that constructor is the gate.
 */
class CustomIcons_Icon_Admin
{
	public $configs = [];
	public $meta_key = 'wcf_addon_custom_icons';
	public $meta_active_key = 'aae_gl_load';
	public $post_type = 'wcf-custom-icons';
	public $process_id = null;
	public $file_name = null;
	public $icon_prefix = null;
	public $icon_postfix = null;
	public $all_posts = [];

	/** @var CustomIcons_Icon_Admin|null */
	private static $instance = null;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct()
	{
		add_action('admin_menu', [$this, 'register_sub_menu_post'], 30);
		add_action('add_meta_boxes', [$this, 'custom_metabox']);
		add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
		add_action('wp_ajax_aaeaddon_custom_icon_settings_state', [$this, 'settings_state']);
		add_action('wp_ajax_aaeaddon_upload_custom_icon_zip', [$this, 'upload_zip']);
		add_action('wp_ajax_aaeaddon_update_custom_icon_title', [$this, 'update_custom_icon_title']);
		add_action('wp_ajax_aaeaddon_update_custom_icon_delete', [$this, 'update_custom_icon_delete']);
		add_action('admin_head', [$this, 'add_default_title_script']);
		add_filter('elementor/icons_manager/additional_tabs', [$this, 'icon_manager']);
		add_filter('post_row_actions', [$this, 'remove_quick_edit_button'], 10, 2);
		add_filter('display_post_states', [$this, 'remove_post_states'], 10, 2);
		add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'add_custom_column']);
		add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'display_column_content'], 10, 2);
		add_action('save_post_' . $this->post_type, [$this, 'update_option_based_on_status'], 10, 3);
	}



	/**
	 * Update the `aae_gl_load` POST META (the per-icon-set switch) when the set is saved
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 * @param bool    $update
	 */
	public function update_option_based_on_status($post_id, $post, $update)
	{

		// Avoid autosave/revision loops
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return;
		}


		// Set meta based on status
		if ($post->post_status === 'publish') {
			update_post_meta($post_id, $this->meta_active_key, 'yes');
		} else {
			update_post_meta($post_id, $this->meta_active_key, 'no');
		}
	}


	/**
	 * Displays custom content for the custom post type columns.
	 *
	 * @param string $column The column name.
	 * @param int $post_id The post ID.
	 * @since 1.0.0
	 */
	function display_column_content($column, $post_id)
	{
		if ($column === 'aae_icontype') {
			$custom_field_value = get_post_meta($post_id, 'wcf_addon_custom_icontype', true);
			if ($custom_field_value) {
				echo esc_html($custom_field_value);
			} else {
				echo esc_html__('Unknown', 'animation-addons-for-elementor');
			}
		}
		if ($column === 'aae_actions') {
			$switcher_value = get_post_meta($post_id, 'aae_gl_load', true); // Check the state from custom field			
			$checked = ($switcher_value === 'yes') ? 'checked' : ''; // Default is unchecked (0)			
			// Switcher (checkbox) for toggle button
			echo '<label class="aae_switch" title="' . esc_attr__('Load Icons Across the Site, if disable , icon will load depends on elementor usage', 'animation-addons-for-elementor') . '">
					<input type="checkbox" data-post-id="' . esc_attr($post_id) . '" class="aaeaddon-global-load-switcher-toggle switcher-toggle" ' . esc_html($checked) . ' />
					<span class="aae_slider round"></span>
				  </label>';
		}
	}

	/**
	 * Adds custom columns to the custom post type list table.
	 *
	 * @param array $columns Existing columns for the custom post type.
	 * @return array Modified columns.
	 * @since 1.0.0
	 */
	function add_custom_column($columns)
	{
		$columns = array_slice($columns, 0, 2, true) + ['aae_actions' => esc_html__('Active', 'animation-addons-for-elementor')] + array_slice($columns, 2, null, true);
		$columns = array_slice($columns, 0, 2, true) + ['aae_icontype' => esc_html__('Type', 'animation-addons-for-elementor')] + array_slice($columns, 2, null, true);
		return $columns;
	}

	/**
	 * Removes unnecessary post states for the custom post type.
	 *
	 * @param array $states Current post states.
	 * @param WP_Post $post Current post object.
	 * @return array Modified post states.
	 * @since 1.0.0
	 */
	function remove_post_states($states, $post)
	{
		if (isset($post->post_type) && $post->post_type === $this->post_type) {
			return [];
		}
		return $states;
	}
	/**
	 * Removes the quick edit button for the custom post type.
	 *
	 * @param array $actions Available actions for the post.
	 * @param WP_Post $post Current post object.
	 * @return array Modified actions.
	 * @since 1.0.0
	 */
	function remove_quick_edit_button($actions, $post)
	{
		// Replace 'your_custom_post_type' with your actual custom post type slug
		if ($post->post_type === $this->post_type) {
			unset($actions['inline hide-if-no-js']); // Remove the Quick Edit button
			unset($actions['edit']); // Remove the Quick Edit button
		}
		return $actions;
	}

	/**
	 * Integrates custom icons with Elementor's icon manager.
	 *
	 * @param array $settings The current icon manager settings.
	 * @return array Modified icon manager settings.
	 * @since 1.0.0
	 */
	function icon_manager($settings)
	{

		$args = [
			'numberposts' => 15, // Limit number of posts
			'post_type' => $this->post_type,
			'post_status' => 'any',
			// Listing custom-icon posts by their marker meta key; capped at 15 posts.
			'meta_key'   => 'wcf_addon_custom_icons', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		];

		$posts = get_posts($args);
		if (is_array($posts)) {
			foreach ($posts as $post) {
				if (get_post_meta($post->ID, 'wcf_addon_custom_icontype', true) == 'icomoon') {
					$slug =	CustomIcons_Lite::createUniqueSlug($post->post_title);
					$metainfo = get_post_meta($post->ID, 'wcf_addon_custom_icons', true);

					if (isset($metainfo['elementor_path']) && isset($metainfo['elementor_style'])) {
						$json_file  = wp_upload_dir()['basedir'] . '/' . $metainfo['elementor_path'];
						$json_data  = wp_upload_dir()['baseurl'] . '/' . $metainfo['elementor_path'];
						$style_file = wp_upload_dir()['basedir'] . '/' . $metainfo['elementor_style'];
						$style      = wp_upload_dir()['baseurl'] . '/' . $metainfo['elementor_style'];

						if (file_exists($json_file) && file_exists($style_file)) {
							$settings[$slug] = [
								'name'          => $slug,
								'label'         => $post->post_title,
								'enqueue'       => [$style],
								'prefix'        => '',
								'displayPrefix' => '',
								'labelIcon'     => 'fab fa-font-awesome-alt',
								'ver'           => '2.0',
								'fetchJson'     => $json_data
							];
						}
					}
				}
			}
		}

		return $settings;
	}
	/**
	 * Adds a default title script to the post editor page.
	 *
	 * @since 1.0.0
	 */
	function add_default_title_script()
	{
		$screen = get_current_screen();

		// Only enqueue the script on the post editor screen
		if ($screen->post_type === $this->post_type && $screen->base === 'post') {
?>
			<script>
				function aaeaddontriggerKeyInput(targetElement, text) {
					if (!targetElement) return;
					// Set the text directly to the input value
					targetElement.value = text;
					// Dispatch input and change events to ensure any listeners are triggered
					targetElement.dispatchEvent(new Event('input', {
						bubbles: true
					}));
					targetElement.dispatchEvent(new Event('change', {
						bubbles: true
					}));
					targetElement.focus();
				}

				document.addEventListener('DOMContentLoaded', function() {
					const titleInput = document.getElementById('title');
					if (titleInput) {
						setTimeout(() => {
							if (titleInput.value === '') {
								aaeaddontriggerKeyInput(titleInput, 'Change Title');
							} else {
								aaeaddontriggerKeyInput(titleInput, titleInput.value);
							}

						}, 900);
					}
				});
			</script>

<?php
		}
	}

	/**
	 * Updates the custom icon settings.
	 *
	 * @since 1.0.0
	 */
	public function settings_state()
	{

		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['option_name'])) {
			return;
		}

		if (! isset($_POST['option_value'])) {
			return;
		}

		if (! isset($_POST['post_id'])) {
			return;
		}
		$process_id   = absint( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
		$option_value = sanitize_text_field( wp_unslash( $_POST['option_value'] ) );
		$option_name  = sanitize_key( wp_unslash( $_POST['option_name'] ) );

		$allowed_options = array( 'aae_gl_load', 'wcf_addon_custom_icontype', 'wcf_addon_custom_icon_active' );
		if ( ! in_array( $option_name, $allowed_options, true ) ) {
			wp_send_json_error( esc_html__( 'Invalid option name.', 'animation-addons-for-elementor' ) );
		}

		update_post_meta( $process_id, $option_name, $option_value );
		wp_send_json_success( esc_html__( 'Update Settings', 'animation-addons-for-elementor' ) );
	}

	/**
	 * Handles uploading of custom icon zip files.
	 *
	 * @since 1.0.0
	 */
	public function upload_zip()
	{
		// 1) Security: nonce + capability
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' =>esc_html__( 'You are not allowed to do this action.', 'animation-addons-for-elementor' ),
			], 400 );
			
		}

		// 2) Validate inputs
		if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
			wp_send_json_error( [
				'message' =>esc_html__( 'Missing process ID.', 'animation-addons-for-elementor' ),
			], 400 );			
		}
		$this->process_id = sanitize_key(wp_unslash($_POST['id'])); // safe for dir names

		if ( empty( $_FILES['custom_icon'] ) || ! isset( $_FILES['custom_icon']['error'] ) ) {
			wp_send_json_error( [
				'message' =>esc_html__( 'No file uploaded.', 'animation-addons-for-elementor' ),
			], 400 );				
		}
		if ( UPLOAD_ERR_OK !== (int) $_FILES['custom_icon']['error'] ) {
			wp_send_json_error( [
				'message' =>esc_html__( 'Upload error.', 'animation-addons-for-elementor' ),
			], 400 );
		}

		// 3) Use WP upload API with strict MIME. wp_handle_upload() is used a
		// few lines down; file.php is only loaded when core has not already.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'zip' => 'application/zip',
				// some browsers send zip like below:
				'x-zip' => 'application/x-zip-compressed',
			),
		);

		// Sanitize filename (extra safety)
		$_FILES['custom_icon']['name'] = isset($_FILES['custom_icon']['name']) ? sanitize_file_name(wp_unslash($_FILES['custom_icon']['name'])) : 'n/a';

		$uploaded = wp_handle_upload($_FILES['custom_icon'], $overrides);

		if ( ! $uploaded || isset( $uploaded['error'] ) ) {
			wp_send_json_error( [
				'message' =>esc_html__( 'Upload failed or invalid file type (zip required).', 'animation-addons-for-elementor' ),
			], 400 );			
		}

		$zip_file_path = $uploaded['file'];     // absolute path to uploaded zip
		$this->file_name = wp_basename($zip_file_path);

		// 4) Prepare target directory
		$uploads      = wp_upload_dir();
		$target_dir   = trailingslashit($uploads['basedir']) . 'aaeaddon-icons/' . $this->process_id . '/';

		if ( ! wp_mkdir_p( $target_dir ) ) {
			if ( file_exists( $zip_file_path ) ) {
				wp_delete_file( $zip_file_path );
			}
			wp_send_json_error( [
				'message' =>esc_html__( 'Failed to create target directory.', 'animation-addons-for-elementor' ),
			], 400 );			
		}

		// 2) Init WP_Filesystem (this may ask for creds if not direct)
		$url   = admin_url( 'admin-ajax.php' ); // or current admin page URL
		$creds = request_filesystem_credentials( $url, '', false, ABSPATH, [] );

		if ( false === $creds ) {			
			wp_send_json_error( [
				'message' => $msg ?: esc_html__( 'Filesystem credentials required.', 'animation-addons-for-elementor' ),
			], 400 );
		}

		if ( ! WP_Filesystem( $creds ) ) {
			// Try again to prompt for correct creds
			request_filesystem_credentials( $url, '', true, ABSPATH, [] );
			wp_send_json_error( [
				'message' => $msg ?: esc_html__( 'Could not initialize WordPress filesystem.', 'animation-addons-for-elementor' ),
			], 400 );
			
		}
		
		// 5) Unzip safely with WP API.
		//
		// unzip_file() is supplied by file.php. It is NOT in
		// class-wp-upgrader.php, which used to be loaded here for it and
		// supplies nothing this method uses, so that require is gone.
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$unzipped = unzip_file( $zip_file_path, $target_dir );
		
		// Always remove the uploaded zip after processing
		if ( file_exists( $zip_file_path ) ) {
			wp_delete_file( $zip_file_path );
		}

		if ( is_wp_error( $unzipped ) ) {
			$msg = $unzipped->get_error_message();
			wp_send_json_error( [
				'message' => $msg ?: esc_html__( 'Failed to unzip the file.', 'animation-addons-for-elementor' ),
			], 400 );
		}

		// 6) An icon pack is an ordinary zip and unzip_file() writes whatever
		// is inside it into a PUBLIC uploads folder, .php included. An admin
		// uploading a pack they downloaded somewhere is not consenting to run
		// its code, so anything an IcoMoon export does not need goes now,
		// before a single line of it can be requested.
		$this->prune_extracted( $target_dir );

		// 7) Process extracted files (your custom logic)
		$msg = $this->process_icon_files($target_dir); // should return string/array
		update_option('aaeaddon_gl_load', 'yes');
		// 7) Done
		wp_send_json_success(array(
			'message'   => $msg ?: esc_html__('Icons processed successfully.', 'animation-addons-for-elementor'),
			'processId' => $this->process_id,
		));
	}


	/**
	 * File types an IcoMoon export actually needs: the manifest, the
	 * stylesheet, and the font faces it points at. Everything else that
	 * arrives in the zip is deleted after extraction.
	 *
	 * @since 2.4.0
	 * @var string[]
	 */
	const ICON_ALLOWED_EXT = array( 'json', 'css', 'svg', 'ttf', 'woff', 'woff2', 'eot', 'otf', 'txt' );

	/**
	 * Delete every extracted file whose extension is not in
	 * self::ICON_ALLOWED_EXT. Runs straight after unzip_file(), so a pack
	 * carrying a .php (or .html, or .htaccess) never becomes reachable over
	 * HTTP even for the moment between extraction and processing.
	 *
	 * @param string $dir Absolute path to the extracted pack.
	 * @since 2.4.0
	 * @return void
	 */
	private function prune_extracted( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( $item->isDir() ) {
				continue;
			}

			$ext = strtolower( pathinfo( $item->getFilename(), PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, self::ICON_ALLOWED_EXT, true ) ) {
				wp_delete_file( $item->getPathname() );
			}
		}
	}

	/**
	 * Processes icon files from the uploaded zip.
	 *
	 * @param string $upload_dir The upload directory path.
	 * @return string Result message.
	 * @since 1.0.0
	 */
	function process_icon_files($upload_dir)
	{

		$icomoon_path = $upload_dir . 'selection.json';

		if (file_exists($icomoon_path)) {
			return $this->icomoon_file_process($icomoon_path, $upload_dir);
		}

		return esc_html__('Unsupported Icon File', 'animation-addons-for-elementor');
	}

	public function icomoon_file_process($json_path, $upload_dir)
	{
		// Initialize WP Filesystem
		if (! function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;

		// WP_Filesystem is initialised directly above; reading through it
		// keeps this on the same API the write below already uses.
		$json_raw  = $wp_filesystem->get_contents($json_path);
		$json_data = $json_raw ? json_decode($json_raw, true) : [];
		$icons = [];
		$this->icon_prefix = $json_data['preferences']['fontPref']['prefix'];
		$this->icon_postfix = $json_data['preferences']['fontPref']['postfix'];

		foreach ($json_data['icons'] as $icon) {
			if (isset($icon['properties']['name'])) {
				$icon_name = explode(',', $icon['properties']['name']);
				foreach ($icon_name as $iitem) {
					$trimi = trim($iitem);
					$formatted_icon = "aaeaddon-icon {$this->icon_prefix}{$trimi}{$this->icon_postfix}";
					$icons[] = $formatted_icon;
				}
			}
		}

		$elementor_file = 'elementor-icon.js';
		$file_path = $upload_dir . $elementor_file;
		$output_data = wp_json_encode(['icons' => $icons], JSON_PRETTY_PRINT);

		// Write file using WP_Filesystem
		if ($wp_filesystem->put_contents($file_path, $output_data, FS_CHMOD_FILE)) {
			delete_post_meta($this->process_id, 'wcf_addon_custom_icons');
		} else {
			return esc_html__('Failed to create file.', 'animation-addons-for-elementor');
		}

		update_post_meta($this->process_id, 'wcf_addon_custom_icons', [
			'name'            => $this->file_name,
			'folder_path'     => 'aaeaddon-icons/' . $this->process_id . '/',
			'elementor_path'  => 'aaeaddon-icons/' . $this->process_id . '/' . $elementor_file,
			'elementor_style' => 'aaeaddon-icons/' . $this->process_id . '/style.css',
			'icon_prefix'     => $this->icon_prefix,
			'icon_postfix'    => $this->icon_postfix
		]);
		update_post_meta($this->process_id, 'wcf_addon_custom_icontype', 'icomoon');
		return esc_html__('File has been Created Successfully.', 'animation-addons-for-elementor');
	}

	public function update_custom_icon_delete()
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['id'])) {
			return;
		}
		// The id names a directory under uploads/aaeaddon-icons/ that is deleted
		// RECURSIVELY. sanitize_text_field() keeps "../", so it must be the
		// same shape upload_zip() wrote it as: one path segment, nothing else.
		$this->process_id = sanitize_key( wp_unslash( $_POST['id'] ) );
		if ( '' === $this->process_id || false !== strpos( $this->process_id, '.' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid icon pack.', 'animation-addons-for-elementor' ) ), 400 );
		}
		$msg = $this->delete_uploads_directory('aaeaddon-icons/' . $this->process_id);
		delete_post_meta($this->process_id, 'wcf_addon_custom_icons');
		delete_post_meta($this->process_id, 'wcf_addon_custom_icontype');
		wp_send_json($msg);
	}

	function delete_uploads_directory($dir_name)
	{
		// Initialize the WordPress filesystem.
		if (! function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		// Get the upload directory path.
		$upload_dir = wp_get_upload_dir();
		$target_dir = trailingslashit($upload_dir['basedir']) . $dir_name;

		// Check if the directory exists.
		if ($wp_filesystem->is_dir($target_dir)) {
			// Attempt to delete the directory and its contents.
			$deleted = $wp_filesystem->delete($target_dir, true);

			if ($deleted) {
				return esc_html__('Directory deleted successfully.', 'animation-addons-for-elementor');
			} else {
				return esc_html__('Failed to delete the directory.', 'animation-addons-for-elementor');
			}
		} else {
			return esc_html__('Directory does not exist.', 'animation-addons-for-elementor');
		}
	}

	public function update_custom_icon_title()
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['custom_font_global'])) {
			return;
		}

		if (! isset($_POST['id'])) {
			return;
		}
		$sanitize_id = sanitize_text_field(wp_unslash($_POST['id']));
		$title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : 'no title';

		$updated_post = array(
			'ID'         => $sanitize_id,
			'post_title' => $title,
		);

		// Update the post in the database
		wp_update_post($updated_post);
		wp_send_json(esc_html__('Updated', 'animation-addons-for-elementor'));
	}
	public function admin_scripts()
	{
		$current_screen = get_current_screen();
		if (isset($current_screen->id) && $current_screen->id == 'edit-wcf-custom-icons') {
			wp_enqueue_style('wcf-addon-pro-custom-icons', AAEADDON_URL . 'assets/css/list.css', array(), AAEADDON_VERSION);
			wp_enqueue_script('wcf-addon-pro-custom-icons', AAEADDON_URL . 'assets/js/list-actions.js', array('jquery'), AAEADDON_VERSION, true);
		}
		if (isset($current_screen->id) && $current_screen->id == 'wcf-custom-icons') {
			wp_enqueue_media();
			wp_enqueue_style('wcf-addon-pro-custom-icons', AAEADDON_URL . 'assets/build/modules/custom-icon/main.css', array( \Wealcoder\AnimationAddons\Aaeaddon_Fonts::ensure() ), AAEADDON_VERSION);
			wp_enqueue_script('wcf-addon-pro-custom-icons', AAEADDON_URL . 'assets/build/modules/custom-icon/main.js', array(
				'react',
				'react-dom',
				'wp-element',
				'wp-i18n'
			), AAEADDON_VERSION, true);
		}

		if (isset($current_screen->id) && ($current_screen->id == 'edit-wcf-custom-icons' || $current_screen->id == 'wcf-custom-icons')) {
			$localize_data = [
				'ajaxurl'     => admin_url('admin-ajax.php'),
				'nonce'       => Nonce::create( Nonce::ADMIN ),
				'id'          => get_the_id(),
				'custom_icon' => get_post_meta(get_the_id(), 'wcf_addon_custom_icons', true)
			];
			wp_localize_script('wcf-addon-pro-custom-icons', 'WCF_ADDONS_ADMIN', $localize_data);
		}
	}

	function custom_metabox()
	{

		add_meta_box(
			'wcf_proaddon_custom_icons_metabox',
			esc_html__('Settings', 'animation-addons-for-elementor'),
			[$this, 'metabox_callback'],
			$this->post_type,
			'normal',
			'high'
		);
	}

	public function metabox_callback()
	{
		// translate="no": browser page translation wraps every text node in <font>,
		// and React's next commit then removes a node that has moved -- it throws and
		// unmounts the whole root, i.e. a blank screen. Reported on a WPML site, whose
		// admin <html lang> is not English, so Chrome offers to translate this page.
		// See plugin_dashboard_entry_page() in inc/admin/dashboard.php.
				echo '<div id="wcf--custom-icons-meta-box" class="notranslate" translate="no">Loading</div>';
	}

	public function metabox_side_settings_callback()
	{
		echo '<div id="wcf--custom-icons-meta-box-side-setting" class="notranslate" translate="no">Loading</div>';
	}

	public function register_sub_menu_post()
	{

		add_submenu_page('aaeaddon_page', esc_html__('Custom Icons', 'animation-addons-for-elementor'), esc_html__('Custom Icons', 'animation-addons-for-elementor'), 'manage_options', "edit.php?post_type=$this->post_type", null);
	}
}
