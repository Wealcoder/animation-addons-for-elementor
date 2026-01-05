<?php

namespace WCF_ADDONS\Admin;

use Elementor\Modules\ElementManager\Options;
use Elementor\Plugin;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

class WCF_Admin_Init
{


	use \WCF_ADDONS\WCF_Extension_Widgets_Trait;

	/**
	 * Parent Menu Page Slug
	 */
	const MENU_PAGE_SLUG = 'wcf_addons_page';

	/**
	 * Menu capability
	 */
	const MENU_CAPABILITY = 'manage_options';

	/**
	 * [$parent_menu_hook] Parent Menu Hook
	 *
	 * @var string
	 */
	static $parent_menu_hook = '';

	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;
	private $plugin_file = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return [_Admin_Init]
	 */
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		$this->plugin_file = WP_PLUGIN_DIR . '/animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php';
		$this->remove_all_notices();
		$this->include();
		$this->init();
	}

	function admin_classes($classes)
	{
		// Get the current admin screen object
		$screen = get_current_screen();

		// Ensure $classes is a string
		if (! is_string($classes)) {
			$classes = '';
		}

		// Check if we are on the correct page
		if ($screen && $screen->id === 'animation-addon_page_wcf_addons_settings') {
			$classes .= ' wcf-anim2024';
		}

		return $classes;
	}


	/**
	 * [init] Assets Initializes
	 *
	 * @return [void]
	 */
	public function init()
	{

		add_action('admin_menu', array($this, 'add_menu'), 25);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_ajax_aae_save_dynamic_settings', array($this, 'save_dynamic_settings'));
		add_action('wp_ajax_aae_get_dynamic_settings', array($this, 'get_dynamic_settings'));
		add_action('wp_ajax_save_settings_with_ajax', array($this, 'save_settings'));
		add_action('wp_ajax_wcf_dashboard_notice_store', array($this, 'notice_store'));
		add_action('wp_ajax_wcf_get_changelog_data', array($this, 'get_changelog'));
		add_action('wp_ajax_wcf_get_notice_data', array($this, 'get_notice'));
		add_action('wp_ajax_save_settings_with_ajax_dashboard', array($this, 'save_settings_dashboard'));

		add_action('wp_ajax_save_smooth_scroller_settings', array($this, 'save_smooth_scroller_settings'));

		add_filter('admin_body_class', array($this, 'admin_classes'), 100);
		add_filter('wcf_addons_dashboard_config', array($this, 'dashboard_db_widgets_config'), 11);
		add_filter('wcf_addons_dashboard_config', array($this, 'dashboard_db_extnsions_config'), 10);
		add_filter('wcf_addons_dashboard_config', array($this, 'dashboard_integrations_config'), 10);

		add_action('admin_footer', array($this, 'admin_footer'));
		add_action('elementor/core/files/clear_cache', function () {
			delete_transient('wcf_menu_42_data');
		});

		//add_action('wp_dashboard_setup', [$this, 'dashboard_widget'], 999);
	}

	public function dashboard_widget()
	{


		if (file_exists($this->plugin_file)) {
			return;
		}

		wp_add_dashboard_widget(
			'aae_dashboard_widget',
			'Animation Addons Overview',
			[$this, 'aae_render_dashboard_widget']
		);


		global $wp_meta_boxes;

		// Check that our widget actually exists before reordering
		if (isset($wp_meta_boxes['dashboard']['normal']['core']['aae_dashboard_banner'])) {
			// Get current dashboard widgets
			$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

			// Backup our widget
			$aae_widget_backup = [
				'aae_dashboard_banner' => $normal_dashboard['aae_dashboard_banner']
			];

			// Remove from bottom and merge on top
			unset($normal_dashboard['aae_dashboard_banner']);
			$sorted_dashboard = array_merge($aae_widget_backup, $normal_dashboard);

			// Assign back
			$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
		}
	}

	function aae_render_dashboard_widget()
	{
		$view = __DIR__ . '/banner/ads.php';
		require_once $view;
	}


	/**
	 * Summary of elementor_disabled_elements
	 *
	 * @return void
	 */
	public function disable_widgets_by_element_manager()
	{

		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option('wcf_save_widgets');
		$pattern         = '/^wcf--\w+/';

		if (is_array($disable_widgets) && is_array($saved_widgets)) {

			foreach ($disable_widgets as $item) {

				if (preg_match($pattern, $item)) {

					$toberemove = trim($item, 'wcf--');
					if (isset($saved_widgets[$toberemove])) {
						unset($saved_widgets[$toberemove]);
					}
				}
			}

			update_option('wcf_save_widgets', $saved_widgets);
		}
	}

	public function sync_widgets_by_element_manager()
	{
		$namefixs        = array(
			'post-paginate'      => 'wcf--blog--post--paginate',
			'post-social-share'  => 'wcf--blog--post--social-share',
			'post-title'         => 'wcf--blog--post--title',
			'search-form'        => 'wcf--blog--search--form',
			'search-query'       => 'wcf--blog--search--query',
			'text-hover-image'   => 'wcf--t-h-image',
			'post-meta-info'     => 'wcf--blog--post--meta-info',
			'post-excerpt'       => 'wcf--blog--post--excerpt',
			'post-feature-image' => 'wcf--theme-post-image',
			'social-icons'       => 'social-icons',
		);
		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option('wcf_save_widgets');

		if (is_array($disable_widgets) && is_array($saved_widgets)) {

			foreach ($saved_widgets as $key => $state) {

				$index = false;
				$index = array_search('wcf--' . $key, $disable_widgets); // Find the index of the element
				if ($index !== false) {
					unset($disable_widgets[$index]); // Remove element if found
				}

				$index = array_search('wcf--blog--' . $key, $disable_widgets); // Find the index of the element
				if ($index !== false) {
					unset($disable_widgets[$index]); // Remove element if found
				}

				if (array_key_exists($key, $namefixs)) {

					$slug  = $namefixs[$key];
					$index = array_search($slug, $disable_widgets); // Find the index of the element
					if ($index !== false) {
						unset($disable_widgets[$index]); // Remove element if found
					}
				}
			}

			array_unshift($disable_widgets);

			Options::update_disabled_elements($disable_widgets);
		}
	}
	/**
	 * merge database saved data with dasboard widgets config
	 *
	 * @return [void]
	 */
	public function dashboard_db_widgets_config($configs)
	{
		$wgt           = get_option('wcf_save_widgets');
		$saved_widgets = is_array($wgt) ? array_keys($wgt) : array();
		$widgets       = $configs['widgets'];
		wcf_get_db_updated_config($widgets, $saved_widgets);
		$configs['widgets'] = $widgets;
		return $configs;
	}

	/**
	 * merge database saved data with dasboard ext config
	 *
	 * @return [void]
	 */
	public function dashboard_db_extnsions_config($configs)
	{
		$ext        = get_option('wcf_save_extensions');
		$saved_ext  = is_array($ext) ? array_keys($ext) : array();
		$extensions = $configs['extensions'];
		wcf_get_db_updated_config($extensions, $saved_ext);
		$configs['extensions'] = $extensions;
		return $configs;
	}

	/**
	 * [include] Load Necessary file
	 *
	 * @return [void]
	 */
	public function include()
	{
		if (! class_exists('\WP_Importer')) {
			require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}
		require_once 'row-actions.php';
		require_once 'plugin-installer.php';
		require_once 'base/Helpers.php';
		require_once 'base/Downloader.php';
		require_once 'base/WPImporterLogger.php';
		require_once 'base/WPImporterLoggerCLI.php';
		require_once 'base/WXRImporter.php';
		require_once 'base/WXRImportInfo.php';
		require_once 'aae-importer.php';
		require_once 'Logger.php';
		require_once 'Importer.php';
		require_once 'st-init.php';
		require_once 'template-importer.php';
		$oneimport = \WCF_ADDONS\Admin\Base\OneClickImport::get_instance();
	}



	/**
	 * [add_menu] Admin Menu
	 */
	public function add_menu()
	{
		if (! (current_user_can('manage_options'))) {
			return;
		}
		self::$parent_menu_hook = add_menu_page(
			esc_html__('Animation Addon', 'animation-addons-for-elementor'),
			esc_html__('Animation Addon', 'animation-addons-for-elementor'),
			self::MENU_CAPABILITY,
			self::MENU_PAGE_SLUG,
			'',
			WCF_ADDONS_URL . '/assets/images/wcf.png',
			8
		);

		add_submenu_page(
			self::MENU_PAGE_SLUG,
			esc_html__('Settings', 'animation-addons-for-elementor'),
			esc_html__('Settings', 'animation-addons-for-elementor'),
			'manage_options',
			'wcf_addons_settings',
			array($this, 'plugin_dashboard_entry_page')
		);

		// Remove Parent Submenu
		remove_submenu_page(self::MENU_PAGE_SLUG, self::MENU_PAGE_SLUG);
	}

	/**
	 * [enqueue_scripts] Add Scripts Base Menu Slug
	 *
	 * @param  [string] $hook
	 *
	 * @return [void]
	 */
	public function enqueue_scripts($hook)
	{
		$total_extensions = $total_widgets = 0;

		if ($hook == 'animation-addon_page_wcf_addons_settings') {
			// sync element manager
			$this->disable_widgets_by_element_manager();
			// CSS
			wp_enqueue_style(
				'wcf-admin', // Handle for the stylesheet
				WCF_ADDONS_URL . 'assets/build/modules/dashboard/index.css',
				array(), // Dependencies (none in this case)
				time()
			);

			wp_enqueue_script('wcf-admin', WCF_ADDONS_URL . 'assets/build/modules/dashboard/index.js', array('react', 'react-dom', 'wp-element', 'wp-i18n'), time(), true);
			wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['extensions'], $total_extensions);
			wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['widgets'], $total_widgets);

			$widgets       = get_option('wcf_save_widgets');
			$saved_widgets = is_array($widgets) ? array_keys($widgets) : array();

			wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['widgets'], $saved_widgets, $foundKeys, $awidgets);

			$extensions       = get_option('wcf_save_extensions');
			$saved_extensions = is_array($extensions) ? array_keys($extensions) : array();

			wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['extensions'], $saved_extensions, $foundext, $activeext);

			$active_widgets = self::get_widgets();
			$active_ext     = self::get_extensions();
			$font_settings  = wp_unslash(get_option('wcf_custom_font_setting'));

			$localize_data = array(
				'ajaxurl'             => admin_url('admin-ajax.php'),
				'nonce'               => wp_create_nonce('wcf_admin_nonce'),
				'addons_config'       => apply_filters('wcf_addons_dashboard_config', $GLOBALS['wcf_addons_config']),
				'adminURL'            => admin_url(),
				'smoothScroller'      => json_decode(get_option('wcf_smooth_scroller')),
				'cf_settings'         => is_string($font_settings) ? json_decode($font_settings) : array(),
				'extensions'          => array(
					'total'  => $total_extensions,
					'active' => is_array($active_ext) ? count($active_ext) : 0,
				),
				'widgets'             => array(
					'total'  => $total_widgets,
					'active' => is_array($active_widgets) ? count($active_widgets) : 0,
				),
				'global_settings_url' => $this->get_elementor_active_edit_url(),
				'theme_builder_url'   => admin_url('edit.php?post_type=wcf-addons-template'),
				'user_role'           => wcfaddon_get_current_user_roles(),
				'version'             => WCF_ADDONS_VERSION,
				'st_template_domain'  => WCF_TEMPLATE_STARTER_BASE_URL,
				'home_url' => add_query_arg(['aae-cache' => 1], home_url('/')),
				'template_menu' => $this->get_template_menu_data(),
				'hero' => file_exists($this->plugin_file) ? WCF_ADDONS_URL . 'assets/images/hero-banner.jpg' : 'no',
				'hero_offer' => WCF_ADDONS_URL . 'assets/video/cyber-sale.mp4',

			);
			wp_localize_script('wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data);
		}
	}

	public function get_template_menu_data()
	{
		$transient_key = 'wcf_menu_42_data';
		$cached_data   = get_transient($transient_key);

		// ✅ Return cached data if available
		if ($cached_data !== false) {
			return $cached_data;
		}

		$url      = "https://www.themecrowdy.com/wp-json/wcf/v1/menu/42";
		$response = wp_remote_get($url, [
			'timeout' => 15,
			'sslverify' => false,
			'headers' => [
				'Accept' => 'application/json'
			]
		]);

		// ✅ Validate response
		if (is_wp_error($response)) {
			return [];
		}

		$status_code = wp_remote_retrieve_response_code($response);
		if ($status_code !== 200) {
			return [];
		}

		$body = wp_remote_retrieve_body($response);
		if (empty($body)) {

			return [];
		}

		// ✅ Decode JSON safely
		$data = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {

			return [];
		}

		// ✅ Ensure expected structure exists
		if (! isset($data['items']) || ! is_array($data['items'])) {

			return [];
		}

		// ✅ Cache valid data for 1 hour
		set_transient($transient_key, $data['items'], HOUR_IN_SECONDS);

		return $data['items'];
	}


	function dashboard_integrations_config($configs)
	{

		if (! isset($configs['integrations']['plugins']['elements'])) {
			return $configs;
		}

		$action    = '';
		$data_base = '';
		foreach ($configs['integrations']['plugins']['elements'] as &$plugin) {

			if (wcf_addons_get_local_plugin_data($plugin['basename']) === false) {
				$action    = 'Download';
				$data_base = $plugin['download_url'];
			} elseif (is_plugin_active($plugin['basename'])) {
				$action = 'Activated';
			} else {
				$action    = 'Active';
				$data_base = $plugin['basename'];
			}
			$plugin['action']    = $action;
			$plugin['data_base'] = $data_base;
		}

		return $configs;
	}

	public function get_elementor_active_edit_url()
	{

		if (defined('ELEMENTOR_VERSION') && class_exists('\Elementor\Plugin')) {
			// Fetch the active kit ID from Elementor settings
			$active_kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();

			$elementor_edit_url = add_query_arg(
				array(
					'post'            => $active_kit_id,
					'action'          => 'elementor',
					'active-document' => $active_kit_id,
				),
				admin_url('post.php')
			);

			return $elementor_edit_url;
		}

		return false;
	}

	public function admin_footer()
	{
		if (! is_admin()) {
			return;
		}
		// Get the current admin screen
		$screen = get_current_screen();

		// Check if we are on the correct admin page
		if ($screen && $screen->id === 'animation-addon_page_wcf_addons_settings') {
			echo '<div id="wcf-admin-toast"></div>';
		}
	}

	public function plugin_dashboard_entry_page()
	{
?>
		<div class="wrap wcf-admin-wrapper" id="wcf-admin-ds-cr-js"></div>
<?php
	}

	/**
	 * [remove_all_notices] remove addmin notices
	 *
	 * @return [void]
	 */
	public function remove_all_notices()
	{
		add_action(
			'in_admin_header',
			function () {
				$screen = get_current_screen();
				if ($screen && 'animation-addon_page_wcf_addons_settings' === $screen->id) {
					remove_all_actions('admin_notices');
					remove_all_actions('all_admin_notices');
					remove_all_actions('user_admin_notices');
					remove_all_actions('network_admin_notices');
				}
			},
			1000
		);
	}

	/**
	 * Save Settings
	 * Save EA settings data through ajax request
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function save_settings()
	{

	
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			return;
		}

		$actives       = $foundkeys = array();
		$option_name   = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';
		$sanitize_data = sanitize_text_field(wp_unslash($_POST['fields']));
		$settings      = json_decode($sanitize_data, true);
		wcf_get_nested_active_config_keys($settings, $found, $actives);
		wcf_get_nested_config_keys($settings, $foundkeys, $updatedSettings);

		update_option('wcf_addons_setup_wizard', 'complete');
		// update new settings
		if (! empty($option_name)) {

			$updated = update_option($option_name, $updatedSettings);

			if ($option_name == 'wcf_save_widgets') {
				$this->sync_widgets_by_element_manager();
				update_option('wcf_widget_dashboardv2', true);
			} else {
				update_option('wcf_extension_dashboardv2', true);
			}

			$return_message = array(
				'status' => $updated,
				'total'  => is_array($actives) ? count($actives) : 0,

			);
			wp_send_json($return_message);
		}

		wp_send_json(esc_html__('Option name not found!', 'animation-addons-for-elementor'));
	}

	public function get_dynamic_settings()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('You are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (empty($_POST['setting_name'])) {
			wp_send_json_error(esc_html__('Missing setting name.', 'animation-addons-for-elementor'));
		}

		$setting_name = sanitize_text_field(wp_unslash($_POST['setting_name']));
		$settings     = get_option($setting_name);

		// If the option was stored as JSON, decode it
		if (is_string($settings) && $this->is_json($settings)) {
			$settings = json_decode($settings, true);
		}

		wp_send_json(
			array(
				'settings' => $settings,
			)
		);
	}

	/**
	 * Check if a string is a valid JSON.
	 */
	private function is_json($string)
	{
		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}

	public function save_dynamic_settings()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['form_fields'])) {
			return;
		}

		if (! isset($_POST['setting_name'])) {
			return;
		}

		$form_data    = sanitize_text_field(wp_unslash($_POST['form_fields']));
		$setting_name = sanitize_text_field(wp_unslash($_POST['setting_name']));
		update_option($setting_name, $form_data);

		$return_message = array(
			'message' => 'Settings Updated',
		);
		wp_send_json($return_message);
	}

	public function notice_store()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['notice'])) {
			return;
		}

		$sanitize_data = sanitize_text_field(wp_unslash($_POST['notice']));
		update_option('wcf_notice_data', $sanitize_data);

		$return_message = array(
			'message' => 'Notice Updated',
		);
		wp_send_json($return_message);
	}

	public function get_changelog()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		$transient      = get_transient('wcf_changelog_notice_cache3');
		$return_message = array(
			'changelog' => '',
		);
		// Yep!  Just return it and we're done.
		if ($transient !== false) {
			$return_message['changelog'] = $transient;
		} else {
			$url                         = 'https://store.wealcoder.com/wp-json/userdata/v1/changelog?p=768';
			$args                        = array(
				'timeout'   => 60,
				'sslverify' => false,
				'headers'   => array(
					'Accept' => 'application/json',
				),
			);
			$out                         = wp_remote_get($url, $args);
			$body                        = wp_remote_retrieve_body($out);
			$decode_data                 = json_decode($body);
			$return_message['changelog'] = $decode_data;
			set_transient('wcf_changelog_notice_cache3', $decode_data, 12 * HOUR_IN_SECONDS);
		}

		wp_send_json($return_message);
	}

	public function get_notice()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		$return_message = array(
			'notice' => json_decode(get_option('wcf_notice_data')),
		);
		wp_send_json($return_message);
	}

	public function save_settings_dashboard()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			return;
		}

		$actives       = array();
		$option_name   = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';
		$sanitize_data = sanitize_text_field(wp_unslash($_POST['fields']));
		$settings      = json_decode($sanitize_data, true);
		$actives       = get_option('wcf_save_widgets');

		if (is_array($actives)) {
			foreach ($settings as $slug => $item) {

				if (array_key_exists($slug, $actives) && ! $item['is_active']) {
					unset($actives[$slug]);
				}

				if (! array_key_exists($slug, $actives) && $item['is_active']) {
					$actives[$slug] = true;
				}
			}
		}
		// update new settings
		if (! empty($option_name)) {

			$updated = update_option($option_name, $actives);

			if ($option_name == 'wcf_save_widgets') {
				$this->sync_widgets_by_element_manager();
			}
			$elements = get_option($option_name);

			$return_message = array(
				'status' => $updated,
				'total'  => is_array($elements) ? count($elements) : 0,
			);
			wp_send_json($return_message);
		}
		wp_send_json(esc_html__('Option name not found!', 'animation-addons-for-elementor'));
	}

	/**
	 * Save smooth scroller Settings
	 * settings data through ajax request
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function save_smooth_scroller_settings()
	{

		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['smooth'])) {
			return;
		}

		$settings = array(
			'smooth' => sanitize_text_field(wp_unslash($_POST['smooth'])),
		);

		if (isset($_POST['mobile'])) {
			$settings['mobile'] = sanitize_text_field(wp_unslash($_POST['mobile']));
		}
		if (isset($_POST['disableMode'])) {
			$settings['disableMode'] = sanitize_text_field(wp_unslash($_POST['disableMode']));
		}
		if (isset($_POST['media'])) {
			$settings['media'] = sanitize_text_field(wp_unslash($_POST['media']));
		}

		$option = wp_json_encode($settings);

		// update new settings
		if (! empty($_POST['smooth'])) {

			update_option('wcf_smooth_scroller', $option);
			wp_send_json($option);
		}

		wp_send_json(esc_html__('Option name not found!', 'animation-addons-for-elementor'));
	}
}

WCF_Admin_Init::instance();
