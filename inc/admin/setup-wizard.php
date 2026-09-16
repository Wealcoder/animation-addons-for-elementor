<?php

namespace Wealcoder\AnimationAddons\Admin;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

class Aaeaddon_Setup_Wizard_Init
{
	use \Wealcoder\AnimationAddons\Aaeaddon_Extension_Widgets_Trait;
	/**
	 * Parent Menu Page Slug
	 */
	const MENU_PAGE_SLUG = 'wcf_addons_setup_page';

	/**
	 * Menu capability
	 */
	const MENU_CAPABILITY = 'manage_options';

	/**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
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
		$this->remove_all_notices();

		$this->init();
	}

	/**
	 * [init] Assets Initializes
	 * @return [void]
	 */
	public function init()
	{

		add_action('admin_menu', [$this, 'add_menu'], 999);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

		// Hook to check the admin screen after it's loaded
		add_action('current_screen', [$this, 'maybe_remove_admin_footer']);
	}

	/**
	 * Remove Admin Footer Text if on the correct page.
	 */
	public function maybe_remove_admin_footer($screen)
	{

		if (!is_object($screen) || empty($screen->id)) {
			return;
		}

		//if ($screen->id === 'animation-addon_page_wcf_addons_setup_page') {
		if ($screen && strpos($screen->id, '_page_wcf_addons_setup_page') !== false) {
			add_filter('admin_footer_text', '__return_empty_string');
			add_filter('update_footer', '__return_empty_string', 11);
		}
	}

	/**
	 * Reports whether the starter theme is active, installed, or absent.
	 *
	 * Read only. This plugin does not install themes and does not change the
	 * site's active theme -- switching themes is the user's decision, taken in
	 * Appearance > Themes. The wizard uses this purely to label its link.
	 */
	public function theme_status($theme_slug)
	{

		$active_theme = wp_get_theme();
		if ($active_theme->get_stylesheet() === $theme_slug) {
			return 'activeted';
		}
		// Check if the theme is already installed
		$installed_themes = wp_get_themes();
		if (array_key_exists($theme_slug, $installed_themes)) {
			return 'installed';
		}

		return 'installnow';
	}

	/**
	 * [add_menu] Admin Menu
	 */
	public function add_menu()
	{

		add_submenu_page(
			'wcf_addons_page',
			esc_html__('Setup', 'animation-addons-for-elementor'),
			esc_html__('Setup', 'animation-addons-for-elementor'),
			self::MENU_CAPABILITY,
			self::MENU_PAGE_SLUG,
			[$this, 'render_wizard']
		);
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
		if (!$screen || strpos($screen->id, '_page_wcf_addons_setup_page') === false) {
			return;
		}

		// Load config once
		$config = aaeaddon_get_config();

		// CSS
		wp_enqueue_style(
			'wcf-admin',
			AAEADDON_URL . 'assets/build/modules/dashboard/wizardSetup.css',
			array( \Wealcoder\AnimationAddons\Aaeaddon_Fonts::ensure() ),
			AAEADDON_VERSION
		);

		// JS
		wp_enqueue_script(
			'wcf-admin',
			AAEADDON_URL . 'assets/build/modules/dashboard/wizardSetup.js',
			array('wp-data', 'react', 'react-dom', 'wp-element', 'wp-i18n'),
			AAEADDON_VERSION,
			true
		);

		// Count extensions & widgets
		aaeaddon_get_total_config_elements_by_key($config['extensions'], $total_extensions);
		aaeaddon_get_total_config_elements_by_key($config['widgets'], $total_widgets);

		// Widgets
		$widgets       = get_option('aaeaddon_save_widgets');
		$saved_widgets = is_array($widgets) ? array_keys($widgets) : [];
		aaeaddon_get_search_active_keys($config['widgets'], $saved_widgets, $foundKeys, $awidgets);

		// Extensions
		$extensions       = get_option('aaeaddon_save_extensions');
		$saved_extensions = is_array($extensions) ? array_keys($extensions) : [];
		aaeaddon_get_search_active_keys($config['extensions'], $saved_extensions, $foundext, $activeext);

		$active_widgets = self::get_widgets();
		$active_ext     = self::get_extensions();

		$current_user = wp_get_current_user();

		$localize_data = [
			'ajaxurl'       => admin_url('admin-ajax.php'),
			'nonce'         => wp_create_nonce('wcf_admin_nonce'),

			'addons_config' => apply_filters(
				'wcf_addons_dashboard_config',  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$config
			),

			'extensions' => [
				'total'  => $total_extensions,
				'active' => is_array($active_ext) ? count($active_ext) : 0
			],

			'widgets' => [
				'total'  => $total_widgets,
				'active' => is_array($active_widgets) ? count($active_widgets) : 0
			],

			'adminURL' => admin_url(),
			'version'  => AAEADDON_VERSION,

			// Read only: the wizard shows the starter theme's state and links to
			// Appearance > Themes. Nothing here installs or activates a theme.
			'theme_status' => $this->theme_status('hello-animation'),

			'user' => [
				'email'        => $current_user->user_email,
				'roles'        => $current_user->roles,
				'display_name' => $current_user->display_name,
				'f_name'       => $current_user->first_name
			]
		];

		wp_localize_script('wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data);
	}


	/**
	 * Render wizard
	 * @return [void]
	 */
	public function render_wizard()
	{
?>
		<div class="wrap wcf-admin-wrapper" id="wcf-animation-addon-wizard">
		</div>
<?php
	}


	/**
	 * [remove_all_notices] remove addmin notices
	 * @return [void]
	 */
	public function remove_all_notices()
	{
		add_action('in_admin_header', function () {

			$screen = get_current_screen();

			if (
				$screen &&
				(
					strpos($screen->id, '_page_wcf_addons_setup_page') !== false ||
					strpos($screen->id, '_page_wcf-cpt-builder') !== false
				)
			) {
				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');
			}

		}, 1000);
	}
}

Aaeaddon_Setup_Wizard_Init::instance();
