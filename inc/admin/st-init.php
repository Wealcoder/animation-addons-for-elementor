<?php

namespace WCF_ADDONS\Admin\Base;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

/**
 * One Click Import class
 */
class OneClickImport {

	public $file_path = 'aaeaddon_tpl_file.xml';	
	private static $instance;
	public $importer;	

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The path of the log file.
	 *
	 * @var string
	 */
	public $log_file_path;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	public $frontend_error_messages = array();

	/**
	 * Was the before content import already triggered?
	 *
	 * @var boolean
	 */
	private $before_import_executed = false;

	/**
	 * Make plugin page options available to other methods.
	 *
	 * @var array
	 */
	private $plugin_page_setup = array();

	/**
	 * Imported terms.
	 *
	 * @var array
	 */
	private $imported_terms = array();

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return OneClickImport the *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Class construct function, to initiate the plugin.
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {	
		add_action( 'wp_ajax_aaeaddon_upload_manual_import_file', [ $this, 'import_demo_data_ajax_callback' ] );
		add_action( 'admin_init', [$this, 'setup_st_importer']);
		add_action( 'set_object_terms', array( $this, 'add_imported_terms' ), 10, 6 );
		add_filter( 'wxr_importer.pre_process.post', [ $this, 'skip_failed_attachment_import' ] );
		add_action( 'wxr_importer.process_failed.post', [ $this, 'handle_failed_attachment_import' ], 10, 5 );
		add_action( 'wp_import_insert_post', [ $this, 'save_wp_navigation_import_mapping' ], 10, 4 );
		add_action( 'aaeaddon/after_import', [ $this, 'fix_imported_wp_navigation' ] );
	}

	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Empty unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	public function __wakeup() {}
	
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		ini_set( 'memory_limit', Helpers::apply_filters( 'aadaddon/st/import_memory_limit', '1024M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			// Define log file path.
			$this->log_file_path = Helpers::get_log_path();
			
			// Get selected file index or set it to 0.
			$this->selected_index = 0;
			$template_data = [];
			check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
			if(isset($_POST['template_data'])){

				$json_data     = sanitize_text_field( wp_unslash($_POST['template_data']) );  // Remove slashes if added by WP		
				$template_data = json_decode($json_data, true);
				
				if (json_last_error() === JSON_ERROR_NONE) {			
					array_walk_recursive($template_data, function (&$value) {
						if (is_string($value)) {
							$value = sanitize_text_field($value);
						}
					});			
				}

			}		
		
			if ( isset($template_data['file']['content_url']) ) { // Provide url
				// Download the import files (content).
			
				$file_path = $template_data['file']['content_url'];
			
				$this->selected_import_files = Helpers::download_import_files( [ 'import_file_url' =>$file_path ] );
			
				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'animation-addons-for-elementor' )
					);
				}

			}
			else {
				$response                   = [];
				$template_data['next_step'] = 'fail';
				$response['msg']            = esc_html__( 'No import files specified!', 'animation-addons-for-elementor' );
				$response['progress']       = 0;
				$response['template']       = wp_unslash( $template_data );
				wp_send_json( $response );
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_st_import_data_transient( $this->get_current_importer_data() );

		if ( ! $this->before_import_executed ) {
			$this->before_import_executed = true;

			/**
			 * 2). Execute the actions hooked to the 'aaeaddon/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			Helpers::do_action( 'aaeaddon/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		}
	
		Helpers::do_action( 'aaeaddon/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );

		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_st_import_data_transient( $this->get_current_importer_data() );

		// Request the after all import AJAX call.
		if ( false !== Helpers::has_action( 'aaeaddon/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			Helpers::do_action( 'aaeaddon/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * Send a JSON response with final report.
	 */
	private function final_response() {
		// Delete importer data transient for current import.
		delete_transient( 'aadaddon_st_importer_data' );
		delete_transient( 'aadaddon_st_mporter_data_failed_attachment_imports' );
		delete_transient( 'aadaddon_import_menu_mapping' );
		delete_transient( 'aaeaddon_import_posts_with_nav_block' );
		
		$response['msg'] = esc_html__( 'Congrats, your demo has been imported.', 'animation-addons-for-elementor' );
		$response['progress'] = 80;
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
		if (isset($_POST['template_data'])) {
			if(isset($template_data['local_path'])){
				unset($template_data['local_path']);
			}
			$json_data                  = sanitize_text_field( wp_unslash($_POST['template_data']));  // Remove slashes if added by WP		
			$template_data              = json_decode($json_data, true);
			$template_data['next_step'] = 'check-theme';
			$response['template']       = wp_unslash( $template_data );
			
		}
		
		wp_send_json( $response );
	}


	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
		if ( $data = get_transient( 'aadaddon_st_importer_data' ) ) {
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->import_files            = empty( $data['import_files'] ) ? array() : $data['import_files'];
			$this->before_import_executed  = empty( $data['before_import_executed'] ) ? false : $data['before_import_executed'];
			$this->imported_terms          = empty( $data['imported_terms'] ) ? [] : $data['imported_terms'];
			$this->importer->set_importer_data( $data );

			return true;
		}
		return false;
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_import_executed'  => $this->before_import_executed,
			'imported_terms'          => $this->imported_terms,
		);
	}


	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}


	/**
	 * Setter function to append additional value to the private frontend_error_messages value.
	 *
	 * @param string $additional_value The additional value that will be appended to the existing frontend_error_messages.
	 */
	public function append_to_frontend_error_messages( $text ) {
		$lines = array();

		if ( ! empty( $text ) ) {
			$text = str_replace( '<br>', PHP_EOL, $text );
			$lines = explode( PHP_EOL, $text );
		}

		foreach ( $lines as $line ) {
			if ( ! empty( $line ) && ! in_array( $line , $this->frontend_error_messages ) ) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}


	/**
	 * Display the frontend error messages.
	 *
	 * @return string Text with HTML markup.
	 */
	public function frontend_error_messages_display() {
		$output = '';

		if ( ! empty( $this->frontend_error_messages ) ) {
			foreach ( $this->frontend_error_messages as $line ) {
				$output .= esc_html( $line );
				$output .= '<br>';
			}
		}

		return $output;
	}


	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */
	public function setup_st_importer() {		

		// Get info of import data files and filter it.
		$this->import_files = array();

		// Importer options array.
		$importer_options = array(
			'fetch_attachments' => true,
		);

		// Logger options for the logger used in the importer.
		$logger_options = array(
			'logger_min_level' => 'warning',
		);

		// Configure logger instance and set it to the importer.
		$logger            = new Logger();
		$logger->min_level = $logger_options['logger_min_level'];

		// Create importer instance with proper parameters.
		$this->importer = new Importer( $importer_options, $logger );	

		
	}


	/**
	 * Add imported terms.
	 *
	 * Mainly it's needed for saving all imported terms and trigger terms count updates.
	 * WP core term defer counting is not working, since import split to chunks and we are losing `$_deffered` array
	 * items between ajax calls.
	 */
	public function add_imported_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ){

		if ( ! isset( $this->imported_terms[ $taxonomy ] ) ) {
			$this->imported_terms[ $taxonomy ] = array();
		}

		$this->imported_terms[ $taxonomy ] = array_unique( array_merge( $this->imported_terms[ $taxonomy ], $tt_ids ) );
	}

	/**
	 * Returns an empty array if current attachment to be imported is in the failed imports list.
	 *
	 * This will skip the current attachment import.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data Post data to be imported.
	 *
	 * @return array
	 */
	public function skip_failed_attachment_import( $data ) {
		// Check if failed import.
		if (
			! empty( $data ) &&
			! empty( $data['post_type'] ) &&
			$data['post_type'] === 'attachment' &&
			! empty( $data['attachment_url'] )
		) {
			// Get the previously failed imports.
			$failed_media_imports = Helpers::get_failed_attachment_imports();

			if ( ! empty( $failed_media_imports ) && in_array( $data['attachment_url'], $failed_media_imports, true ) ) {
				// If the current attachment URL is in the failed imports, then skip it.
				return [];
			}
		}

		return $data;
	}

	/**
	 * Save the failed attachment import.
	 *
	 * @since 3.2.0
	 *
	 * @param WP_Error $post_id Error object.
	 * @param array    $data Raw data imported for the post.
	 * @param array    $meta Raw meta data, already processed.
	 * @param array    $comments Raw comment data, already processed.
	 * @param array    $terms Raw term data, already processed.
	 */
	public function handle_failed_attachment_import( $post_id, $data, $meta, $comments, $terms ) {

		if ( empty( $data ) || empty( $data['post_type'] ) || $data['post_type'] !== 'attachment' ) {
			return;
		}

		Helpers::set_failed_attachment_import( $data['attachment_url'] );
	}

	/**
	 * Save the information needed to process the navigation block.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $post_id     The new post ID.
	 * @param int   $original_id The original post ID.
	 * @param array $postdata    The post data used to insert the post.
	 * @param array $data        Post data from the WXR file.
	 */
	public function save_wp_navigation_import_mapping( $post_id, $original_id, $postdata, $data ) {

		if ( empty( $postdata['post_content'] ) ) {
			return;
		}

		if ( $postdata['post_type'] !== 'wp_navigation' ) {

			/*
			 * Save the post ID that has navigation block in transient.
			 */
			if ( strpos( $postdata['post_content'], '<!-- wp:navigation' ) !== false ) {
				// Keep track of POST ID that has navigation block.
				$wcfio_post_nav_block = get_transient( 'aaeaddon_import_posts_with_nav_block' );

				if ( empty( $wcfio_post_nav_block ) ) {
					$wcfio_post_nav_block = [];
				}

				$wcfio_post_nav_block[] = $post_id;

				set_transient( 'aaeaddon_import_posts_with_nav_block', $wcfio_post_nav_block, HOUR_IN_SECONDS );
			}
		} else {

			/*
			 * Save the `wp_navigation` post type mapping of the original menu ID and the new menu ID
			 * in transient.
			 */
			$wcfio_menu_mapping = get_transient( 'aadaddon_import_menu_mapping' );

			if ( empty( $wcfio_menu_mapping ) ) {
				$wcfio_menu_mapping = [];
			}

			// Let's save the mapping of the original menu ID and the new menu ID.
			$wcfio_menu_mapping[] = [
				'original_menu_id' => $original_id,
				'new_menu_id'      => $post_id,
			];

			set_transient( 'aadaddon_import_menu_mapping', $wcfio_menu_mapping, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Fix issue with WP Navigation block.
	 *
	 * We did this by looping through all the imported posts with the WP Navigation block
	 * and replacing the original menu ID with the new menu ID.
	 *
	 * @since 3.2.0
	 */
	public function fix_imported_wp_navigation() {

		// Get the `wp_navigation` import mapping.
		$nav_import_mapping = get_transient( 'aadaddon_import_menu_mapping' );

		// Get the post IDs that needs to be updated.
		$posts_nav_block = get_transient( 'aaeaddon_import_posts_with_nav_block' );

		if ( empty( $nav_import_mapping ) || empty( $posts_nav_block ) ) {
			return;
		}

		$replace_pairs = [];

		foreach ( $nav_import_mapping as $mapping ) {
			$replace_pairs[ '<!-- wp:navigation {"ref":' . $mapping['original_menu_id'] . '} /-->' ] = '<!-- wp:navigation {"ref":' . $mapping['new_menu_id'] . '} /-->';
		}

		// Loop through each the posts that needs to be updated.
		foreach ( $posts_nav_block as $post_id ) {
			$post_nav_block = get_post( $post_id );

			if ( empty( $post_nav_block ) || empty( $post_nav_block->post_content ) ) {
				return;
			}

			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => strtr( $post_nav_block->post_content, $replace_pairs ),
				]
			);
		}
	}

	/**
	 * Update imported terms count.
	 */
	private function update_terms_count() {

		foreach ( $this->imported_terms as $tax => $terms ) {
			wp_update_term_count_now( $terms, $tax );
		}
	}

	
}
