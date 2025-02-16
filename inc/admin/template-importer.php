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
	/**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;
	private $plugin_installer = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [Woolentor_Admin_Init]
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
	}

	public function heartbeat_data(){
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
        $return_data = apply_filters('aaeaddon_heartbeat_data', ['msg' => get_option('aaeaddon_template_import_state')]);        
		wp_send_json($return_data);		
	}

	public function wishlist(){
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

    if (!isset($_POST['wishlist'])) {
			wp_send_json_error( __( 'Provide wishlist data', 'animation-addons-for-elementor' ) );
		} 
		
		$wishlist = sanitize_text_field(wp_unslash($_POST['wishlist'])); // Remove slashes if added by WP		
		$updated = update_user_meta( get_current_user_id(), 'aaeaddon_user_wishlist', $wishlist );
		if(get_user_meta( get_current_user_id(), 'aaeaddon_user_wishlist', true ) === false) {
		$wishlist_db = [$wishlist];
		} else {
			$wishlist_db = get_user_meta( get_current_user_id(), 'aaeaddon_user_wishlist', true );
			if(is_array($wishlist_db) || 1) {
				$wishlist_db[] = $wishlist;
				$wishlist_db = array_unique($wishlist_db);
			}
		}
		
		wp_send_json($wishlist_db);		
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
			$json_data = wp_unslash($_POST['template_data']); // Remove slashes if added by WP		
			$template_data = json_decode($json_data, true);		

			if (json_last_error() === JSON_ERROR_NONE) {			
				array_walk_recursive($template_data, function (&$value) {
					if (is_string($value)) {
						$value = sanitize_text_field($value);
					}
				});			
			}

			if(isset($template_data['next_step']) && $template_data['next_step'] == 'plugins-importer' && is_array($user_plugins) && $user_plugins){
				// Install required plugin
			    
				if(isset($template_data['dependencies']['plugins']) && is_array($template_data['dependencies']['plugins'])){					
					foreach($template_data['dependencies']['plugins'] as $item){
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
					$template_data['next_step'] = 'check-template-status';	
					update_option('aaeaddon_template_import_state', esc_html__( 'Plugin Installation Done' , 'animation-addons-for-elementor' ));
				}else{
					$template_data['next_step'] = 'check-template-status';	
				}				
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-template-status'){	
				$tpl = $this->validate_download_file($template_data);				
				if($tpl){
					update_option('aaeaddon_template_import_state', esc_html__( 'Validating demo file' , 'animation-addons-for-elementor' ) );
					$template_data['next_step'] = 'download-xml-file';
					$template_data['file'] = json_decode($tpl);
				}else{
					update_option('aaeaddon_template_import_state', esc_html__( 'Invalid file', 'animation-addons-for-elementor'));
					$template_data['next_step'] = 'fail';
				}
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'download-xml-file'){					
				if(isset($template_data['file']['content_url'])){
					$msg =	$this->download_remote_wp_xml_file($template_data['file']['content_url']);				
								
					update_option('aaeaddon_template_import_state', esc_html__('Demo file Downloaded', 'animation-addons-for-elementor'));
					$template_data['next_step'] = 'install-template';
					$progress                   = '50';				 				
				}else{
					$template_data['next_step'] = 'fail';
					update_option('aaeaddon_template_import_state', esc_html__('Missing xml file, contact plugin author', 'animation-addons-for-elementor'));
				}			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-template'){
				$template_data['next_step'] = 'check-theme';
				$progress                   = '50';
				$msg                        = $this->install_template();
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
				$progress                   = '75';
				$msg = $this->install_theme($theme_slug);
				update_option('aaeaddon_template_import_state', $msg);
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-elementor-settings'){
				$template_data['next_step'] = 'install-wp-options';
				$progress                   = '90';		
				if ( isset( $template_data['elementor_settings']['content_url'] ) && $template_data['elementor_settings']['type'] === 'json' ) {
					$response = wp_remote_get( $template_data['elementor_settings']['content_url']);
					if ( is_array( $response ) && ! is_wp_error( $response ) ) {
						$json_data = wp_remote_retrieve_body( $response );
						$msg = $this->installElementorKit($json_data);
						update_option('aaeaddon_template_import_state', $msg);
					}
				}	
				
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-wp-options'){

				$template_data['next_step'] = 'done';
				$progress                   = '100';
				$msg                        = 'done';	
				if(isset($template_data['wp_options']) && is_array($template_data['wp_options'])){
					$this->install_options($template_data['wp_options']);
				}							
				update_option('aaeaddon_template_import_state', 'done');			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'fail'){
				$msg = esc_html__('Template Demo Import fail', 'animation-addons-for-elementor');
			}else{
				$template_data['next_step'] = 'plugins-importer';		
				update_option('aaeaddon_template_import_state', esc_html__('Checking Setup requirement', 'animation-addons-for-elementor'));
			}			
			
		}
	    
		wp_send_json( ['template' => wp_unslash( $template_data ),'msg' => $msg, 'progress' => $progress] );
	}

	public function install_options($settings){

		foreach($settings as $item){
			$response = wp_remote_get( $item['xml_file'] );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$headers  = $response['headers']; // array of http header lines
				$xml_data = wp_remote_retrieve_body( $response );
				$xml      = simplexml_load_string( $xml_data );
				// Extract the serialized value
				$serialized_data = (string) $xml->value;
				if ( is_string( $serialized_data ) ) {
					$arr = unserialize( $serialized_data );
					if ( is_array( $arr ) ) {
						update_option( $item['option_name'], $arr );
					}
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

	public function install_template(){
		// Define file path in the uploads directory
		$upload_dir = wp_upload_dir();
		$file_path = trailingslashit($upload_dir['path']) . $this->file_path;
	
		if (!file_exists($file_path)) {
			return __('file_missing', 'XML file not found.','animation-addons-for-elementor');
		}

		if (!class_exists('WP_Importer')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}
		
		require_once ABSPATH . 'wp-admin/includes/import.php';
		require_once( 'base/WPImporterLogger.php' );
		require_once( 'base/WPImporterLoggerCLI.php' );
		require_once( 'base/WXRImportInfo.php' );
		require_once( 'base/WXRImporter.php' );
		require_once( 'base/Importer.php' );
		ob_start(); 

		$options = [
			'chunk_size'         => 100,
			'validate_schema'    => true,
			'skip_duplicates'    => false,
			'overwrite_existing' => true,    // Do not overwrite existing records
			'skip_empty_nodes'   => true,    // Skip nodes with empty data
		];

		$logger   = new WPImporterLogger();          		
		$importer = new Importer($options, $logger);
		$result   = $importer->import($file_path);
		ob_end_clean(); // Clear the buffer

		update_option('aaeaddon_template_import_state', esc_html__('Import completed successfully.', 'animation-addons-for-elementor'));
		return esc_html__('Import completed successfully.', 'animation-addons-for-elementor');
	}

	function validate_download_file($template) {
	
		if (empty($template)) {
			update_option('aaeaddon_template_import_state', esc_html__('Template Required', 'animation-addons-for-elementor'));
			return false;
		}
		
	    $remote_url = WCF_TEMPLATE_STARTER_BASE_URL . 'wp-json/starter-templates/download';	
		$args = [
			'timeout'   => 60,
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
		$file_path = trailingslashit($upload_dir['path']) . $this->file_path;
		
		// Write the file using the filesystem API
		if (!$wp_filesystem->put_contents($file_path, $body, FS_CHMOD_FILE)) {
			update_option('aaeaddon_template_import_state', esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor'));
			return esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor');
		}
		update_option('aaeaddon_template_import_state', esc_html__('File downloaded and saved successfully', 'animation-addons-for-elementor'));
		return esc_html__('File downloaded and saved successfully at ', 'animation-addons-for-elementor') . $file_path;
	}
	function install_theme($slug) {
		
		if (empty($slug)) {
			update_option('aaeaddon_template_import_state', esc_html__('No theme specified.', 'animation-addons-for-elementor'));	
			return esc_html__('No theme specified.', 'animation-addons-for-elementor');
		}
	
		$theme_slug = sanitize_key($slug);

		$theme_data = wp_get_theme($theme_slug);

		if ($theme_data->exists()) {
			switch_theme($theme_slug);
			update_option('aaeaddon_template_import_state', esc_html__("Theme '{$theme_data->get('Name')}' installed and activated successfully.",'animation-addons-for-elementor'));
			return esc_html__("Theme '{$theme_data->get('Name')}' installed and activated successfully.",'animation-addons-for-elementor');
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
	
		$upgrader = new \Theme_Upgrader(new \WP_Ajax_Upgrader_Skin());
		$result = $upgrader->install($api->download_link);
	
		if (is_wp_error($result)) {	
			update_option('aaeaddon_template_import_state', $result->get_error_message());	
			return $result->get_error_message();
		}

		if ($theme_data->exists()) {
			switch_theme($theme_slug);
			update_option('aaeaddon_template_import_state', esc_html__("Theme '{$theme_data->get('Name')}' installed and activated successfully.",'animation-addons-for-elementor'));
			return esc_html__("Theme '{$theme_data->get('Name')}' installed and activated successfully.",'animation-addons-for-elementor');
		}
	
		return esc_html__('Theme Installation Done', 'animation-addons-for-elementor');
	}
	
	
}
AAEAddon_Importer::instance();