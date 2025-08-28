<?php

namespace WCF_ADDONS\Extensions;

use ZipArchive;
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if(class_exists('\WCF_ADDONS\Extensions\CustomIcons')){
    return;
}

/**
 * Class CustomIcons
 *
 * Handles custom icon management for the plugin, including uploading and processing custom icon zip files,
 * managing icon settings, and integrating with Elementor's icon manager.
 *
 * @package WCF_ADDONS\Extensions
 * @since 1.0.0
 */

class CustomIcons_Lite
{

	/** @var array Configuration settings for custom icons */
	public $configs = [];

	/** @var string Meta key for custom icons data */
	public $meta_key = 'wcf_addon_custom_icons';

	/** @var string Custom post type for managing custom icons */
	public $post_type = 'wcf-custom-icons';

	/** @var string|null Process ID for current icon management */
	public $process_id = null;

	/** @var string|null The uploaded icon file name */
	public $file_name = null;

	/** @var string|null The prefix for icon class names */
	public $icon_prefix = null;

	/** @var string|null The postfix for icon class names */
	public $icon_postfix = null;	
	public $all_posts = [];	

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.2.0
	 * @access public
	 */
	public static function instance()
	{

		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *  Plugin class constructor
	 *
	 * Register plugin action hooks and filters
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct()
	{
		// Load plugin text domain for translations
		
		add_action( 'init', [ $this, 'custom_post_type' ] );
		add_action( 'admin_menu', [ $this, 'register_sub_menu_post' ], 30 );
		add_action( 'add_meta_boxes', [ $this, 'custom_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this,'frontend_scripts' ] , 7 );
		add_action( 'wp_ajax_aaeaddon_custom_icon_settings_state', [ $this, 'settings_state' ] );
		add_action( 'wp_ajax_aaeaddon_upload_custom_icon_zip', [ $this, 'upload_zip' ] );
		add_action( 'wp_ajax_aaeaddon_update_custom_icon_title', [ $this, 'update_custom_icon_title' ] );
		add_action( 'wp_ajax_aaeaddon_update_custom_icon_delete', [ $this, 'update_custom_icon_delete' ] );	
		add_action( 'admin_head', [ $this , 'add_default_title_script' ] );
		add_filter( 'elementor/icons_manager/additional_tabs', [ $this,'icon_manager' ] );
		add_filter( 'post_row_actions', [$this,'remove_quick_edit_button' ], 10, 2 );
		add_filter( 'display_post_states', [$this,'remove_post_states' ], 10, 2 );
		add_filter( 'manage_'.$this->post_type.'_posts_columns', [ $this,'add_custom_column' ] );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', [ $this , 'display_column_content' ], 10, 2 );
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
				echo esc_html__('Unknown', 'animation-addons-for-elementor-pro');
			}
		}
		if ($column === 'aae_actions') {		
			$switcher_value = get_post_meta($post_id, 'aae_gl_load', true); // Check the state from custom field			
			$checked = ($switcher_value === 'yes') ? 'checked' : ''; // Default is unchecked (0)			
			// Switcher (checkbox) for toggle button
			echo '<label class="aae_switch" title="' . esc_attr__('Load Icons Across the Site, if disable , icon will load depends on elementor usage', 'animation-addons-for-elementor-pro') . '">
					<input type="checkbox" data-post-id="' . esc_attr($post_id) . '" class="aaeaddon-global-load-switcher-toggle switcher-toggle" ' . $checked . ' />
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
		$columns = array_slice($columns, 0, 2, true) + ['aae_actions' => esc_html__('Action', 'animation-addons-for-elementor-pro')] + array_slice($columns, 2, null, true);
		$columns = array_slice($columns, 0, 2, true) + ['aae_icontype' => esc_html__('Type', 'animation-addons-for-elementor-pro')] + array_slice($columns, 2, null, true);
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
	function remove_post_states($states, $post) {		
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
	function remove_quick_edit_button($actions, $post) {
		// Replace 'your_custom_post_type' with your actual custom post type slug
		if ($post->post_type === $this->post_type) {
			unset($actions['inline hide-if-no-js']); // Remove the Quick Edit button
			unset($actions['edit']); // Remove the Quick Edit button
		}
		return $actions;
	}

	/**
     * Creates a unique slug from a post title.
     *
     * @param string $title The post title.
     * @return string The generated slug.
     * @since 1.0.0
     */
	function createUniqueSlug($title) {
		$slug = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($title)));
		$slug = rtrim($slug, '-');	
		$uniqueSlug = 'aae'.$slug.'-iset';	
		return $uniqueSlug;
	}

 	/**
     * Integrates custom icons with Elementor's icon manager.
     *
     * @param array $settings The current icon manager settings.
     * @return array Modified icon manager settings.
     * @since 1.0.0
     */
	function icon_manager($settings){        
	
		$args = [
			'numberposts' => 15, // Limit number of posts
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'meta_key'   => 'wcf_addon_custom_icons',
		];

		$posts = get_posts($args);
		if(is_array($posts)) {
			foreach ($posts as $post) {		
				if(get_post_meta( $post->ID ,'wcf_addon_custom_icontype', true ) == 'icomoon'){
					$slug =	$this->createUniqueSlug($post->post_title);		
					$metainfo = get_post_meta( $post->ID ,'wcf_addon_custom_icons', true );	
					
					if(isset($metainfo['elementor_path']) && isset($metainfo['elementor_style'])){
						$json_file  = wp_upload_dir()['basedir'] .'/' .$metainfo['elementor_path'];
						$json_data  = wp_upload_dir()['baseurl'] .'/' .$metainfo['elementor_path'];
						$style_file = wp_upload_dir()['basedir'] .'/' .$metainfo['elementor_style'];
						$style      = wp_upload_dir()['baseurl'] .'/' .$metainfo['elementor_style'];
					
						if(file_exists($json_file) && file_exists($style_file)){					
							$settings[$slug] = [
								'name'          => $slug,
								'label'         => $post->post_title,								
								'enqueue'       => [ $style ],
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
			        targetElement.dispatchEvent(new Event('input', { bubbles: true }));
			        targetElement.dispatchEvent(new Event('change', { bubbles: true }));			
			        targetElement.focus();
			    }
				
			    document.addEventListener('DOMContentLoaded', function () {
			        const titleInput = document.getElementById('title');				
			        if (titleInput ) {	
			            setTimeout(() => {
				            if(titleInput.value === ''){
								aaeaddontriggerKeyInput(titleInput, 'Change Title');	
				            }else{
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

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor-pro'));
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
		$process_id = sanitize_text_field(wp_unslash($_POST['post_id']));		
		$option_value = sanitize_text_field(wp_unslash($_POST['option_value']));		
		$option_name = sanitize_text_field(wp_unslash($_POST['option_name']));	
       update_post_meta($process_id, $option_name, $option_value);
	   wp_send_json(esc_html__('Update Settings', 'animation-addons-for-elementor-pro'));
	}

	/**
     * Handles uploading of custom icon zip files.
     *
     * @since 1.0.0
     */
	public function upload_zip()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor-pro'));
		}

		if (! isset($_FILES['custom_icon'])) {
			return;
		}

		if (! isset($_POST['id'])) {
			return;
		}
		
		$this->process_id = sanitize_text_field(wp_unslash($_POST['id']));		
		$file = $_FILES['custom_icon'];
		$upload_dir = wp_upload_dir()['basedir'] . '/aaeaddon-icons/'.$this->process_id . '/';	
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0755, true);
		}	
		$zip_path = $upload_dir . $file['name'];	
		$this->file_name = $file['name'];
		$msg = '';
		// Move uploaded file
		if (move_uploaded_file($file['tmp_name'], $zip_path)) {
			$zip = new ZipArchive;
			if ($zip->open($zip_path) === TRUE) {
				$zip->extractTo($upload_dir);
				$zip->close();
				unlink($zip_path); // Clean up the zip file
				$msg = $this->process_icon_files($upload_dir);				
			} else {
				wp_send_json_error(esc_html__('Failed to extract ZIP file.','animation-addons-for-elementor-pro'));
			}
		}		

		wp_send_json($msg);
	}
	 /**
     * Processes icon files from the uploaded zip.
     *
     * @param string $upload_dir The upload directory path.
     * @return string Result message.
     * @since 1.0.0
     */
	function process_icon_files($upload_dir) {	

		$icomoon_path = $upload_dir . 'selection.json';	

		if(file_exists($icomoon_path)){
		 	return $this->icomoon_file_process($icomoon_path, $upload_dir);
		}
		
		return esc_html__('Unsupported Icon File','animation-addons-for-elementor-pro');
	}
	
	public function icomoon_file_process($json_path,$upload_dir){
		$json_data = json_decode(file_get_contents($json_path), true);
		$icons = [];
		$this->icon_prefix = $json_data['preferences']['fontPref']['prefix'];
		$this->postfix = $json_data['preferences']['fontPref']['postfix'];

		foreach ($json_data['icons'] as $icon) {

			if (isset($icon['properties']['name'])) {

				$icon_name      = explode(',' , $icon['properties']['name'] );
				foreach($icon_name as $iitem){
					$trimi = trim($iitem);
					$formatted_icon = "aaeaddon-icon {$this->icon_prefix}{$trimi}{$this->postfix}";
					$icons[]        = $formatted_icon;
				}				

			}
		}

		$elementor_file = 'elementor-icon.js';
		$file_path      = $upload_dir . $elementor_file;
		$output_data    = json_encode(['icons' => $icons], JSON_PRETTY_PRINT);

		$file = fopen($file_path, 'w'); // 'w' mode createfilename: filename: s or overwrites the file
		if ($file) {
		    fwrite($file, $output_data);
		    fclose($file);	
			delete_post_meta($this->process_id, 'wcf_addon_custom_icons');	   
		} else {
			error_log("Failed to write to $file_path. Check file permissions.");
		    return esc_html__('Failed to create file.','animation-addons-for-elementor-pro');
		}
		update_post_meta($this->process_id, 'wcf_addon_custom_icons',[
			'name'           => $this->file_name,
			'folder_path'    => 'aaeaddon-icons/'.$this->process_id . '/',
			'elementor_path' => 'aaeaddon-icons/'.$this->process_id . '/'.$elementor_file,
			'elementor_style' => 'aaeaddon-icons/'.$this->process_id . '/style.css',
			'icon_prefix'    => $this->icon_prefix,
			'icon_postfix'   => $this->icon_postfix
		]);
		update_post_meta($this->process_id, 'wcf_addon_custom_icontype', 'icomoon');
		return esc_html__('File has been Created Successfully.','animation-addons-for-elementor-pro');
	}

	public function update_custom_icon_delete()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor-pro'));
		}

		if (! isset($_POST['id'])) {
			return;
		}
		$this->process_id = sanitize_text_field(wp_unslash($_POST['id']));
		$msg = $this->delete_uploads_directory('aaeaddon-icons/'.$this->process_id);	
		delete_post_meta($this->process_id, 'wcf_addon_custom_icons');
		delete_post_meta($this->process_id, 'wcf_addon_custom_icontype');
		wp_send_json($msg);
	}

	function delete_uploads_directory( $dir_name ) {
        // Initialize the WordPress filesystem.
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();
        global $wp_filesystem;

        // Get the upload directory path.
        $upload_dir = wp_get_upload_dir();
        $target_dir = trailingslashit( $upload_dir['basedir'] ) . $dir_name;

        // Check if the directory exists.
        if ( $wp_filesystem->is_dir( $target_dir ) ) {
            // Attempt to delete the directory and its contents.
            $deleted = $wp_filesystem->delete( $target_dir, true );

            if ( $deleted ) {
                return esc_html__('Directory deleted successfully.', 'animation-addons-for-elementor-pro');
            } else {
                return esc_html__('Failed to delete the directory.', 'animation-addons-for-elementor-pro');
            }
        } else {         
			return esc_html__('Directory does not exist.', 'animation-addons-for-elementor-pro');
        }
    }

	public function update_custom_icon_title()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor-pro'));
		}

		if (! isset($_POST['custom_font_global'])) {
			return;
		}

		if (! isset($_POST['id'])) {
			return;
		}
		$sanitize_id = sanitize_text_field(wp_unslash($_POST['id']));
		$title = sanitize_text_field(wp_unslash($_POST['title']));

		$updated_post = array(
			'ID'         => $sanitize_id,
			'post_title' => $title,
		);

		// Update the post in the database
		wp_update_post($updated_post);
		wp_send_json(esc_html__('Updated', 'animation-addons-for-elementor-pro'));
	}

	public function frontend_scripts(){
		$args = [
			'numberposts' => 15, // Limit number of posts
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'meta_key'   => 'wcf_addon_custom_icons',
		];

		$posts = get_posts($args);

		if(is_array($posts)) {
			foreach ($posts as $post) {		
				// check icomoon
				if(get_post_meta( $post->ID ,'wcf_addon_custom_icontype', true ) == 'icomoon'){
					$slug =	$this->createUniqueSlug($post->post_title);		
					$metainfo = get_post_meta( $post->ID ,'wcf_addon_custom_icons', true );		
					$aae_gl_load = get_post_meta( $post->ID ,'aae_gl_load', true );	
					if(isset($metainfo['elementor_path']) && isset($metainfo['elementor_style']) && $aae_gl_load == 'yes'){						
						$style_file = wp_upload_dir()['basedir'] .'/' .$metainfo['elementor_style'];
						$style      = wp_upload_dir()['baseurl'] .'/' .$metainfo['elementor_style'];	
						
						if(file_exists($style_file)){
							wp_enqueue_style( $slug , $style, array(), WCF_ADDONS_VERSION , 'all' );
						}				
					}
								
				}	
				
			}	
		}	
	}
	public function admin_scripts()
	{
		$current_screen = get_current_screen();
		if (isset($current_screen->id) && $current_screen->id == 'edit-wcf-custom-icons') {
			wp_enqueue_style('wcf-addon-pro-custom-icons', WCF_ADDONS_URL . 'assets/css/list.css');
			wp_enqueue_script('wcf-addon-pro-custom-icons', WCF_ADDONS_URL . 'assets/js/list-actions.js', array('jquery'), null, true);
		}
		if (isset($current_screen->id) && $current_screen->id == 'wcf-custom-icons') {
			wp_enqueue_media();
			wp_enqueue_style('wcf-addon-pro-custom-icons', WCF_ADDONS_URL . 'assets/build/modules/custom-icon/main.css');
			wp_enqueue_script('wcf-addon-pro-custom-icons', WCF_ADDONS_URL . 'assets/build/modules/custom-icon/main.js', array(
				'react',
				'react-dom',
				'wp-element',
				'wp-i18n'
			), WCF_ADDONS_VERSION, true);			
		}

		if (isset($current_screen->id) && ($current_screen->id == 'edit-wcf-custom-icons' ||$current_screen->id == 'wcf-custom-icons')) {
			$localize_data = [
				'ajaxurl'     => admin_url('admin-ajax.php'),
				'nonce'       => wp_create_nonce('wcf_admin_nonce'),
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
			esc_html__('Settings', 'animation-addons-for-elementor-pro'),
			[$this, 'metabox_callback'],
			$this->post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'wcf_proaddon_custom_icons_metabox_settings',
			esc_html__('Settings', 'animation-addons-for-elementor-pro'),
			[$this, 'metabox_side_settings_callback'],
			$this->post_type,
			'side',
			'high'
		);
	}

	public function metabox_callback()
	{
		echo '<div id="wcf--custom-icons-meta-box">Loading</div>';
	}

	public function metabox_side_settings_callback()
	{
		echo '<div id="wcf--custom-icons-meta-box-side-setting">Loading</div>';
	}

	public function register_sub_menu_post()
	{

		add_submenu_page('wcf_addons_page', esc_html__('Custom Icons', 'animation-addons-for-elementor-pro'), esc_html__('Custom Icons', 'animation-addons-for-elementor-pro'), 'manage_options', "edit.php?post_type=$this->post_type", null);
	}

	function custom_post_type()
	{
		$labels = array(
			'name'                  => _x('Custom Icons', 'Post type general name', 'animation-addons-for-elementor-pro'),
			'singular_name'         => _x('Custom Icon', 'Post type singular name', 'animation-addons-for-elementor-pro'),
			'menu_name'             => _x('Custom Icons', 'Admin Menu text', 'animation-addons-for-elementor-pro'),
			'name_admin_bar'        => _x('Custom Icon', 'Add New on Toolbar', 'animation-addons-for-elementor-pro'),
			'add_new'               => __('Add New', 'animation-addons-for-elementor-pro'),
			'add_new_item'          => __('Add New Icon', 'animation-addons-for-elementor-pro'),
			'new_item'              => __('New Icon', 'animation-addons-for-elementor-pro'),
			'edit_item'             => __('Edit Icon', 'animation-addons-for-elementor-pro'),
			'view_item'             => __('View Icon', 'animation-addons-for-elementor-pro'),
			'all_items'             => __('All Icons', 'animation-addons-for-elementor-pro'),
			'search_items'          => __('Search Icon', 'animation-addons-for-elementor-pro'),
			'parent_item_colon'     => __('Parent Icons:', 'animation-addons-for-elementor-pro'),
			'not_found'             => __('No Icon found.', 'animation-addons-for-elementor-pro'),
			'not_found_in_trash'    => __('No Icon found in Trash.', 'animation-addons-for-elementor-pro'),
			'featured_image'        => _x('Icon Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor-pro'),
			'set_featured_image'    => _x('Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor-pro'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor-pro'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor-pro'),
			'archives'              => _x('Icon archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'animation-addons-for-elementor-pro'),
			'insert_into_item'      => _x('Insert into Icon', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'animation-addons-for-elementor-pro'),
			'uploaded_to_this_item' => _x('Uploaded to this Icon', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'animation-addons-for-elementor-pro'),
			'filter_items_list'     => _x('Filter Icons list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'animation-addons-for-elementor-pro'),
			'items_list_navigation' => _x('Icons list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'animation-addons-for-elementor-pro'),
			'items_list'            => _x('Icons list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'animation-addons-for-elementor-pro'),
		);
		register_post_type(
			$this->post_type,
			array(
				'labels'      => $labels,
				'public'              => true,
				'menu_icon'           => 'dashicons-text-page',
				'supports'            => ['title'],
				'exclude_from_search' => true,
				'has_archive'         => false,
				'publicly_queryable'  => false,
				'hierarchical'        => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'show_in_admin_bar'   => false,
			)
		);
	}
}

CustomIcons_Lite::instance();
