<?php

/**
 * Static functions used in the Animation Addons plugin.
 *
 * @package Wealcoder\AnimationAddons
 */
namespace Wealcoder\AnimationAddons\Admin\Base;

defined( 'ABSPATH' ) || die();
/**
 * Class with static helper functions.
 */
class Helpers {
	/**
	 * Holds the date and time string for demo import and log file.
	 *
	 * @var string
	 */
	public static $demo_import_start_time = '';

	/**
	 * Filter through the array of import files and get rid of those who do not comply.
	 *
	 * @param  array $import_files list of arrays with import file details.
	 * @return array list of filtered arrays.
	 */
	public static function validate_import_file_info( $import_files ) {

		$filtered_import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( self::is_import_file_info_format_correct( $import_file ) ) {
				$filtered_import_file_info[] = $import_file;
			}
		}

		return $filtered_import_file_info;
	}


	/**
	 * Helper function: a simple check for valid import file format.
	 *
	 * @param  array $import_file_info array with import file details.
	 * @return boolean
	 */
	private static function is_import_file_info_format_correct( $import_file_info ) {

		if ( empty( $import_file_info['import_file_name'] ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file_info array with import file details.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_files( $import_file_info ) {

		$downloaded_files = array(
			'content'    => '',				
		);

		$downloader = new Downloader();

		$import_file_info = self::apply_filters('aaeaddon/pre_download_import_files', $import_file_info);

		// ----- Set content file path -----
		// Check if 'import_file_url' is not defined. That would mean a local file.
		if ( empty( $import_file_info['import_file_url'] ) ) {
			if ( file_exists( $import_file_info['local_import_file'] ) ) {
				$downloaded_files['content'] = $import_file_info['local_import_file'];
			}
		}
		else {
			// Set the filename string for content import file.
			$content_filename = self::apply_filters( 'aaeaddon/downloaded_content_file_prefix', 'demo-content-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'aaeaddon/downloaded_content_file_suffix_and_file_extension', '.xml' );

			// Download the content import file.
			$downloaded_files['content'] = $downloader->download_file( $import_file_info['import_file_url'], $content_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['content'] ) ) {
				return $downloaded_files['content'];
			}
		}

		return $downloaded_files;
	}


	/**
	 * Write content to a file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error path to the saved file or WP_Error object with error message.
	 */
	public static function write_to_file( $content, $file_path ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		if ( ! $wp_filesystem->put_contents( $file_path, $content ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'animation-addons-for-elementor' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file path on successful file write.
		return $file_path;
	}


	/**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}
		self::set_import_state( $content );
		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		$existing_data = '';
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
		}

		// Style separator.
		$separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		if ( ! $wp_filesystem->put_contents( $file_path, $existing_data . $separator . $content . PHP_EOL ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'animation-addons-for-elementor' ),
					'<br>',
					$file_path
				)
			);
		}

		return true;
	}


	/**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to read a file.
		global $wp_filesystem;

		$data = $wp_filesystem->get_contents( $file_path );

		if ( ! $data ) {
			return new \WP_Error(
				'failed_reading_file_from_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %1$s%2$s.', 'animation-addons-for-elementor' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $data;
	}
	/**
	 * Get the plugin page setup data.
	 *
	 * @return array
	 */
	public static function get_plugin_page_setup_data() {
		return Helpers::apply_filters( 'aaeaddon/plugin_page_setup', array(
			'parent_slug' => 'wcf_addons_settings',	
			'capability'  => 'import',
			'menu_slug'   => 'wcf_addons_settings',
		) );
	}

	/**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {
		// Check if the file-system method is 'direct', if not display an error.
		if ( ! ( 'direct' === get_filesystem_method() ) ) {
			return new \WP_Error(
				'no_direct_file_access',
				sprintf( /* translators: %1$s and %2$s - strong HTML tags, %3$s - HTML link to a doc page. */
					__( 'This WordPress page does not have %1$sdirect%2$s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site. You can change this setting with these instructions: %3$s.', 'animation-addons-for-elementor' ),
					'<strong>',
					'</strong>',
					'<a href="http://gregorcapuder.com/wordpress-how-to-set-direct-filesystem-method/" target="_blank">How to set <strong>direct</strong> filesystem method</a>'
				)
			);
		}

		// Get plugin page settings.
		$plugin_page_setup = self::get_plugin_page_setup_data();

		// Get user credentials for WP file-system API.
		$demo_import_page_url = wp_nonce_url( $plugin_page_setup['parent_slug'] . '?page=' . $plugin_page_setup['menu_slug'], $plugin_page_setup['menu_slug'] );

		if ( false === ( $creds = request_filesystem_credentials( $demo_import_page_url, '', false, false, null ) ) ) {
			return new \WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'animation-addons-for-elementor' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new \WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'animation-addons-for-elementor' )
			);
		}

		return true;
	}


	/**
	 * Get log file path
	 *
	 * @return string, path to the log file
	 */
	public static function get_log_path() {
		$upload_dir  = wp_upload_dir();
		$upload_path = self::apply_filters( 'aaeaddon/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		$log_path = $upload_path . self::apply_filters( 'aaeaddon/log_file_prefix', 'log_file_' ) . self::$demo_import_start_time . self::apply_filters( 'aaeaddon/log_file_suffix_and_file_extension', '.txt' );

		self::register_file_as_media_attachment( $log_path );

		return $log_path;
	}


	/**
	 * Register file as attachment to the Media page.
	 *
	 * @param string $log_path log file path.
	 * @return void
	 */
	public static function register_file_as_media_attachment( $log_path ) {
		// Check the type of file.
		$log_mimes = array( 'txt' => 'text/plain' );
		$filetype  = wp_check_filetype( basename( $log_path ), self::apply_filters( 'aaeaddon/file_mimes', $log_mimes ) );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => self::get_log_url( $log_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => self::apply_filters( 'aaeaddon/attachment_prefix', esc_html__( 'Starter Template Import - ', 'animation-addons-for-elementor' ) ) . preg_replace( '/\.[^.]+$/', '', basename( $log_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the file as attachment in Media page.
		$attach_id = wp_insert_attachment( $attachment, $log_path );
	}


	/**
	 * Get log file url
	 *
	 * @param string $log_path log path to use for the log filename.
	 * @return string, url to the log file.
	 */
	public static function get_log_url( $log_path ) {
		$upload_dir = wp_upload_dir();
		$upload_url = self::apply_filters( 'aaeaddon/upload_file_url', trailingslashit( $upload_dir['url'] ) );

		return $upload_url . basename( $log_path );
	}


	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				sprintf( 
					/* translators: %1$s - opening div and paragraph HTML tags, %2$s - closing div and paragraph HTML tags. */
					esc_html__( 'Your user role isn\'t high enough. You don\'t have permission to import demo data.', 'animation-addons-for-elementor' ),
					'<div class="notice notice-error"><p>',
					'</p></div>'
				)
			);
			
		}
	}


	/**
	 * Process uploaded files and return the paths to these files.
	 *
	 * @param array  $uploaded_files $_FILES array form an AJAX request.
	 * @param string $log_file_path path to the log file.
	 * @return array of paths to the content import and widget import files.
	 */
	public static function process_uploaded_files( $uploaded_files, $log_file_path ) {
		// Variable holding the paths to the uploaded files.
		$selected_import_files = array(
			'content'    => '',
			'widgets'    => ''		
		);

		// Upload settings to disable form and type testing for AJAX uploads.
		$upload_overrides = array(
			'test_form' => false,
		);

		// Register the import file types and their mime types.
		// add_filter( 'upload_mimes', function ( $defaults ) {

		// 	$custom = [
		// 		'xml'  => 'text/xml',
		// 		'json' => 'application/json',		
		// 	];
		
			
		// 	return array_merge( $custom, $defaults );
		// } );

		// Error data if the demo file was not provided.
		$file_not_provided_error = array(
			'error' => esc_html__( 'No file provided.', 'animation-addons-for-elementor' )
		);

		// Handle demo file uploads.
		$content_file_info = isset( $uploaded_files['content_file'] ) ?
			wp_handle_upload( $uploaded_files['content_file'], $upload_overrides ) :
			$file_not_provided_error;

		// Process content import file.
		if ( $content_file_info && ! isset( $content_file_info['error'] ) ) {
			// Set uploaded content file.
			$selected_import_files['content'] = $content_file_info['file'];
		}
		else {
			// Add this error to log file.
			$log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Content file was not uploaded. Error: %s', 'animation-addons-for-elementor' ),
					$content_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'animation-addons-for-elementor' )
			);
		}


		// Add this message to log file.
		$log_added = self::append_to_file(
			__( 'The import files were successfully uploaded!', 'animation-addons-for-elementor' ) . self::import_file_info( $selected_import_files ),
			$log_file_path,
			esc_html__( 'Upload files' , 'animation-addons-for-elementor' )
		);

		// Return array with paths of uploaded files.
		return $selected_import_files;
	}


	/**
	 * Get import file information and max execution time.
	 *
	 * @param array $selected_import_files array of selected import files.
	 */
	public static function import_file_info( $selected_import_files ) {

		return PHP_EOL .
		sprintf( /* translators: %s - the max execution time. */
			__( 'Initial max execution time = %s', 'animation-addons-for-elementor' ),
			ini_get( 'max_execution_time' )
		) . PHP_EOL .
		sprintf( /* translators: %1$s - new line break, %2$s - the site URL, %3$s - the file path for content import, %4$s - the file path for widgets import, %5$s - the file path for widgets import, %6$s - the file path for redux import. */
			__( 'Files info:%1$sSite URL = %2$s%1$sData file = %3$s%1$s', 'animation-addons-for-elementor' ),
			PHP_EOL,
			get_site_url(),
			empty( $selected_import_files['content'] ) ? esc_html__( 'not defined!', 'animation-addons-for-elementor' ) : $selected_import_files['content'],		
		);
	}


	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {
		// Add this error to log file.
		$log_added = self::append_to_file(
			$error_text,
			$log_file_path,
			$separator
		);
		$template_data              = [];
		$response                   = [];
		$template_data['next_step'] = 'fail';
		$response['msg']            = $error_text;
		$response['progress']       = 0;
		$response['template']       = wp_unslash( $template_data );
		wp_send_json( $response );
	}


	/**
	 * Set the $demo_import_start_time class variable with the current date and time string.
	 */
	public static function set_demo_import_start_time() {
		self::$demo_import_start_time = gmdate( self::apply_filters( 'aaeaddon/date_format_for_file_names', 'Y-m-d__H-i-s' ) );
	}

	/**
	 * Set the startter transient with the current importer data.
	 *
	 * @param array $data Data to be saved to the transient.
	 */
	public static function set_st_import_data_transient( $data ) {
		set_transient( 'aaeaddon_st_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}


	/**
	 * Record what the import is currently doing, for the progress screen.
	 *
	 * Deliberately NOT autoloaded, and this is the only writer so it cannot
	 * drift back. The value is scratch: one polling request reads it while an
	 * import runs and nothing reads it afterwards. Autoloaded it was fetched
	 * on every request to the site for the rest of that site's life, and every
	 * write to an autoloaded row makes core re-serialise the WHOLE alloptions
	 * blob into the object cache -- 105 KB on the development site.
	 *
	 * @param string $message Human readable state.
	 */
	public static function set_import_state( $message ) {
		update_option( 'aaeaddon_template_import_state', (string) $message, false );
	}


	/**
	 * Drop the rows an import writes to report itself.
	 *
	 * Called when an import starts and again when it finishes: both describe a
	 * run in flight and mean nothing once it is over. Nothing used to remove
	 * them on the finishing side at all, so the last import's progress sat in
	 * the options table permanently.
	 */
	public static function clear_import_status() {
		delete_option( 'aaeaddon_template_import_state' );
		delete_option( 'aaeaddon_template_import_progress' );
	}


	/**
	 * The two halves of the name every downloaded content file is written under.
	 *
	 * Read through the same filters download_import_files() writes with, so a
	 * site that renames its downloads can still find and remove them.
	 *
	 * @return array Prefix and suffix, in that order.
	 */
	private static function download_file_name_parts() {
		return array(
			self::apply_filters( 'aaeaddon/downloaded_content_file_prefix', 'demo-content-import-file_' ),
			self::apply_filters( 'aaeaddon/downloaded_content_file_suffix_and_file_extension', '.xml' ),
		);
	}


	/**
	 * Is this path a content file THIS plugin downloaded, inside uploads?
	 *
	 * Two independent tests, because the answer decides whether a file is
	 * deleted. A file the user supplied themselves keeps its own name and
	 * cannot match the prefix, and the realpath test means no filter can point
	 * the sweep at something outside the uploads directory.
	 *
	 * @param string $path Full path to check.
	 * @return boolean
	 */
	private static function is_downloaded_import_file( $path ) {
		if ( empty( $path ) || ! is_file( $path ) ) {
			return false;
		}

		list( $prefix, $suffix ) = self::download_file_name_parts();

		if ( '' === $prefix || '' === $suffix ) {
			return false;
		}

		$name = basename( $path );

		if ( 0 !== strpos( $name, $prefix ) || $suffix !== substr( $name, - strlen( $suffix ) ) ) {
			return false;
		}

		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return false;
		}

		$real = wp_normalize_path( (string) realpath( $path ) );
		$base = trailingslashit( wp_normalize_path( (string) realpath( $uploads['basedir'] ) ) );

		return '' !== $real && 0 === strpos( $real, $base );
	}


	/**
	 * Delete a content file this plugin downloaded for an import.
	 *
	 * The file is a complete copy of the demo -- 3 to 12 MB, and readable by
	 * anyone who guesses the URL, since it sits in uploads. Nothing removed it:
	 * 36 of them, 81 MB, had collected on the development site.
	 *
	 * @param string $path Full path to the file.
	 * @return boolean Whether a file was removed.
	 */
	public static function cleanup_import_file( $path ) {
		if ( ! self::is_downloaded_import_file( $path ) ) {
			return false;
		}

		wp_delete_file( $path );

		return true;
	}


	/**
	 * Remove content files left behind by imports that never finished.
	 *
	 * An import that fails, or whose tab is closed, never reaches the cleanup
	 * at the end, so a sweep when the next one starts is the only thing that
	 * can collect those. Age-gated so a download belonging to an import running
	 * in another tab is never pulled out from under it.
	 *
	 * @param integer $max_age Only remove files older than this, in seconds.
	 * @return integer Number of files removed.
	 */
	public static function sweep_stale_import_files( $max_age = DAY_IN_SECONDS ) {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return 0;
		}

		list( $prefix, $suffix ) = self::download_file_name_parts();

		if ( '' === $prefix || '' === $suffix ) {
			return 0;
		}

		$base    = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
		$pattern = $prefix . '*' . $suffix;

		// The year/month folders, plus the flat layout a site gets with
		// `uploads_use_yearmonth_folders` switched off.
		$files = array_merge(
			(array) glob( $base . $pattern ),
			(array) glob( $base . '*/*/' . $pattern )
		);

		$cutoff  = time() - (int) $max_age;
		$removed = 0;

		foreach ( $files as $file ) {
			if ( ! is_string( $file ) || filemtime( $file ) > $cutoff ) {
				continue;
			}

			if ( self::cleanup_import_file( $file ) ) {
				$removed++;
			}
		}

		return $removed;
	}


	public static function apply_filters( $hook, $default_data ) {
		$new_data = apply_filters( $hook, $default_data );  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		if ( $new_data !== $default_data ) {
			return $new_data;
		}

		$old_data = apply_filters( "st-$hook", $default_data );  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( $old_data !== $default_data ) {
			return $old_data;
		}

		return $default_data;
	}

	/**
	 * Backwards compatible do_action helper.
	 * With 3.0 we changed the action prefix from 'st-wcfio/' to just 'wcfio/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all do_action calls.
	 *
	 * @since 3.2.0 Run both `$hook` and `st-$hook` actions.
	 *
	 * @param string $hook   The action hook name.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the
	 *                       functions hooked to the action. Default empty.
	 */
	public static function do_action( $hook, ...$arg ) {
		do_action( $hook, ...$arg );  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		$args = [];
		foreach ( $arg as $argument ) {
			$args[] = $argument;
		}

		do_action_deprecated( "st-$hook", $args, '3.0.0', $hook );
	}

	/**
	 * Backwards compatible has_action helper.
	 * With 3.0 we changed the action prefix from 'st-wcfio/' to just 'aaeaddon/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all has_action calls.
	 *
	 * @param string        $hook              The name of the action hook.
	 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	public static function has_action( $hook, $function_to_check = false ) {
		if ( has_action( $hook ) ) {
			return has_action( $hook, $function_to_check );
		} else if ( has_action( "st-$hook" ) ) {
			return has_action( "st-$hook", $function_to_check );
		}

		return false;
	}	

	/**
	 * How many times an attachment may fail to download before it is skipped.
	 *
	 * ONE failure used to be final, which made a single 502 from the demo host
	 * permanently cost that image -- the visible result being a demo with holes
	 * in it and nothing but a warning in a log file nobody reads. A chunked
	 * import re-walks the file from the top on every AJAX call, so a second
	 * attempt costs nothing to arrange: the next chunk simply does not skip it.
	 *
	 * @var integer
	 */
	const FAILED_ATTACHMENT_ATTEMPTS = 2;

	/**
	 * Name of the transient holding this import's download failures.
	 *
	 * @var string
	 */
	const FAILED_ATTACHMENT_TRANSIENT = 'aaeaddon_st_importer_data_failed_attachment_imports';

	/**
	 * Failed attachment URLs mapped to how many times each has been tried.
	 *
	 * @return array URL => attempt count.
	 */
	private static function get_failed_attachment_attempts() {

		$stored = get_transient( self::FAILED_ATTACHMENT_TRANSIENT );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			return [];
		}

		$attempts = [];

		foreach ( $stored as $key => $value ) {
			if ( is_int( $key ) ) {
				// The flat list this used to store, from an import that was
				// already in flight when the plugin updated: given up on.
				$attempts[ (string) $value ] = self::FAILED_ATTACHMENT_ATTEMPTS;
				continue;
			}

			$attempts[ $key ] = (int) $value;
		}

		return $attempts;
	}

	/**
	 * Get the attachment imports that have been given up on.
	 *
	 * A URL that has failed fewer times than FAILED_ATTACHMENT_ATTEMPTS is
	 * deliberately absent, so the next chunk tries it again.
	 *
	 * @since 3.2.0
	 *
	 * @return array List of attachment URLs to skip.
	 */
	public static function get_failed_attachment_imports() {

		$given_up = [];

		foreach ( self::get_failed_attachment_attempts() as $url => $attempts ) {
			if ( $attempts >= self::FAILED_ATTACHMENT_ATTEMPTS ) {
				$given_up[] = $url;
			}
		}

		return $given_up;
	}

	/**
	 * Record that an attachment failed to import.
	 *
	 * @since 3.2.0
	 *
	 * @param string $attachment_url The attachment URL that was not imported.
	 *
	 * @return void
	 */
	public static function set_failed_attachment_import( $attachment_url ) {

		$attachment_url = (string) $attachment_url;

		if ( '' === $attachment_url ) {
			return;
		}

		$attempts = self::get_failed_attachment_attempts();

		$attempts[ $attachment_url ] = isset( $attempts[ $attachment_url ] )
			? $attempts[ $attachment_url ] + 1
			: 1;

		set_transient( self::FAILED_ATTACHMENT_TRANSIENT, $attempts, HOUR_IN_SECONDS );
	}
}
