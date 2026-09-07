<?php

/**
 * Class for declaring the content importer used in the One Click Demo Import plugin
 *
 * @package Animation Addon
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin\Base;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

class Importer
{
	/**
	 * The importer class object used for importing content.
	 *
	 * @var object
	 */
	private $importer;

	/**
	 * Time in milliseconds, marking the beginning of the import.
	 *
	 * @var float
	 */
	private $microtime;

	/**
	 * The instance of the WCF_ADDONS\Admin\Base\Logger class.
	 *
	 * @var object
	 */
	public $logger;

	/**
	 * The instance of the Import class.
	 *
	 * @var object
	 */
	private $wcfio;

	/**
	 * Output-buffer nesting level recorded immediately before this class
	 * called ob_start(), or null when it holds no buffer.
	 *
	 * The import runs third-party code (WordPress core's importer, every
	 * filter hooked to it) and can end inside one of our own callbacks, so
	 * the buffer cannot simply be opened and closed on adjacent lines. This
	 * marker is what lets close_import_buffer() close exactly the buffers
	 * opened at or above our own and never one that existed before it.
	 *
	 * @var int|null
	 */
	private $ob_level = null;

	/**
	 * Include required files.
	 */
	private function include_required_files()
	{
		// AAEImporter extends WXRImporter, which extends the core \WP_Importer
		// class. Core does not autoload that class, so it is loaded here with
		// require_once and used immediately by the constructor below.
		if (! class_exists('\WP_Importer')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}
	}


	/**
	 * Imports content from a WordPress export file.
	 *
	 * @param string $data_file path to xml file, file with WordPress export data.
	 */
	public function import($data_file)
	{
		$this->importer->import($data_file);
	}


	/**
	 * Set the logger used in the import
	 *
	 * @param object $logger logger instance.
	 */
	public function set_logger($logger)
	{
		$this->importer->set_logger($logger);
	}


	/**
	 * Get all protected variables from the WXR_Importer needed for continuing the import.
	 */
	public function get_importer_data()
	{
		return $this->importer->get_importer_data();
	}


	/**
	 * Sets all protected variables from the WXR_Importer needed for continuing the import.
	 *
	 * @param array $data with set variables.
	 */
	public function set_importer_data($data)
	{
		$this->importer->set_importer_data($data);
	}


	/**
	 * Import content from an WP XML file.
	 *
	 * @param string $import_file_path Path to the import file.
	 */
	public function import_content($import_file_path)
	{
		$this->microtime = microtime(true);

		// Increase PHP max execution time. Just in case, even though the AJAX calls are only 60 sec long.
		if (! empty(ini_get('disable_functions')) &&  strpos(ini_get('disable_functions'), 'set_time_limit') === false) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			set_time_limit(Helpers::apply_filters('aaeaddon/set_time_limit_for_demo_data_import', 60));
		}

		// Disable import of authors.
		add_filter('wxr_importer.pre_process.user', '__return_false');

		// Check, if we need to send another AJAX request and set the importing author to the current user.
		add_filter('wxr_importer.pre_process.post', array($this, 'new_ajax_request_maybe'));

		// Disables generation of multiple image sizes (thumbnails) in the content import step.
		if (! Helpers::apply_filters('aaeaddon/regenerate_thumbnails_in_content_import', true)) {
			add_filter('intermediate_image_sizes_advanced', '__return_null');
		}

		// Import content.
		if (! empty($import_file_path)) {
			$this->ob_level = ob_get_level();
			ob_start();

			// finally, not a plain close: an exception anywhere inside the
			// importer would otherwise leave the buffer open for the rest of
			// the request.
			try {
				$this->import($import_file_path);
			} finally {
				$message = $this->close_import_buffer();
			}
		}

		// Return any error messages for the front page output (errors, critical, alert and emergency level messages only).
		return $this->logger->error_output;
	}


	/**
	 * Close the output buffer this class opened and return what it captured.
	 *
	 * Safe to call more than once and safe to call when nothing is open: it
	 * unwinds only down to the level recorded when we called ob_start(), so a
	 * buffer belonging to WordPress, a theme or another plugin is never
	 * closed. Returns an empty string when we hold no buffer.
	 *
	 * @return string
	 */
	private function close_import_buffer()
	{
		$message = '';

		if (null !== $this->ob_level) {
			while (ob_get_level() > $this->ob_level) {
				$message = (string) ob_get_clean() . $message;
			}
			$this->ob_level = null;
		}

		return $message;
	}


	/**
	 * Check if we need to create a new AJAX request, so that server does not timeout.
	 *
	 * @param array $data current post data.
	 * @return array
	 */
	public function new_ajax_request_maybe($data)
	{

		if (empty($data)) {
			return $data;
		}

		$time = microtime(true) - $this->microtime;

		// We should make a new ajax call, if the time is right.
		if ($time > Helpers::apply_filters('aaeaddon/time_for_one_ajax_call', 25)) {
			$response = array(
				'status'  => 'newAJAX',
				'message' => 'Time for new AJAX request!: ' . $time,
			);

			// This runs as a filter callback from inside the importer and ends
			// in wp_send_json(), which does not return -- so the buffer opened
			// by import_content() has to be closed here or it would never be
			// closed at all. close_import_buffer() closes only what we opened.
			$message = $this->close_import_buffer();

			// Add any error messages to the frontend_error_messages variable in main class.
			if (! empty($message)) {
				$this->wcfio->append_to_frontend_error_messages($message);
			}

			// Add message to log file.
			$log_added = Helpers::append_to_file(
				__('Content installing', 'animation-addons-for-elementor') . PHP_EOL . $message,
				$this->wcfio->get_log_file_path(),
				''
			);

			// Set the current importer stat, so it can be continued on the next AJAX call.
			$this->set_current_importer_data();
			$response['state'] = get_option('aaeaddon_template_import_state');
			// Send the request for a new AJAX call.
			wp_send_json($response);
		}

		// Set importing author to the current user.
		// Fixes the [WARNING] Could not find the author for ... log warning messages.
		$current_user_obj    = wp_get_current_user();
		$data['post_author'] = $current_user_obj->user_login;

		return $data;
	}


	/**
	 * Set current state of the content importer, so we can continue the import with new AJAX request.
	 */
	private function set_current_importer_data()
	{
		$data = array_merge($this->wcfio->get_current_importer_data(), $this->get_importer_data());

		Helpers::set_st_import_data_transient($data);
	}

	/**
	 * Constructor method.
	 *
	 * @param array  $importer_options Importer options.
	 * @param object $logger           Logger object used in the importer.
	 */
	public function __construct($importer_options = array(), $logger = null)
	{
		// Include files that are needed for WordPress Importer v2.
		$this->include_required_files();

		// Set the WordPress Importer v2 as the importer used in this plugin.
		// More: https://github.com/humanmade/WordPress-Importer.
		$this->importer = new AAEImporter($importer_options);

		// Set logger to the importer.
		$this->logger = $logger;
		if (! empty($this->logger)) {
			$this->set_logger($this->logger);
		}

		$this->wcfio = OneClickImport::get_instance();
	}
}
