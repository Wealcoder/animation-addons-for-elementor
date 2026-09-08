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
		add_action('wp_ajax_wcf_dashboard_notice_store', array($this, 'notice_store'));
		add_action('wp_ajax_wcf_get_notice_data', array($this, 'get_notice'));
		add_action('wp_ajax_wcf_request_new_feature', array($this, 'request_new_feature'));
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
		// base/WXRImporter.php below declares `class WXRImporter extends
		// \WP_Importer`, so the core class has to exist before that file is
		// parsed. Core does not autoload it; require_once, used immediately.
		if (! class_exists('\WP_Importer')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
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
		require_once 'atomic-attachment-remap.php';
		require_once 'atomic-kit-import.php';
		require_once 'atomic-v3-switch-off.php';
		require_once 'atomic-image-localize.php';
		\WCF_ADDONS\Admin\Base\Atomic_Attachment_Remap::init();
		\WCF_ADDONS\Admin\Base\Atomic_Kit_Import::init();
		\WCF_ADDONS\Admin\Base\Atomic_Image_Localize::init();
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
			// 81 -- immediately BELOW Settings (80), in the block where plugins
			// belong. It used to be 8, which sits between Posts (5) and Media
			// (10) and pushed this above Media, Pages, Comments, Appearance,
			// Plugins, Users, Tools and Settings. Core's own order is what
			// people navigate by and it is not ours to reorder.
			//
			// A top-level item rather than a Settings or Tools page because
			// this is not a settings screen: the menu also carries Templates,
			// Custom Fonts, Custom Icons, Code Snippets and Form Submissions,
			// which are content screens with their own list tables.
			81
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
			wcf_asset_version()
		);

		wp_enqueue_script(
			'wcf-admin',
			WCF_ADDONS_URL . 'assets/build/modules/dashboard/index.js',
			array('react', 'react-dom', 'wp-element', 'wp-i18n'),
			wcf_asset_version(),
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

			/*
			 * Widget usage counts. Free ships the button and the per-card
			 * count line; the scan that produces the numbers is Pro
			 * (pro/inc/Usage/), so capability arrives through a filter Pro
			 * answers rather than a direct class reference.
			 *
			 * An empty array is the correct free-only value: the widgets
			 * screen reads it as "Pro is not here" and renders the button as
			 * an upsell instead of firing a request at an endpoint nobody
			 * registered. Note this only ever carries CAPABILITY — shipping
			 * counts with the page would re-introduce exactly the cost the
			 * on-demand design exists to avoid.
			 */
			'usage' => apply_filters('aae/usage/dashboard_payload', array()),
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
				// A translation JSON shipped in this plugin's own /languages folder,
				// addressed by locale + a constant hash -- a local path, never a URL.
				$json    = file_get_contents($json_file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
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

	/**
	 * Forward a "Request New Feature" submission to animation-addons.com.
	 *
	 * The dashboard form cannot post to the vendor's endpoint directly: the
	 * shared key would have to ship in the JS bundle, where it is readable by
	 * anyone with devtools on any install. So the browser posts here and this
	 * method relays it server-to-server, which is the only reason the key
	 * stays in PHP.
	 *
	 * The receiving end is the separate "AAE Feature Request API" plugin
	 * installed on animation-addons.com.
	 *
	 * @since 4.1.1
	 * @return void
	 */
	public function request_new_feature()
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('you are not allowed to do this action', 'animation-addons-for-elementor'));
		}

		$name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$email   = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
		$feature = isset($_POST['feature']) ? sanitize_textarea_field(wp_unslash($_POST['feature'])) : '';

		// Validate here as well as at the far end, so an obviously incomplete
		// form never costs an outbound HTTP request.
		if ('' === $name || '' === $feature || '' === $email || ! is_email($email)) {
			wp_send_json_error(
				esc_html__('Please provide your name, a valid email address, and a feature description.', 'animation-addons-for-elementor')
			);
		}

		/*
		 * Local throttle, per user. The vendor endpoint rate-limits too, but
		 * that limit is keyed on this SITE's IP — so without this, one
		 * impatient admin could exhaust the allowance for everybody on a
		 * multi-user install and the next person would just see a failure.
		 */
		$throttle_key = 'wcf_feature_request_' . get_current_user_id();

		if (get_transient($throttle_key)) {
			wp_send_json_error(
				esc_html__('You have just sent a request. Please wait a moment before sending another.', 'animation-addons-for-elementor')
			);
		}

		$response = wp_safe_remote_post(
			self::feature_request_endpoint(),
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
					'Accept'       => 'application/json',
					'X-API-Key'    => self::feature_request_api_key(),
				),
				'body'    => wp_json_encode(
					array(
						'name'    => $name,
						'email'   => $email,
						'feature' => $feature,
					)
				),
			)
		);

		if (is_wp_error($response)) {
			wp_send_json_error(
				esc_html__('Could not reach the feature request service. Please try again later.', 'animation-addons-for-elementor')
			);
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);

		/*
		 * Only 200/201 count. Anything else is reported as a failure with the
		 * far end's own message when it sent one — a 429 in particular has to
		 * reach the user as "wait", not as a generic error, or they will
		 * keep pressing Submit.
		 */
		if (! in_array($code, array(200, 201), true) || empty($body['success'])) {
			$message = ! empty($body['message'])
				? sanitize_text_field($body['message'])
				: esc_html__('Something went wrong. Please try again.', 'animation-addons-for-elementor');

			wp_send_json_error($message);
		}

		/*
		 * A 2xx with success:true is NOT proof a row was written.
		 *
		 * The receiver answers "Feature request received." for BOTH a fresh
		 * insert (201) and a duplicate it deliberately did not store (200), and
		 * this method used to forward that message and nothing else — so a
		 * submission that stored nothing was reported to the user, and to anyone
		 * debugging, as a success indistinguishable from a real one.
		 *
		 * The id is the proof. Both branches of the receiver return one, so its
		 * absence means the far end never confirmed storage — an older receiver,
		 * a proxy rewriting the body, or something answering on that URL that is
		 * not the plugin at all. That is a failure, and it is reported as one
		 * rather than being smoothed over into a green toast.
		 */
		$stored_id = isset($body['id']) ? (int) $body['id'] : 0;

		if ($stored_id < 1) {
			wp_send_json_error(
				esc_html__('The service accepted the request but did not confirm it was saved. Nothing has been stored — please try again or contact support.', 'animation-addons-for-elementor')
			);
		}

		set_transient($throttle_key, 1, MINUTE_IN_SECONDS);

		/*
		 * A duplicate says so plainly. Telling someone their idea was received
		 * when the receiver recognised it as one it already holds invites them
		 * to send it a third time.
		 */
		if (! empty($body['duplicate'])) {
			wp_send_json_success(
				sprintf(
					/* translators: %d: stored feature request id. */
					esc_html__('You have already sent this request — it is on file as #%d.', 'animation-addons-for-elementor'),
					$stored_id
				)
			);
		}

		wp_send_json_success(
			sprintf(
				/* translators: %d: stored feature request id. */
				esc_html__('Thanks! Your feature request has been saved as #%d.', 'animation-addons-for-elementor'),
				$stored_id
			)
		);
	}

	/**
	 * Where feature requests are sent.
	 *
	 * The value lives in WCF_FEATURE_REQUEST_ENDPOINT (declared in the main
	 * plugin file, overridable from wp-config.php). The filter is the third
	 * layer, for a staging site that needs to decide per-request rather than
	 * per-install.
	 *
	 * `defined()` is still checked because this method has to answer even if
	 * the constant block was edited away — an empty endpoint makes
	 * wp_remote_post() return a WP_Error the caller already reports, which is
	 * a clean failure rather than a fatal.
	 *
	 * @since 4.1.1
	 * @return string
	 */
	private static function feature_request_endpoint()
	{
		$endpoint = defined('WCF_FEATURE_REQUEST_ENDPOINT') ? WCF_FEATURE_REQUEST_ENDPOINT : '';

		return apply_filters('wcf_addons_feature_request_endpoint', $endpoint); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * The shared key the receiver checks.
	 *
	 * Declared as WCF_FEATURE_REQUEST_API_KEY in the main plugin file; see the
	 * note there on why it is obfuscation rather than authentication.
	 *
	 * @since 4.1.1
	 * @return string
	 */
	private static function feature_request_api_key()
	{
		return defined('WCF_FEATURE_REQUEST_API_KEY') ? WCF_FEATURE_REQUEST_API_KEY : '';
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
