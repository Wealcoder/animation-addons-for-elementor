<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use Elementor\Modules\ElementManager\Options;
use Elementor\Plugin;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

class WCF_Admin_Init
{


	use \WCF_ADDONS\WCF_Extension_Widgets_Trait;

	/**
	 * Option names the dashboard AJAX endpoints may read and write.
	 *
	 * The endpoints take the option name from the request, so without this an
	 * administrator-level request could point them at any option at all —
	 * siteurl, admin_email, a role definition. Whitelisting keeps them pointed
	 * at the plugin's own settings.
	 *
	 * EVERY name the JS bundles send has to be here or that screen silently
	 * stops loading and saving. Current senders:
	 *   free  save_settings_with_ajax  -> wcf_save_widgets, wcf_save_extensions
	 *   free  aae_*_dynamic_settings   -> aae_mailchimp_api, aae_tiktok_api_advanced_settings,
	 *                                     aae_weather_api_advanced_settings,
	 *                                     aae_youtube_video_advanced_settings
	 *   Pro   aae_*_dynamic_settings   -> aae_anim_builder_settings, wcf_addon_sl_license_key
	 * Pro ships its own screens against these same free endpoints, so its
	 * option names belong here too even though nothing in this plugin reads them.
	 *
	 * @since 2.7.3
	 */
	private static $allowed_option_names = array(
		'wcf_save_widgets',
		'wcf_save_extensions',
		'wcf_custom_font_setting',
		'wcf_smooth_scroller',
		'wcf_notice_data',
		'wcf_addons_setup_wizard',
		'aae_mailchimp_api',
		'aae_tiktok_api_advanced_settings',
		'aae_weather_api_advanced_settings',
		'aae_youtube_video_advanced_settings',
		'aae_anim_builder_settings',
		'wcf_addon_sl_license_key',
	);

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
		if ($screen && strpos($screen->id, '_page_wcf_addons_settings') !== false) {
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
		add_action('wp_ajax_aae_complete_setup_wizard', array($this, 'complete_setup_wizard'));
		add_action('wp_ajax_aae_wizard_subscribe', array($this, 'wizard_subscribe'));
		add_action('wp_ajax_wcf_dashboard_notice_store', array($this, 'notice_store'));
		add_action('wp_ajax_wcf_get_changelog_data', array($this, 'get_changelog'));
		add_action('wp_ajax_wcf_get_notice_data', array($this, 'get_notice'));
		add_action('wp_ajax_save_settings_with_ajax_dashboard', array($this, 'save_settings_dashboard'));

		add_action('wp_ajax_save_smooth_scroller_settings', array($this, 'save_smooth_scroller_settings'));

		// Prune AAE widgets only when Elementor's Element Manager list actually changes.
		add_action('add_option_elementor_disabled_elements', array($this, 'disable_widgets_by_element_manager'));
		add_action('update_option_elementor_disabled_elements', array($this, 'disable_widgets_by_element_manager'));

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
	 * Saved widget key => Elementor element manager widget name, for the
	 * widgets whose element name is not simply 'wcf--' . key.
	 */
	const ELEMENT_MANAGER_NAME_FIXES = array(
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

	/**
	 * Summary of elementor_disabled_elements
	 *
	 * @return void
	 */
	public function disable_widgets_by_element_manager()
	{

		if (! class_exists('\Elementor\Modules\ElementManager\Options')) {
			return;
		}

		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option('wcf_save_widgets');

		if (is_array($disable_widgets) && is_array($saved_widgets)) {

			foreach ($disable_widgets as $item) {

				$key = $this->element_name_to_widget_key($item);

				if ($key !== null && isset($saved_widgets[$key])) {
					unset($saved_widgets[$key]);
				}
			}

			update_option('wcf_save_widgets', $saved_widgets);
		}
	}

	public function sync_widgets_by_element_manager()
	{

		if (! class_exists('\Elementor\Modules\ElementManager\Options')) {
			return;
		}

		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option('wcf_save_widgets');

		if (is_array($disable_widgets) && is_array($saved_widgets)) {

			foreach ($disable_widgets as $index => $item) {

				$key = $this->element_name_to_widget_key($item);

				// Widget re-enabled in the AAE dashboard: lift the
				// Element Manager block so it can register again.
				if ($key !== null && isset($saved_widgets[$key])) {
					unset($disable_widgets[$index]);
				}
			}

			Options::update_disabled_elements(array_values($disable_widgets));
		}
	}

	/**
	 * Resolve an Element Manager element name to its AAE dashboard widget key.
	 * Primary source is the live map filled while widgets register; the
	 * name-fix table and prefix strips cover widgets registered outside
	 * register_widgets() (theme builder blog widgets).
	 *
	 * @param string $element_name Widget name as registered with Elementor.
	 * @return string|null Dashboard widget key, or null for non-AAE elements.
	 */
	private function element_name_to_widget_key($element_name)
	{
		static $element_to_key = null;

		if ($element_to_key === null) {

			// Force widget registration so register_widgets() fills the
			// element-name => key map. Widgets the Element Manager just
			// disabled are still constructed (Elementor only blocks them
			// from its registry), so they land in the map too.
			if (did_action('elementor/loaded')) {
				Plugin::instance()->widgets_manager->get_widget_types();
			}

			$element_to_key  = \WCF_ADDONS\Plugin::$widget_element_keys;
			$element_to_key += array_flip(self::ELEMENT_MANAGER_NAME_FIXES);
		}

		if (isset($element_to_key[$element_name])) {
			return $element_to_key[$element_name];
		}

		foreach (array('wcf--blog--', 'wcf--', 'aae--') as $prefix) {
			if (strpos($element_name, $prefix) === 0) {
				return substr($element_name, strlen($prefix));
			}
		}

		return null;
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
			WCF_ADDONS_URL . 'assets/images/wcf.png',
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

		$screen = get_current_screen();
		if ( ! $screen || strpos($screen->id, '_page_wcf_addons_settings') === false) {
			return;
		}

		// Load config once
		$config = wcf_get_config();

		// CSS
		wp_enqueue_style(
			'wcf-admin',
			WCF_ADDONS_URL . 'assets/build/modules/dashboard/index.css',
			array(),
			time()
		);

		wp_enqueue_script(
			'wcf-admin',
			WCF_ADDONS_URL . 'assets/build/modules/dashboard/index.js',
			array('react', 'react-dom', 'wp-element', 'wp-i18n'),
			time(),
			true
		);

		// Count widgets/extensions
		wcf_get_total_config_elements_by_key($config['extensions'], $total_extensions);
		wcf_get_total_config_elements_by_key($config['widgets'], $total_widgets);

		// Widgets
		$widgets       = get_option('wcf_save_widgets');
		$saved_widgets = is_array($widgets) ? array_keys($widgets) : array();

		wcf_get_search_active_keys($config['widgets'], $saved_widgets, $foundKeys, $awidgets);

		// Extensions
		$extensions       = get_option('wcf_save_extensions');
		$saved_extensions = is_array($extensions) ? array_keys($extensions) : array();

		wcf_get_search_active_keys($config['extensions'], $saved_extensions, $foundext, $activeext);

		$active_widgets = self::get_widgets();
		$active_ext     = self::get_extensions();

		$font_settings = wp_unslash(get_option('wcf_custom_font_setting'));

		$localize_data = array(
			'ajaxurl'        => admin_url('admin-ajax.php'),
			'isSettingsPage' => true,
			'nonce'          => wp_create_nonce('wcf_admin_nonce'),

			'addons_config'  => apply_filters('wcf_addons_dashboard_config', $config),  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			'adminURL'       => admin_url(),
			'smoothScroller' => json_decode(get_option('wcf_smooth_scroller')),

			// When MotionKit is connected and its ScrollSmoother is switched on
			// site-wide, AAE Pro stands its own smoother down (MotionKit has
			// priority). Surface that here so the Scroll Smoother panel can tell the
			// user why their setting is inactive instead of looking broken.
			'motionkitSmoother' => array(
				'active' => (
					defined('MOTIONKIT_VERSION')
					&& ! empty(get_option('motionkit_access_token'))
					&& class_exists('\MotionKit\Frontend\ScrollSmoother')
					&& method_exists('\MotionKit\Frontend\ScrollSmoother', 'is_enabled_globally')
					&& \MotionKit\Frontend\ScrollSmoother::is_enabled_globally()
				),
			),

			'cf_settings' => is_string($font_settings)
				? json_decode($font_settings)
				: array(),

			'extensions' => array(
				'total'  => $total_extensions,
				'active' => is_array($active_ext) ? count($active_ext) : 0,
			),

			'widgets' => array(
				'total'  => $total_widgets,
				'active' => is_array($active_widgets) ? count($active_widgets) : 0,
			),

			/*
			 * Does this site's CONTENT use v3 widgets, regardless of what the
			 * toggles say? `_elementor_data LIKE '%"widgetType":"wcf--%'` plus
			 * the Kit's chrome keys, cached an hour (aae_v3_usage transient).
			 *
			 * The dashboard hides the era a site does not use, and the active
			 * COUNT is not enough to decide that: a site can hold 34 pages
			 * built from wcf--* widgets while `wcf_save_widgets` is empty —
			 * that is the exact shape maybe_enable_used_v3_widgets() exists to
			 * heal, and it deliberately bails once the option has been written
			 * by hand. Hiding V3 from that user would take away the only screen
			 * that could bring their pages back.
			 *
			 * Same ratchet as `legacy_v3` (Rule 5 in CLAUDE.md): evidence of v3
			 * can only ever switch V3 back ON.
			 */
			'v3_in_use' => class_exists('\WCF_ADDONS\AnimationSettings\Animation_Settings')
				&& \WCF_ADDONS\AnimationSettings\Animation_Settings::has_v3_usage(),

			'global_settings_url' => $this->get_elementor_active_edit_url(),
			'theme_builder_url'   => admin_url('edit.php?post_type=wcf-addons-template'),
			'user_role'           => wcfaddon_get_current_user_roles(),

			'version'            => WCF_ADDONS_VERSION,
			'st_template_domain' => WCF_TEMPLATE_STARTER_BASE_URL,

			'home_url' => add_query_arg(['aae-cache' => 1], home_url('/')),

			'template_menu' => $this->get_template_menu_data(),

			'hero'       => file_exists($this->plugin_file)
				? WCF_ADDONS_URL . 'assets/images/hero-banner.jpg'
				: 'no',

			'hero_offer' => WCF_ADDONS_URL . 'assets/video/cyber-sale.mp4',

			// Animation Settings screen. Shipped in the initial payload rather
			// than fetched, so the panel paints filled in on first open.
			'animation_settings' => array(
				'settings'      => \WCF_ADDONS\AnimationSettings\Animation_Settings::get(),
				'schema'        => \WCF_ADDONS\AnimationSettings\Animation_Settings::schema_for_ui(),
				'global_colors' => \WCF_ADDONS\AnimationSettings\Animation_Settings::global_colors(),
				'has_pro'       => \WCF_ADDONS\AnimationSettings\Animation_Settings::has_pro(),
			),

			/*
			 * Performance screen. The SCREEN ships in free; the settings store
			 * and the delivery pipeline behind it are Pro
			 * (pro/inc/Performance/), so the payload arrives through a filter
			 * Pro answers rather than a direct class reference.
			 *
			 * An empty array is the correct free-only value, not a missing one:
			 * the React page reads it as "Pro is not here" and renders the
			 * locked upsell state.
			 */
			'performance' => apply_filters('aae/performance/dashboard_payload', array()),
		);

		wp_localize_script('wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data);

		// WordPress.org translations take priority, bundled translations in plugin's languages/ folder serve as fallback
		wp_set_script_translations('wcf-admin', 'animation-addons-for-elementor', WCF_ADDONS_PATH . 'languages');

		// Support user-level locale (when user sets language in their profile)
		$user_locale = get_user_locale();
		$site_locale = get_locale();

		if ($user_locale !== $site_locale && $user_locale !== 'en_US') {
			$md5    = md5('assets/build/modules/dashboard/index.js');
			$json_file = WCF_ADDONS_PATH . "languages/animation-addons-for-elementor-{$user_locale}-{$md5}.json";

			if (file_exists($json_file)) {
				$json    = file_get_contents($json_file);
				$decoded = json_decode($json, true);

				if ($decoded && isset($decoded['locale_data']['messages'])) {
					$locale_data = wp_json_encode($decoded['locale_data']['messages']);
					wp_add_inline_script(
						'wcf-admin',
						"wp.i18n.setLocaleData({$locale_data}, 'animation-addons-for-elementor');",
						'before'
					);
				}
			}
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
		if ($screen && strpos($screen->id, '_page_wcf_addons_settings') !== false) {
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
				if ($screen && strpos($screen->id, '_page_wcf_addons_settings') !== false) {
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

		// Only ever write one of the plugin's own options.
		if (! empty($option_name) && ! in_array($option_name, self::$allowed_option_names, true)) {
			wp_send_json_error(esc_html__('Invalid option name.', 'animation-addons-for-elementor'));
		}

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

	/**
	 * Mark the setup wizard finished.
	 *
	 * save_settings() used to be the only thing that wrote this flag, as a side
	 * effect of persisting wcf_save_widgets. The V4 wizard saves through
	 * class-atomic.php's own aae_save_atomic_* handlers instead and never calls
	 * save_settings(), so without this endpoint the flag is never written and
	 * class-plugin.php's admin_init redirect sends the user back into the wizard
	 * on every page load — forever.
	 *
	 * Kept separate rather than folded into the atomic save handlers: "the
	 * wizard is done" is a different fact from "these widgets are on", and the
	 * dashboard's own Save button must never mark a wizard complete.
	 */
	public function complete_setup_wizard()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		update_option('wcf_addons_setup_wizard', 'complete');

		wp_send_json_success(array('status' => 'complete'));
	}

	/**
	 * Lead-capture relay. Holds the Brevo API key and list id server-side, so
	 * neither ever ships with the plugin.
	 *
	 * Its schema (GET https://animation-addons.com/wp-json/leads/v1) accepts
	 * `email` (required) plus optional firstName / lastName / company / phone /
	 * source / site — which is exactly what wizard_subscribe() sends. Anything
	 * added here has to exist there too, or the relay drops it silently.
	 */
	const LEADS_ENDPOINT = 'https://animation-addons.com/wp-json/leads/v1/subscribe';

	/**
	 * Register the site administrator as a product lead.
	 *
	 * NO CREDENTIAL LIVES IN THIS PLUGIN, and that is the whole point. The
	 * relay above owns the Brevo API key and the target list id, and maps these
	 * neutral fields onto Brevo's attributes itself — so there is nothing here
	 * to leak, whether from the JS bundle, the PHP source, or the shipped zip.
	 *
	 * That matters because of what this replaced: the call used to run in the
	 * BROWSER, from WizWidget.jsx, POSTing with an HTTP Basic Auth
	 * username and password written into the component. webpack published them
	 * to assets/build/9479.js — fetchable by anyone from any site running the
	 * plugin — so the key was public and could be used to write to the CRM
	 * directly. Moving that call to PHP would NOT have fixed it: a distributed
	 * plugin ships its source too. Only removing the credential fixes it.
	 *  in released builds; it has to
	 * be rotated regardless of this change.)
	 *
	 * Server-side rather than from the browser, unlike the brevo-configaration
	 * branch's version: the request then survives ad/tracker blockers, the
	 * once-per-site flag is an option instead of per-browser localStorage, and
	 * the fields come from WordPress rather than from a payload the wizard
	 * would have to localise (WCF_ADDONS_ADMIN.user carries no last name,
	 * company or phone, so those keys silently went out empty).
	 *
	 * The client sends NOTHING but a nonce — the address is read from the
	 * current user here, so this cannot be used to push arbitrary addresses.
	 */
	public function wizard_subscribe()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		// Once per SITE. The old guard was localStorage, so the same site
		// re-subscribed from every new browser, and clearing site data re-ran it.
		if ('yes' === get_option('wcf_addons_wizard_subscribed')) {
			wp_send_json_success(array('status' => 'already'));
		}

		$user  = wp_get_current_user();
		$email = sanitize_email($user->user_email);

		if (! is_email($email)) {
			wp_send_json_error(esc_html__('No valid administrator email.', 'animation-addons-for-elementor'));
		}

		$first_name = trim((string) $user->first_name);

		if ('' === $first_name) {
			// Same derivation the JS did: local part, dots to spaces, capitalised.
			$local      = strstr($email, '@', true);
			$first_name = implode(' ', array_map('ucfirst', explode('.', (string) $local)));
		}

		$lead = array(
			'email'     => $email,
			'firstName' => $first_name,
			'lastName'  => trim((string) $user->last_name),
			'source'    => 'animation-addon',
			'site'      => home_url(),
		);

		// Send only what has a value — the relay treats an empty attribute as a
		// write and would blank a field the contact already has.
		$lead = array_filter(
			$lead,
			static function ($value) {
				return '' !== $value && null !== $value;
			}
		);

		/** Swap the relay (e.g. to api.animationaddons.com) without a release. */
		$endpoint = apply_filters(
			'aae/wizard/lead_endpoint',  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			self::LEADS_ENDPOINT
		);

		/** Return [] to opt a site out of lead capture entirely. */
		$lead = apply_filters('aae/wizard/lead_payload', $lead);  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if (empty($endpoint) || empty($lead)) {
			update_option('wcf_addons_wizard_subscribed', 'yes');
			wp_send_json_success(array('status' => 'skipped'));
		}

		$response = wp_remote_post(
			esc_url_raw($endpoint),
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode($lead),
			)
		);

		// Marked done either way, matching the old behaviour: the JS wrote its
		// flag in both the success and the catch branch, so a relay outage never
		// re-queued the request on every wizard visit.
		update_option('wcf_addons_wizard_subscribed', 'yes');

		if (is_wp_error($response)) {
			wp_send_json_success(array('status' => 'failed'));
		}

		wp_send_json_success(
			array(
				'status' => 'sent',
				'code'   => wp_remote_retrieve_response_code($response),
			)
		);
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

		// Only ever read one of the plugin's own options.
		if (! in_array($setting_name, self::$allowed_option_names, true)) {
			wp_send_json_error(esc_html__('Invalid option name.', 'animation-addons-for-elementor'));
		}

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

		// Only ever write one of the plugin's own options.
		if (! in_array($setting_name, self::$allowed_option_names, true)) {
			wp_send_json_error(esc_html__('Invalid option name.', 'animation-addons-for-elementor'));
		}

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

		// Only ever write one of the plugin's own options.
		if (! empty($option_name) && ! in_array($option_name, self::$allowed_option_names, true)) {
			wp_send_json_error(esc_html__('Invalid option name.', 'animation-addons-for-elementor'));
		}

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

		$settings = sanitize_text_field(wp_unslash($_POST['smooth']));
	
		$decode = json_decode($settings);
		$option = wp_json_encode($decode);

		// update new settings
		if (! empty($_POST['smooth'])) {

			update_option('wcf_smooth_scroller', $option);
			wp_send_json($option);
		}

		wp_send_json(esc_html__('Option name not found!', 'animation-addons-for-elementor'));
	}
}

WCF_Admin_Init::instance();
