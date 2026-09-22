<?php
namespace Wealcoder\AnimationAddons\Admin\Base;

use Wealcoder\AnimationAddons\Nonce;
use WP_Error;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

/**
 * One Click Import class
 */
class OneClickImport
{

	public $file_path = 'aaeaddon_tpl_file.xml';
	private static $instance;
	public $importer;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files = array();

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
	 * IDs of the pages THIS import wrote, across every chunk.
	 *
	 * final_response() reads it to tell a page import that created a page
	 * from one that created nothing -- the WXR engine skips a post it has
	 * seen before without a log line at any level, and the response used to
	 * say "Congrats" either way.
	 *
	 * @var int[]
	 */
	private $imported_pages = array();

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
	public static function get_instance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Class construct function, to initiate the plugin.
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct()
	{
		add_action('wp_ajax_aaeaddon_upload_manual_import_file', [$this, 'import_demo_data_ajax_callback']);

		// setup_st_importer() is deliberately NOT on admin_init. Building the
		// importer pulls in the whole WXR engine, and admin_init fires on
		// every wp-admin page AND every admin-ajax request -- heartbeat,
		// another plugin's dashboard widget, all of it -- so hooking it there
		// loaded ~99 KB of import machinery on requests that have nothing to
		// do with importing. The two places that touch $this->importer call
		// setup_st_importer() themselves; it is idempotent.
		add_action('set_object_terms', array($this, 'add_imported_terms'), 10, 6);
		add_filter('wxr_importer.pre_process.post', [$this, 'skip_failed_attachment_import']);
		add_filter('wxr_importer.pre_process.post', [$this, 'fresh_copy_for_page_import']);
		add_action('wxr_importer.process_failed.post', [$this, 'handle_failed_attachment_import'], 10, 5);
		add_action('admin_init', [$this, 'register_import_report_notice'], 12);
		// The importer's own limit is 0 = unbounded, which does not refuse a
		// 900 MB demo video -- it downloads it for as long as the host allows
		// and then dies mid-chunk. A bounded default refuses it up front with
		// a reason the report can show; the site owner can raise it through
		// the same (core-named) filter.
		add_filter('import_attachment_size_limit', [$this, 'default_attachment_size_limit'], 5);
		add_action('wp_import_insert_post', [$this, 'save_wp_navigation_import_mapping'], 10, 4);
		add_action('wp_import_insert_post', [$this, 'save_wp_page_import_track'], 10, 4);
		add_action('aaeaddon/after_import', [$this, 'fix_imported_wp_navigation']);
		// 'aae_lite_get_latest_imported_pages' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_lite_get_latest_imported_pages', 'aaeaddon_lite_get_latest_imported_pages', [$this,'get_latest_imported_pages'] );
			
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

	public function save_wp_page_import_track($post_id, $original_id, $postdata, $data){
		if (empty($postdata['post_content'])) {
			return;
		}

		if ($postdata['post_type'] == 'page') {
			$batch_id = 'wxr_' . gmdate('Ymd_His');
			update_option('aaeaddon_last_import_batch', $batch_id);
			add_post_meta($post_id, 'aae_import_batch', $batch_id, true);
			add_post_meta($post_id, 'aae_imported', 1, true);
			$this->imported_pages[] = (int) $post_id;
		}
	}

	/**
	 * Is the running import a starter PAGE import (Pages -> Import Page)?
	 *
	 * The client sends `import_type=page` on every request of that flow --
	 * the step machine and the content chunks alike -- and `full-demo` from
	 * the template grid. Read after Helpers::verify_ajax_call() has run.
	 *
	 * @return bool
	 */
	private function is_page_import()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the calling handler verified the nonce.
		return isset($_POST['import_type']) && 'page' === sanitize_text_field(wp_unslash($_POST['import_type']));
	}

	/**
	 * Make a starter PAGE import create the page even when the same page was
	 * imported before.
	 *
	 * The WXR engine prefills a map of every GUID already on the site and
	 * skips a post whose GUID it finds there -- the right behaviour for a
	 * whole-site migration, where an item is the same page it was last time.
	 * "Import Page" is not a migration: the visitor picked a design and wants
	 * it on their site, and a second import of the same design is a request
	 * for a second copy. Measured: with #3365 ("Services", from an earlier
	 * import) on the site, re-importing its starter page wrote nothing, the
	 * engine logged nothing at any level, and the screen said "Congrats" with
	 * a "Go to page" button pointing at the PREVIOUS import's batch.
	 *
	 * So for a page import the page's GUID is made unique to this import
	 * before the engine looks it up. Only the page: attachments keep their
	 * GUID so an image already in the media library is reused, not fetched
	 * again. WordPress de-duplicates the slug (services, services-2) as it
	 * does for any page created twice.
	 *
	 * @param array $data Post data from the WXR item.
	 *
	 * @return array
	 */
	public function fresh_copy_for_page_import($data)
	{
		if (
			empty($data) ||
			empty($data['post_type']) ||
			'page' !== $data['post_type'] ||
			empty($data['guid']) ||
			! $this->is_page_import()
		) {
			return $data;
		}

		// A fragment, so the original URL is still readable in the stored
		// GUID and nothing downstream that parses it as a URL is disturbed.
		$data['guid'] .= '#aae-page-import-' . gmdate('YmdHis');

		return $data;
	}

	function get_latest_imported_pages() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if (
			! isset( $_POST['nonce'] ) ||
			! check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce', false )
		) {
			wp_send_json_error( [ 'message' => 'Invalid or missing nonce' ], 403 );
		}

		// A nonce proves where the request came from, not what the user is
		// allowed to do. This reports what the last import wrote, so it is
		// gated on the same authority the importer itself needs.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Access denied.' ], 403 );
		}

		$per_page = isset($_POST['per_page']) ? max(1, (int) $_POST['per_page']) : 1; // latest one by default
		$batch_id = get_option('aaeaddon_last_import_batch');

		// If batch not found, gracefully fall back to any page marked imported
		$meta_query = [];
		if ($batch_id) {
			$meta_query[] = [
				'key'   => 'aae_import_batch',
				'value' => $batch_id,
				'compare' => '='
			];
		} else {
			$meta_query[] = [
				'key'   => 'aae_imported',
				'value' => 1,
				'compare' => '='
			];
		}

		$q = new \WP_Query([
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'date',        // or 'ID'
			'order'          => 'DESC',
			// Filtering imported pages by the aae_imported marker meta; lookup by meta is required here.
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'no_found_rows'  => true,
			'fields'         => 'ids',
		]);

		if (empty($q->posts)) {
			wp_send_json_success([
				'batch_id' => $batch_id,
				'pages'    => []
			]);
		}

		$pages = array_map(function($id) {
			return [
				'id'        => $id,
				'title'     => get_the_title($id),
				'permalink' => get_permalink($id),
				'date'      => get_post_time('c', true, $id),
			];
		}, $q->posts);

		wp_send_json_success([
			'batch_id' => $batch_id,
			'pages'    => $pages
		]);
	}

	/**
	 * Default for core's `import_attachment_size_limit` when nothing set one.
	 *
	 * @param int $limit Bytes; 0 means unbounded.
	 * @return int
	 */
	public function default_attachment_size_limit( $limit ) {
		return (int) $limit > 0 ? (int) $limit : 100 * MB_IN_BYTES;
	}

	public function import_demo_data_ajax_callback()
	{
		// Try to update PHP memory limit (so that it does not run out of it).
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		ini_set('memory_limit', Helpers::apply_filters('aaeaddon/st/import_memory_limit', '1024M'));

		// An attachment is streamed to disk inside THIS request, so the
		// importer's 60-second HTTP timeout only means something if PHP is
		// allowed to run that long. Shared hosts default max_execution_time
		// to 30 s, which killed a large video download mid-chunk and surfaced
		// as "import failed" with nothing in the log. Same call core's own
		// importer makes; a host that forbids it simply ignores it.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- disabled_functions on some hosts.
		}

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if (! $use_existing_importer_data) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			// A NEW content import (not a chunk continuation): let per-import
			// trackers drop the previous run's state. `import_start` cannot be
			// used for this -- AaeaddonWXRImporter fires it on every chunk.
			do_action('aaeaddon/content_import/fresh_start'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- slash-namespaced plugin hook, same family as aaeaddon/after_import.

			// Start from a clean progress reading. report_progress() carries the
			// previous chunk's figure forward as a floor, so a leftover row from
			// an earlier demo would make this import's bar open part-filled.
			Helpers::clear_import_status();

			// Collect the content files of imports that never reached the end.
			// Age-gated, so a download belonging to an import running in another
			// tab is never pulled out from under it.
			Helpers::sweep_stale_import_files();

			// Define log file path.
			$this->log_file_path = Helpers::get_log_path();

			// Get selected file index or set it to 0.
			$this->selected_index = 0;
			$template_data = [];
			check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );
			if (isset($_POST['template_data'])) {

				$json_data     = sanitize_text_field(wp_unslash($_POST['template_data']));  // Remove slashes if added by WP		
				$template_data = json_decode($json_data, true);

				if (json_last_error() === JSON_ERROR_NONE) {
					array_walk_recursive($template_data, function (&$value) {
						if (is_string($value)) {
							$value = sanitize_text_field($value);
						}
					});
				}
			}

			if (isset($template_data['file']['content_url'])) { // Provide url
				// Download the import files (content).

				$file_path = $template_data['file']['content_url'];

				$this->selected_import_files = Helpers::download_import_files(['import_file_url' => $file_path]);

				// Check Errors.
				if (is_wp_error($this->selected_import_files)) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__('Downloaded files', 'animation-addons-for-elementor')
					);
				}
			} else {
				$response                   = [];
				$template_data['next_step'] = 'fail';
				$response['msg']            = esc_html__('No import files specified!', 'animation-addons-for-elementor');
				$response['progress']       = 0;
				$response['template']       = wp_unslash($template_data);
				wp_send_json($response);
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_st_import_data_transient($this->get_current_importer_data());

		if (! $this->before_import_executed) {
			$this->before_import_executed = true;

			/**
			 * 2). Execute the actions hooked to the 'aaeaddon/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			Helpers::do_action('aaeaddon/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index);
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		if (! empty($this->selected_import_files['content'])) {
			$this->setup_st_importer();
			$this->append_to_frontend_error_messages($this->importer->import_content($this->selected_import_files['content']));
		}

		Helpers::do_action('aaeaddon/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index);

		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_st_import_data_transient($this->get_current_importer_data());

		// Request the after all import AJAX call.
		if (false !== Helpers::has_action('aaeaddon/after_all_import_execution')) {
			wp_send_json(array('status' => 'afterAllImportAJAX'));
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback()
	{
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ($this->use_existing_importer_data()) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			Helpers::do_action('aaeaddon/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index);
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * Send a JSON response with final report.
	 */
	private function final_response()
	{
		// The content file is a complete copy of the demo sitting in uploads,
		// readable by anyone who guesses its URL, and every later step of the
		// import works from the database rather than from it. Nothing removed
		// it before: 36 of them, 81 MB, had collected on the dev site.
		if (! empty($this->selected_import_files['content'])) {
			Helpers::cleanup_import_file($this->selected_import_files['content']);
		}

		// Delete importer data transient for current import.
		delete_transient('aaeaddon_st_importer_data');
		// What this import could not download becomes the admin notice's
		// report -- read it BEFORE the row goes, or the list is gone with it.
		$lost = Helpers::record_import_report();
		// Was misspelled twice over ('aad' for 'aae', 'mporter' for 'importer'),
		// so this had never deleted anything and the real row -- 7 KB of failed
		// attachment URLs -- outlived every import. Helpers owns the name now.
		delete_transient(Helpers::FAILED_ATTACHMENT_TRANSIENT);
		delete_transient('aaeaddon_import_menu_mapping');
		delete_transient('aaeaddon_import_posts_with_nav_block');

		// The CPT Builder caches its registrations in these two options and
		// returns early while the cache is non-empty. The content just
		// imported may carry new post-type and taxonomy definitions; a cache
		// built before they existed would keep them unregistered on every
		// later request until someone opened the CPT Builder screen. The
		// template importer clears them BEFORE the content step, which is the
		// wrong side of it; this is the right one. Plain deletes, because the
		// builder's class is only loaded while its extension is switched on.
		\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( 'aaeaddon_cpts_cache' );
		\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( 'aaeaddon_taxs_cache' );

		$response['msg'] = $lost > 0
			? sprintf(
				/* translators: %d: number of media files that could not be downloaded */
				esc_html( _n( 'Content imported; %d media file could not be downloaded (see the notice on your dashboard).', 'Content imported; %d media files could not be downloaded (see the notice on your dashboard).', $lost, 'animation-addons-for-elementor' ) ),
				$lost
			)
			: esc_html__('Congrats, your demo has been imported.', 'animation-addons-for-elementor');
		$response['progress'] = 80;
		$next_step            = 'check-theme';

		// A page import that wrote no page is a failure, whatever the engine
		// said -- and the engine says nothing: a skipped item logs at no
		// level. Reporting "Congrats" here sent the user to a "Go to page"
		// button built from the PREVIOUS import's batch, i.e. a different
		// page than the one they picked. The engine's own error lines, if it
		// produced any, are the only diagnosis there is, so they go into the
		// message rather than being discarded.
		if ( $this->is_page_import() && empty( $this->imported_pages ) ) {
			$why = implode( ' ', array_map( 'wp_strip_all_tags', (array) $this->frontend_error_messages ) );
			$response['msg'] = trim(
				esc_html__( 'No page was imported: the content file held nothing this site could import.', 'animation-addons-for-elementor' )
				. ( '' !== $why ? ' ' . esc_html( $why ) : '' )
			);
			$response['progress'] = 0;
			$next_step            = 'fail';
		}

		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );
		if (isset($_POST['template_data'])) {
			if (isset($template_data['local_path'])) {
				unset($template_data['local_path']);
			}
			$json_data                  = sanitize_text_field(wp_unslash($_POST['template_data']));  // Remove slashes if added by WP
			$template_data              = json_decode($json_data, true);
			$template_data['next_step'] = $next_step;
			$response['template']       = wp_unslash($template_data);
		}

		wp_send_json($response);
	}


	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data()
	{
		if ($data = get_transient('aaeaddon_st_importer_data')) {

			// FIRST -- setup_st_importer() clears $this->import_files, so
			// building the importer after restoring the transient would wipe
			// the value this method exists to restore. Reached only when a
			// previous chunk left importer data behind, i.e. an import really
			// is in progress.
			$this->setup_st_importer();

			$this->frontend_error_messages = empty($data['frontend_error_messages']) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty($data['log_file_path']) ? '' : $data['log_file_path'];
			$this->selected_index          = empty($data['selected_index']) ? 0 : $data['selected_index'];
			$this->selected_import_files   = empty($data['selected_import_files']) ? array() : $data['selected_import_files'];
			$this->import_files            = empty($data['import_files']) ? array() : $data['import_files'];
			$this->before_import_executed  = empty($data['before_import_executed']) ? false : $data['before_import_executed'];
			$this->imported_terms          = empty($data['imported_terms']) ? [] : $data['imported_terms'];
			$this->imported_pages          = empty($data['imported_pages']) ? [] : array_map('intval', (array) $data['imported_pages']);

			$this->importer->set_importer_data($data);

			return true;
		}
		return false;
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data()
	{
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_import_executed'  => $this->before_import_executed,
			'imported_terms'          => $this->imported_terms,
			'imported_pages'          => $this->imported_pages,
		);
	}


	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path()
	{
		return $this->log_file_path;
	}


	/**
	 * Setter function to append additional value to the private frontend_error_messages value.
	 *
	 * @param string $additional_value The additional value that will be appended to the existing frontend_error_messages.
	 */
	public function append_to_frontend_error_messages($text)
	{
		$lines = array();

		if (! empty($text)) {
			$text = str_replace('<br>', PHP_EOL, $text);
			$lines = explode(PHP_EOL, $text);
		}

		foreach ($lines as $line) {
			if (! empty($line) && ! in_array($line, $this->frontend_error_messages)) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}


	/**
	 * Display the frontend error messages.
	 *
	 * @return string Text with HTML markup.
	 */
	public function frontend_error_messages_display()
	{
		$output = '';

		if (! empty($this->frontend_error_messages)) {
			foreach ($this->frontend_error_messages as $line) {
				$output .= esc_html($line);
				$output .= '<br>';
			}
		}

		return $output;
	}


	/**
	 * Build the importer, once, at the point something needs it.
	 *
	 * Called from the import flow rather than from admin_init -- see the
	 * note beside the removed hook in __construct(). Idempotent, so the
	 * call sites do not have to know whether it has already run.
	 */
	public function setup_st_importer()
	{
		// Null check, not `instanceof Importer` -- naming the class here
		// would be asking about a class this method has not required yet.
		if (null !== $this->importer) {
			return;
		}

		// Get info of import data files and filter it.
		$this->import_files = array();
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$attachment_status =  array_key_exists('attachment', $_POST) ? sanitize_text_field(wp_unslash($_POST['attachment'])) : true; // Remove slashes if added by WP	
		// Importer options array.
		$importer_options = array(
			'fetch_attachments' => $attachment_status,
		);

		// Logger options for the logger used in the importer.
		$logger_options = array(
			'logger_min_level' => 'warning',
		);

		// This method is the only code in either plugin that names Importer,
		// so its file is required here rather than on every admin request.
		// load_engine() then brings in the rest -- it has to run before the
		// Logger line below, not inside Importer's own constructor, because
		// Logger extends WPImporterLoggerCLI. Everything is require_once, so
		// a second import step asking again costs nothing.
		require_once __DIR__ . '/Importer.php';
		Importer::load_engine();

		// Configure logger instance and set it to the importer.
		$logger            = new Logger();
		$logger->min_level = $logger_options['logger_min_level'];

		// Create importer instance with proper parameters.
		$this->importer = new Importer($importer_options, $logger);
	}


	/**
	 * Add imported terms.
	 *
	 * Mainly it's needed for saving all imported terms and trigger terms count updates.
	 * WP core term defer counting is not working, since import split to chunks and we are losing `$_deffered` array
	 * items between ajax calls.
	 */
	public function add_imported_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
	{

		if (! isset($this->imported_terms[$taxonomy])) {
			$this->imported_terms[$taxonomy] = array();
		}

		$this->imported_terms[$taxonomy] = array_unique(array_merge($this->imported_terms[$taxonomy], $tt_ids));
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
	public function skip_failed_attachment_import($data)
	{
		// Check if failed import.
		if (
			! empty($data) &&
			! empty($data['post_type']) &&
			$data['post_type'] === 'attachment' &&
			! empty($data['attachment_url'])
		) {
			// Get the previously failed imports.
			$failed_media_imports = Helpers::get_failed_attachment_imports();

			if (! empty($failed_media_imports) && in_array($data['attachment_url'], $failed_media_imports, true)) {
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
	public function handle_failed_attachment_import($post_id, $data, $meta, $comments, $terms)
	{

		if (empty($data) || empty($data['post_type']) || $data['post_type'] !== 'attachment') {
			return;
		}

		$reason = is_wp_error( $post_id ) ? $post_id->get_error_message() : '';

		Helpers::set_failed_attachment_import( $data['attachment_url'], $reason );
	}

	/**
	 * Tell the administrator what the last import could not download.
	 *
	 * The import ends in "Congrats" whatever happened to its media: a demo
	 * host that answered 404 for every file, a 30-second execution limit that
	 * killed a video download, a font type WordPress refused -- each of them
	 * left a demo with holes in it and a line in a log file nobody reads.
	 * One dismissible notice per import (its id carries the import time), so
	 * dismissing it never hides the NEXT import's report.
	 *
	 * @return void
	 */
	public function register_import_report_notice() {
		if ( ! class_exists( '\Wealcoder\AnimationAddons\Admin\Notices\Notices' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = Helpers::get_import_report();
		if ( null === $report ) {
			return;
		}

		$failed  = $report['failed_media'];
		$blocked = false;
		$items   = '';
		foreach ( $failed as $url => $why ) {
			if ( false !== stripos( (string) $why, 'blocked requests through HTTP' ) ) {
				$blocked = true;
			}
			$items .= sprintf(
				'<li><code>%1$s</code>%2$s</li>',
				esc_html( $url ),
				'' !== (string) $why ? ' &mdash; ' . esc_html( $why ) : ''
			);
		}

		$intro = sprintf(
			/* translators: %d: number of files */
			esc_html( _n(
				'Animation Addons: %d file of the imported template could not be downloaded from the demo server. The pages still point at the demo server\'s copy, so they show it for now and will stop showing it if that server moves.',
				'Animation Addons: %d files of the imported template could not be downloaded from the demo server. The pages still point at the demo server\'s copies, so they show them for now and will stop showing them if that server moves.',
				count( $failed ),
				'animation-addons-for-elementor'
			) ),
			count( $failed )
		);

		$hint = $blocked
			? esc_html__( 'This site blocks outgoing HTTP requests (WP_HTTP_BLOCK_EXTERNAL). Add the demo host to WP_ACCESSIBLE_HOSTS in wp-config.php and import again.', 'animation-addons-for-elementor' )
			: esc_html__( 'Check that the demo host is reachable from this server, then import the template again: files already in your media library are kept, the missing ones are retried.', 'animation-addons-for-elementor' );

		\Wealcoder\AnimationAddons\Admin\Notices\Notices::instance()->add( array(
			'notice_id'   => 'aaeaddon_import_report_' . (int) $report['time'],
			'type'        => 'warning',
			'dismissible' => true,
			'capability'  => 'manage_options',
			'message'     => sprintf(
				'<p><strong>%1$s</strong></p><p>%2$s</p><details><summary>%3$s</summary><ul style="margin-left:1.5em;list-style:disc">%4$s</ul></details>',
				$intro,
				$hint,
				esc_html__( 'Show the files', 'animation-addons-for-elementor' ),
				$items
			),
		) );
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
	public function save_wp_navigation_import_mapping($post_id, $original_id, $postdata, $data)
	{

		if (empty($postdata['post_content'])) {
			return;
		}

		if ($postdata['post_type'] !== 'wp_navigation') {

			/*
			 * Save the post ID that has navigation block in transient.
			 */
			if (! empty($postdata['post_content']) && strpos($postdata['post_content'], '<!-- wp:navigation') !== false) {
				// Keep track of POST ID that has navigation block.
				$wcfio_post_nav_block = get_transient('aaeaddon_import_posts_with_nav_block');

				if (empty($wcfio_post_nav_block)) {
					$wcfio_post_nav_block = [];
				}

				$wcfio_post_nav_block[] = $post_id;

				set_transient('aaeaddon_import_posts_with_nav_block', $wcfio_post_nav_block, HOUR_IN_SECONDS);
			}
		} else {

			/*
			 * Save the `wp_navigation` post type mapping of the original menu ID and the new menu ID
			 * in transient.
			 */
			$wcfio_menu_mapping = get_transient('aaeaddon_import_menu_mapping');

			if (empty($wcfio_menu_mapping)) {
				$wcfio_menu_mapping = [];
			}

			// Let's save the mapping of the original menu ID and the new menu ID.
			$wcfio_menu_mapping[] = [
				'original_menu_id' => $original_id,
				'new_menu_id'      => $post_id,
			];

			set_transient('aaeaddon_import_menu_mapping', $wcfio_menu_mapping, HOUR_IN_SECONDS);
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
	public function fix_imported_wp_navigation()
	{

		// Get the `wp_navigation` import mapping.
		$nav_import_mapping = get_transient('aaeaddon_import_menu_mapping');

		// Get the post IDs that needs to be updated.
		$posts_nav_block = get_transient('aaeaddon_import_posts_with_nav_block');

		if (empty($nav_import_mapping) || empty($posts_nav_block)) {
			return;
		}

		$replace_pairs = [];

		foreach ($nav_import_mapping as $mapping) {
			$replace_pairs['<!-- wp:navigation {"ref":' . $mapping['original_menu_id'] . '} /-->'] = '<!-- wp:navigation {"ref":' . $mapping['new_menu_id'] . '} /-->';
		}

		// Loop through each the posts that needs to be updated.
		foreach ($posts_nav_block as $post_id) {
			$post_nav_block = get_post($post_id);

			if (empty($post_nav_block) || empty($post_nav_block->post_content)) {
				return;
			}

			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => strtr($post_nav_block->post_content, $replace_pairs),
				]
			);
		}
	}

	/**
	 * Update imported terms count.
	 */
	private function update_terms_count()
	{

		foreach ($this->imported_terms as $tax => $terms) {
			wp_update_term_count_now($terms, $tax);
		}
	}
}
