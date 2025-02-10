<?php

namespace WCF_ADDONS\Admin\Base;

use WP_Error;

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
	}

	public function heartbeat_data(){
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
        $return_data = apply_filters('aaeaddon_heartbeat_data', ['msg' => get_option('aaeaddon_template_import_state')]);
        // $return_data = apply_filters('aaeaddon_heartbeat_data', ['msg' => false]);
		wp_send_json($return_data);		
	}

	public function template_installer(){

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}
		$progress = '25';	
		$msg = '';			
		$template_data = [];
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

			if(isset($template_data['next_step']) && $template_data['next_step'] == 'core-importer'){
				$template_data['next_step'] = 'check-template-status';
				$msg = $this->wordpress_importer_installed_and_active();
				update_option('aaeaddon_template_import_state', 'checking template ststus');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-template-status'){			
				if(isset($template_data['file']['content_url'])){

				 $msg =	$this->download_remote_wp_xml_file($template_data['file']['content_url']);

				 if('importer_install' == $msg){
					$template_data['next_step'] = 'core-importer';
					$progress                   = '25';
				 }else{
					update_option('aaeaddon_template_import_state', 'Installing Template');
					$template_data['next_step'] = 'install-template';
					$progress                   = '50';
				 }
				
				}else{
					$template_data['next_step'] = 'fail';
					update_option('aaeaddon_template_import_state', esc_html__('Missing xml file, contact plugin author', 'animation-addons-for-elementor'));
				}			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-template'){
				$template_data['next_step'] = 'check-theme';
				$progress                   = '50';
				//$msg                        = $this->install_template();
				update_option('aaeaddon_template_import_state', 'Checking Theme');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-theme'){
				$template_data['next_step'] = 'install-theme';
				$progress                   = '75';
				update_option('aaeaddon_template_import_state', 'Installing Theme');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-theme'){
				$template_data['next_step'] = 'install-elementor-settings';
				$progress                   = '75';
				update_option('aaeaddon_template_import_state', 'Installing Elementor Settings');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-elementor-settings'){
				$template_data['next_step'] = 'done';
				$progress                   = '100';
				$msg                        = 'Done';
				delete_option('aaeaddon_template_import_state');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'fail'){
				$msg = 'Template Demo Import fail';	
			}else{
				//$template_data['next_step'] = 'core-importer';
				$template_data['next_step'] = 'done';
				$msg                        = $this->install_template(); // remove after test
			
				update_option('aaeaddon_template_import_state', 'Checking Setup requirement');
			}			
			
		}
	    
		wp_send_json( ['template' => wp_unslash( $template_data ),'msg' => $msg, 'progress' => $progress] );
	}

	public function install_template(){
		// Define file path in the uploads directory
		$upload_dir = wp_upload_dir();
		$file_path = trailingslashit($upload_dir['path']) . $this->file_path;

		if (!file_exists($file_path)) {
			return __('file_missing', 'XML file not found.','animation-addons-for-elementor');
		}

		require_once ABSPATH . 'wp-admin/includes/import.php';
        if (!class_exists('WXRImporter')) {           
			require_once( 'base/WXRImporter.php' );
        }

		ob_start(); // Suppress output		
		
        $importer = new WXRImporter();
        $importer->import($file_path);
		error_log($file_path);
		ob_end_clean(); // Clear the buffer
		update_option('aaeaddon_template_import_state', 'Import completed successfully.');
		return 'Import completed successfully.';
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
	

	function wordpress_importer_installed_and_active() {
		ob_start();    
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugin_slug = 'wordpress-importer';
		$plugin_file = "$plugin_slug/wordpress-importer.php";
	
		// Check if the plugin is installed
		if (!is_dir(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/misc.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	
			$api = plugins_api('plugin_information', ['slug' => $plugin_slug]);
	
			if (is_wp_error($api)) {
				update_option('aaeaddon_template_import_state', 'Invalid plugin slug or API error.');
				return json_encode(['status' => 'error', 'message' => 'Failed to retrieve plugin information.']);
			}
	
			// Install the plugin
			$upgrader = new \Plugin_Upgrader();
			$result = $upgrader->install($api->download_link);
	
			if (is_wp_error($result)) {
				update_option('aaeaddon_template_import_state', 'Importer Plugin installation failed.');
				return json_encode(['status' => 'error', 'message' => 'Plugin installation failed.']);
			}
		}
	
		// Activate the plugin if not active
		if (!is_plugin_active($plugin_file)) {
			$activation_result = activate_plugin(WP_PLUGIN_DIR . '/' . $plugin_file);
	
			if (is_wp_error($activation_result)) {
				return json_encode(['status' => 'error', 'message' => 'Plugin activation failed.']);
			}
		}
	
		update_option('aaeaddon_template_import_state', 'Importer installed and activated successfully.');
		return ob_get_clean();
	}
	
}
AAEAddon_Importer::instance();