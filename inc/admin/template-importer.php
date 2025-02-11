<?php

namespace WCF_ADDONS\Admin\Base;
use WCF_ADDONS\Admin\WCF_Plugin_Installer;
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
		$this->plugin_installer = new WCF_Plugin_Installer(true);     
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

			if(isset($template_data['next_step']) && $template_data['next_step'] == 'plugins-importer'){
				// Install required plugin
				if(isset($template_data['dependencies']['plugins'])){
					
					foreach($template_data['dependencies']['plugins'] as $item){

						if(isset($item['host']) && $item['slug']){
							if($item['host'] == 'self_host'){
							
								$result = $this->plugin_installer->install_plugin($item['self_host_url'], $item['host'], true);
							}else{
								$result = $this->plugin_installer->install_plugin($item['slug'], $item['host'], true);
							}							
						}

					}
					
					$template_data['next_step'] = 'check-template-status';	
					update_option('aaeaddon_template_import_state', $result);

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
								
					update_option('aaeaddon_template_import_state', 'Demo file Downloaded');
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
				$template_data['next_step'] = 'plugins-importer';		
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
		require_once( 'base/WPImporterLogger.php' );
		require_once( 'base/WPImporterLoggerCLI.php' );
		require_once( 'base/WXRImportInfo.php' );
		require_once( 'base/WXRImporter.php' );
		require_once( 'base/Importer.php' );
		ob_start(); 

		$options = [
			'chunk_size' => 100,
			'validate_schema' => true,		
			'skip_duplicates' => false,
			'overwrite_existing' => true, // Do not overwrite existing records
			'skip_empty_nodes' => true, // Skip nodes with empty data
		];

		$logger   = new WPImporterLogger();          		
		$importer = new Importer($options, $logger);
		$result   = $importer->import($file_path);
		ob_end_clean(); // Clear the buffer
		update_option('aaeaddon_template_import_state', 'Import completed successfully.');
		return 'Import completed successfully.';
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
	
	
}
AAEAddon_Importer::instance();