<?php

namespace WCF_ADDONS\Admin\Base;

use WCF_ADDONS\Admin\WCF_Plugin_Installer;
use WP_Error;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class AAEAddon_Importer {

	public $file_path = 'aaeaddon_tpl_file.xml';
	public $full_path = null;
	public $wishlist_key = 'aaeaddon_user_wishlists';
	/**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;
	private $plugin_installer = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	public function __construct() {
		add_action( 'wp_ajax_aaeaddon_template_installer', [ $this, 'template_installer' ] );
		add_action( 'wp_ajax_aaeaddon_heartbeat_data', [ $this, 'heartbeat_data' ] );  
		add_action( 'wp_ajax_aaeaddon_wishlist_option', [ $this, 'wishlist' ] ); 
	
		$this->plugin_installer = new WCF_Plugin_Installer(true);     
		add_filter('wcf_addons_dashboard_config', [ $this, 'include_user_wishlist']);
	}

	public function include_user_wishlist($config) {
		$user_id = get_current_user_id();
    
    	// Fetch existing wishlist data
    	$config['wishlist'] = get_user_meta($user_id, $this->wishlist_key, true);
		return $config;
	}

	public function heartbeat_data(){
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
        $return_data = apply_filters('aaeaddon_heartbeat_data', ['msg' => get_option('aaeaddon_template_import_state')]);        
		wp_send_json($return_data);		
	}

	public function wishlist() {
    	check_ajax_referer('wcf_admin_nonce', 'nonce');

    	if (!current_user_can('install_plugins')) {
        	wp_send_json_error(__('You are not allowed to perform this action.', 'animation-addons-for-elementor'));
    	}

    	if (!isset($_POST['wishlist'])) {
        	wp_send_json_error(__('Provide wishlist data.', 'animation-addons-for-elementor'));
    	}

		$wishlist = sanitize_text_field(wp_unslash($_POST['wishlist'])); // Sanitize input
		$user_id = get_current_user_id();
		
		// Fetch existing wishlist data
		$wishlist_db = get_user_meta($user_id, $this->wishlist_key, true);
		
		// Ensure it's an array
		$wishlist_db = is_array($wishlist_db) ? $wishlist_db : [];

		if (in_array($wishlist, $wishlist_db, true)) {
			// Remove if exists
			$wishlist_db = array_values(array_filter($wishlist_db, fn($v) => $v !== $wishlist));
		} else {
			// Add new item
			$wishlist_db[] = $wishlist;
		}

		// Update user meta with modified wishlist
		update_user_meta($user_id, $this->wishlist_key, $wishlist_db);

		wp_send_json_success($wishlist_db);
	}


	public function template_installer(){
  
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}
		$progress = '25';	
		$msg = '';			
		$template_data = [];
		$theme_slug = $user_plugins = null;
       
		if (isset($_POST['theme_slug'])) {
			$theme_slug = sanitize_text_field(wp_unslash($_POST['theme_slug'])); // Remove slashes if added by WP		
		}
		if (isset($_POST['user_plugins'])) {
			$user_plugins = sanitize_text_field(wp_unslash($_POST['user_plugins'])); // Remove slashes if added by WP	
			$user_plugins = explode(',',$user_plugins);	
		}
		
		if (isset($_POST['template_data'])) {
			$json_data = sanitize_text_field( wp_unslash($_POST['template_data']) ); // Remove slashes if added by WP		
			$template_data = json_decode($json_data, true);		
		
			if (json_last_error() === JSON_ERROR_NONE) {			
				array_walk_recursive($template_data, function (&$value) {
					if (is_string($value)) {
						$value = sanitize_text_field($value);
					}
				});			
			}

			if(isset($template_data['next_step']) && $template_data['next_step'] == 'plugins-importer' ){
				// Install required plugin
			    // Include the necessary plugin.php file
				$progress                   = '20';	
				require_once ABSPATH . 'wp-admin/includes/plugin.php';

				if(is_array($user_plugins) && $user_plugins){
					if(isset($template_data['dependencies']['plugins']) && is_array($template_data['dependencies']['plugins'])){	
							if ( current_user_can( 'install_plugins' ) ) {				
								foreach($template_data['dependencies']['plugins'] as $item){
									if ( file_exists( WP_PLUGIN_DIR . '/' . $item['Base_Slug'] ) ) {
										$result = activate_plugin( $item['Base_Slug'] , '', false, false );	
									}else{
										
										if (in_array($item['slug'], $user_plugins)) {				
											if(isset($item['host']) && $item['slug']){
												update_option('aaeaddon_template_import_state', sprintf( 'Installing %s' , $item['name'] ));
												if($item['host'] == 'self_host'){	
													$result = $this->plugin_installer->install_plugin($item['self_host_url'], $item['host'], true);
													
												}else{
													$result = $this->plugin_installer->install_plugin($item['slug'], $item['host'], true);
												}							
											}
										}
									}							
								}
							}					
							update_option('aaeaddon_template_import_state', esc_html__( 'Plugin Installation Done' , 'animation-addons-for-elementor' ));
						}
				}
				$template_data['next_step'] = 'install-wp-options';					
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-template-status'){	
				$tpl = $this->validate_download_file($template_data);				
				if($tpl){
					update_option('aaeaddon_template_import_state', esc_html__( 'Content file Downloading' , 'animation-addons-for-elementor' ) );
					$template_data['next_step'] = 'download-xml-file';
					$template_data['file']      = json_decode($tpl);
				}else{
					update_option('aaeaddon_template_import_state', esc_html__( 'Invalid file', 'animation-addons-for-elementor'));
					$template_data['next_step'] = 'fail';
				}
				$progress                    = '35';
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'download-xml-file'){					
				if(isset($template_data['file']['content_url'])){						
					update_option('aaeaddon_template_import_state', esc_html__('Content installing', 'animation-addons-for-elementor'));
					$template_data['next_step']  = 'install-template';
					$template_data['local_path'] = $this->full_path;					
				}else{
					$template_data['next_step'] = 'fail';
					update_option('aaeaddon_template_import_state', esc_html__('Missing Content file, contact author', 'animation-addons-for-elementor'));
				}
				$progress                    = '40';			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-template'){
				$template_data['next_step'] = 'check-theme';
				$progress                   = '50';
				$msg                        = esc_html__('Varifying Content Import', 'animation-addons-for-elementor');
				update_option('aaeaddon_template_import_state', 'Checking Theme');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-theme'){
				if($theme_slug){
					$template_data['next_step'] = 'install-theme';
					$progress                   = '75';
					update_option('aaeaddon_template_import_state', 'Installing Theme');
				}else{
					$template_data['next_step'] = 'install-elementor-settings';			
				}			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-theme'){
				$template_data['next_step'] = 'install-elementor-settings';
				$progress                   = '80';
				if ( current_user_can( 'install_themes' ) ) {
					$msg = $this->install_theme($theme_slug);
				}
				update_option('aaeaddon_template_import_state', $msg);
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-elementor-settings'){
				update_option( 'wcf_addons_setup_wizard', 'complete' );
				$template_data['next_step'] = 'done';
				$progress                   = '100';		
				if ( isset( $template_data['elementor_settings']['content_url'] ) && $template_data['elementor_settings']['type'] === 'json' ) {
					$response = wp_remote_get( $template_data['elementor_settings']['content_url']);
					if ( is_array( $response ) && ! is_wp_error( $response ) ) {
						$json_data = wp_remote_retrieve_body( $response );
						$msg = $this->installElementorKit($json_data);
						update_option('aaeaddon_template_import_state', $msg);
					}
				}
				$this->update_blog_and_homepage_options($template_data);	
				do_action('aaeaddon/starter-template/import/step/metasettings');				
				
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-wp-options'){

				$template_data['next_step'] = 'check-template-status';
				$progress                   = '30';
				$msg                        =  esc_html__('Checking Template Content', 'animation-addons-for-elementor');
				if(isset($template_data['wp_options']) && is_array($template_data['wp_options'])){
					$this->install_options($template_data['wp_options']);
				}	
									
				update_option('aaeaddon_template_import_state', 'done');			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'fail'){
				$msg = esc_html__('Template Demo Import fail', 'animation-addons-for-elementor');
			}else{
				$template_data['next_step'] = 'plugins-importer';	
				$progress                   = '10';	
			
				update_option('aaeaddon_template_import_state', esc_html__('Checking Setup requirement', 'animation-addons-for-elementor'));
			}			
			
		}
	    
		wp_send_json( ['template' => wp_unslash( $template_data ),'msg' => $msg, 'progress' => $progress] );
	}

	public function update_blog_and_homepage_options($template_data){
	
		 if($template_data['home_page'] && $template_data['home_page'] !=''){
			// Get the front page.
			$front_page = get_posts(
				[
				'post_type'              => 'page',
				'title'                  => $template_data['home_page'],
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				]
			);
			
			if ( ! empty( $front_page ) ) {		
				update_option( 'page_on_front', $front_page[0]->ID );
			}
		 }

		 if($template_data['blog_page'] && $template_data['blog_page'] !=''){
			// Get the blog page.
			$blog_page = get_posts(
				[
				'post_type'              => 'page',
				'title'                  => $template_data['blog_page'],
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				]
			);
			
			if ( ! empty( $blog_page ) ) {
				update_option( 'page_for_posts', $blog_page[0]->ID );
			}
			
			if ( ! empty( $blog_page ) || ! empty( $front_page ) ) {
				update_option( 'show_on_front', 'page' );
			}
			
		}
	}

	public function install_options( $settings ) {
		global $wpdb;
	
		foreach ( $settings as $item ) {
			$response = wp_remote_get( $item['xml_file'] );
	
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$xml_data = wp_remote_retrieve_body( $response );
				$xml      = simplexml_load_string( $xml_data );
				if ( ! $xml ) {
					continue; // Skip if XML parsing fails.
				}
	
				if ( isset( $xml->option ) ) {
					foreach ( $xml->option as $opt ) {
						$option_name     = sanitize_text_field( (string) $opt->name );
						$serialized_data = sanitize_text_field( (string) $opt->value );
	
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
						$wpdb->update(
							$wpdb->options,
							array( 'option_value' => $serialized_data ),
							array( 'option_name'  => $option_name )
						);
					}
				} else {
					$option_name     = sanitize_text_field( (string) $xml->name );
					$serialized_data = sanitize_text_field( (string) $xml->value );
	
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->update(
						$wpdb->options,
						array( 'option_value' => $serialized_data ),
						array( 'option_name'  => $option_name )
					);
				}
			}
		}
	}
	
	

	public function installElementorKit($elementor){
		$activeKitId = get_option( 'elementor_active_kit' );
		$kit_data    = json_decode( $elementor, true );
		if ( $activeKitId ) {
			$postMeta = get_post_meta( $activeKitId, '_elementor_page_settings', true );
			// Ensure $postMeta is an array
			$newPostMeta = is_array( $postMeta ) ? $postMeta : [];
			// Add or override custom colors
			if ( $kit_data && isset( $kit_data['settings'] ) ) {
				$newPostMeta = $kit_data['settings'];
				update_post_meta( $activeKitId, '_elementor_page_settings', $newPostMeta );
			}
			return esc_html__('Kit Settings Update', 'animation-addons-for-elementor');
		}
	}	

	function validate_download_file($template) {
	
		if (empty($template)) {
			update_option('aaeaddon_template_import_state', esc_html__('Template Required', 'animation-addons-for-elementor'));
			return false;
		}
		
	    $remote_url = WCF_TEMPLATE_STARTER_BASE_URL . 'wp-json/starter-templates/download';	
		$args = [
			'timeout'   => 180,
			'body' => [
				'template' => $template
			],
			'sslverify' => false // Disable SSL verification
		];	
	
		// Fetch the remote file with POST request
		$response = wp_safe_remote_get($remote_url, apply_filters('aaeaddon/starter_templates/download_args',$args));
	
		if (is_wp_error($response)) {
			update_option('aaeaddon_template_import_state', esc_html__('Failed to validate file from remote URL.', 'animation-addons-for-elementor'));
			return false;
		}
	
		$response_code = wp_remote_retrieve_response_code($response);		
		if ($response_code !== 200) {
			update_option('aaeaddon_template_import_state', esc_html__('Invalid file arguments. Please check the URL.', 'animation-addons-for-elementor'));
			return false;
		}
	
		$body = wp_remote_retrieve_body($response);
	
		if (empty($body)) {
			update_option('aaeaddon_template_import_state', esc_html__('The downloadable file is empty.', 'animation-addons-for-elementor'));
			return false;
		}

		return $body;
	}
	
	
	function download_remote_wp_xml_file($remote_url) {
		
		if (empty($remote_url)) {
			
			return esc_html__('Remote URL is required.', 'animation-addons-for-elementor');
		}
	
		// Fetch the remote file
		$response = wp_safe_remote_get($remote_url);
	
		if (is_wp_error($response)) {
			return esc_html__('Failed to fetch XML from remote URL.', 'animation-addons-for-elementor');
		}
	
		$body = wp_remote_retrieve_body($response);
	
		if (empty($body)) {
			update_option('aaeaddon_template_import_state', esc_html__('The remote XML file is empty.', 'animation-addons-for-elementor'));
			return esc_html__('The remote XML file is empty.', 'animation-addons-for-elementor');
		}
	
		// Initialize the WordPress filesystem
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
	
		global $wp_filesystem;
		WP_Filesystem();
	
		// Define file path in the uploads directory
		$upload_dir = wp_upload_dir();
		$this->full_path = trailingslashit($upload_dir['path']) . $this->file_path;
		
		// Write the file using the filesystem API
		if (!$wp_filesystem->put_contents($this->full_path, $body, FS_CHMOD_FILE)) {
			update_option('aaeaddon_template_import_state', esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor'));
			return esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor');
		}
		update_option('aaeaddon_template_import_state', esc_html__('File downloaded and saved successfully', 'animation-addons-for-elementor'));
		return esc_html__('File downloaded and saved successfully at ', 'animation-addons-for-elementor') . $this->full_path;
	}
	
	function install_theme($slug) {
		
		if (empty($slug)) {
			update_option('aaeaddon_template_import_state', esc_html__('No theme specified.', 'animation-addons-for-elementor'));	
			return esc_html__('No theme specified.', 'animation-addons-for-elementor');
		}
	
		$theme_slug = sanitize_key($slug);

		$theme_data = wp_get_theme($theme_slug);
		/* translators: 1: WordPress active theme name */
		$msg = sprintf(	esc_html__('Theme "%s" installed and activated successfully.', 'animation-addons-for-elementor'), 
					$theme_data->get('Name')
		);		
		if ($theme_data->exists()) {
			switch_theme($theme_slug);
			
			update_option(
				'aaeaddon_template_import_state', 
				$msg 
			);
			
			return $msg;
		}
	
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';
	
		$api = themes_api('theme_information', array(
			'slug' => $theme_slug,
			'fields' => array('sections' => false),
		));
	
		if (is_wp_error($api)) {	
			return $api->get_error_message();
		}
	
		$upgrader 	= new \Theme_Upgrader(new \WP_Ajax_Upgrader_Skin());
		$result 	= $upgrader->install($api->download_link);
	
		if (is_wp_error($result)) {	
			update_option('aaeaddon_template_import_state', $result->get_error_message());
			return $result->get_error_message();
		}

		// Translators: %s is the theme name.
		$msg = sprintf(esc_html__('Theme "%s" installed and activated successfully.','animation-addons-for-elementor'),	esc_html($theme_data->get('Name')));
		
		if ($theme_data->exists()) {
			switch_theme($theme_slug);	
			update_option('aaeaddon_template_import_state', $msg);			
		}
		return $msg;
	}
	
	
}

AAEAddon_Importer::instance();