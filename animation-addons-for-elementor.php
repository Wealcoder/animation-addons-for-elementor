<?php
/**
 * Plugin Name:                Animation Addons
 * Description:                Animation Addons for Elementor comes with GSAP Animation Builder, Customizable Widgets, Header Footer, Single Post, Archive Page Builder, and more.
 * Plugin URI:                 https://animation-addons.com/
 * Version:                    4.1.0
 * Author:                     Wealcoder
 * Author URI:                 https://animation-addons.com/
 * License:                    GPL v2 or later
 * License URI:                https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:                animation-addons-for-elementor
 * Domain Path:                /languages
 * Requires at least: 		   6.6
 * Requires PHP:               7.4
 * Requires Plugins:           elementor
 * Tested up to:               7.1
 * Elementor tested up to:     4.2.4
 * Elementor Pro tested up to: 4.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! defined( 'WCF_ADDONS_DASHBOARD_V2' ) ) {
	define( 'WCF_ADDONS_DASHBOARD_V2', true);
}

if ( ! defined( 'WCF_ADDONS_VERSION' ) ) {
	/**
	 * Plugin Version.
	 */
	define('WCF_ADDONS_VERSION', '4.1.0');
}
if ( ! defined( 'WCF_ADDONS_FILE' ) ) {
	/**
	 * Plugin File Ref.
	 */
	define( 'WCF_ADDONS_FILE', __FILE__ );
}
if ( ! defined( 'WCF_ADDONS_BASE' ) ) {
	/**
	 * Plugin Base Name.
	 */
	define( 'WCF_ADDONS_BASE', plugin_basename( WCF_ADDONS_FILE ) );
}
if ( ! defined( 'WCF_ADDONS_PATH' ) ) {
	/**
	 * Plugin Dir Ref.
	 */
	define( 'WCF_ADDONS_PATH', plugin_dir_path( WCF_ADDONS_FILE ) );
}
if ( ! defined( 'WCF_ADDONS_URL' ) ) {
	/**
	 * Plugin URL.
	 */
	define( 'WCF_ADDONS_URL', plugin_dir_url( WCF_ADDONS_FILE ) );
}
if ( ! defined( 'WCF_ADDONS_WIDGETS_PATH' ) ) {
	/**
	 * Widgets Dir Ref.
	 */
	define( 'WCF_ADDONS_WIDGETS_PATH', WCF_ADDONS_PATH . 'widgets/' );
}

if ( ! defined( 'WCF_TEMPLATE_STARTER_BASE_URL' ) ) {
	/**
	 * Template Path
	 */
	define( 'WCF_TEMPLATE_STARTER_BASE_URL', 'https://www.themecrowdy.com/' );
}

if ( ! defined( 'WCF_FEATURE_REQUEST_ENDPOINT' ) ) {
	define( 'WCF_FEATURE_REQUEST_ENDPOINT', 'https://animation-addons.com/wp-json/aae/v1/request-new-feature' );
}

if ( ! defined( 'WCF_FEATURE_REQUEST_API_KEY' ) ) {
	/**
	 * Shared key the receiver checks, sent as the X-API-Key header.
	 *
	 * Must match AAEFR_API_KEY on the receiving side — change one without the
	 * other and every submission comes back 401.
	 *
	 * This is obfuscation, NOT authentication: the plugin ships publicly, so
	 * the key is extractable from the zip. The receiver's own rate limit is
	 * what actually protects the endpoint.
	 */
	define( 'WCF_FEATURE_REQUEST_API_KEY', '0700c72d204521236f5af03011cb0cbb4f6229a6bbdc2ef041d76184e9a795b7' );
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Main WCF_ADDONS_Plugin Class
 *
 * The init class that runs the Hello World plugin.
 * Intended To make sure that the plugin's minimum requirements are met.
 *
 * You should only modify the constants to match your plugin's needs.
 *
 * Any custom code should go inside Plugin Class in the plugin.php file.
 *
 * @since 1.2.0
 */
final class WCF_ADDONS_Plugin {

	/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 * @var string The plugin version.
	 */
	const VERSION = '3.0.1';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.32.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.2.0
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		
		// register_activation_hook( WCF_ADDONS_BASE, [ __CLASS__, 'plugin_activation_hook' ] );
		// register_deactivation_hook( WCF_ADDONS_BASE, [ __CLASS__, 'plugin_deactivation_hook' ] );
		// register_uninstall_hook( WCF_ADDONS_BASE, [ __CLASS__, 'plugin_unregister_hook' ] );
		add_action('admin_enqueue_scripts', [$this,'enqueue_elementor_install_script']);
		add_action('admin_head', [$this,'print_admin_menu_icon_style']);
		add_action('wp_ajax_wcf_install_elementor_plugin', [$this,'install_elementor_plugin_handler']);
		// Init Plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		// Translations must not load before `init` (WP 6.7+ warns via
		// _load_textdomain_just_in_time). Kept separate from init() above, which
		// has to stay on plugins_loaded for the Elementor bootstrap ordering.
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );		
		
	}

	/**
	 * Plugin activation hook
	 *
	 * @since 1.0.0
	 */
	public static function plugin_activation_hook() {

		if ( ! get_option('aae_installed') ) {
			add_option('aae_installed', time(), '', false);
		}

		if ( ! get_option('wcf_addons_setup_wizard') ) {
			update_option('wcf_addons_setup_wizard', 'redirect', false);
		}

		flush_rewrite_rules();
	}
	/**
	 * Plugin dactivation hook
	 *
	 * @since 1.0.0
	 */
	public static function plugin_deactivation_hook() {

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 *
	 * @since 1.0.0
	 */
	public static function plugin_unregister_hook() {

		$options = [
			'aae_installed',
			'aae_do_activation_redirect',
			'wcf_addons_setup_wizard',
			'wcf_addons_version',

			'aae_activation_count',
			'aae_deactivation_count',

			'aae_last_activated',
			'aae_last_deactivated',

			// Written by the removed wizard lead capture. Kept in this list so
			// sites that already carry the row are cleaned up on uninstall.
			'wcf_addons_wizard_subscribed',

			'aae_send_activation_event',
			'aae_send_deactivation_event',
		];

		foreach ($options as $option) {
			delete_option($option);
		}
	}

	/**
	 * Initialize the plugin
	 *
	 * Validates that Elementor is already loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed include the plugin class.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	/**
	 * Load the plugin textdomain.
	 *
	 * Hooked to `init` (priority 0), never to `plugins_loaded`. Since WP 6.7 any
	 * translation triggered before `init` raises "_load_textdomain_just_in_time
	 * was called incorrectly".
	 *
	 * Note this call is optional for a WordPress.org-hosted plugin — core has
	 * loaded translations from the languages directory automatically since 4.6,
	 * and the docs now discourage calling it by hand. It is kept because the
	 * plugin also ships its own /languages folder.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function load_textdomain() {

		load_plugin_textdomain(
			'animation-addons-for-elementor',
			false,
			dirname(plugin_basename(WCF_ADDONS_FILE)) . '/languages'
		);
	}

	public function init() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {			
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );

			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );

			return;
		}

		add_action( 'wp_loaded', function () {
			// Set current version to DB
			if ( get_option( 'wcf_addons_version' ) !== WCF_ADDONS_VERSION ) {
				// Update plugin version
				update_option( 'wcf_addons_version', WCF_ADDONS_VERSION );

				/*
				 * Drop Elementor's cached ATOMIC BASE STYLES on every version change.
				 *
				 * Atomic_Widget_Base_Styles::get_all_base_styles() walks every
				 * registered atomic element ONCE and caches the combined
				 * stylesheet under a single 'base' key. Nothing in that pipeline
				 * notices that a plugin update added a widget or edited a
				 * define_base_styles() — the cache is only invalidated by
				 * Elementor's own `elementor/core/files/clear_cache`, which this
				 * plugin listened to but never fired.
				 *
				 * So a new widget shipped with NO base CSS at all until somebody
				 * happened to press Elementor > Tools > Clear Files & Data. That
				 * is how the Google Maps widget reached a live page with its
				 * `position/overflow/width/height` rules missing: the iframe kept
				 * the browser's default 300x150 inline box, so resizing the
				 * widget in the Style tab appeared to do nothing.
				 *
				 * The version option is written BEFORE this on purpose. If
				 * clearing ever throws, the site loses one cache clear rather
				 * than re-running a full cache rebuild on every single request.
				 */
				if ( class_exists( '\Elementor\Plugin' )
					&& isset( \Elementor\Plugin::$instance->files_manager ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}
			}
		
			// Sanitize and check the 'page' parameter
			
		} );
		
		add_action( 'current_screen', function ( $screen ) {
			// Check if user has required capabilities
			
			if ( current_user_can( 'manage_options' ) &&  strpos( $screen->id, '_page_wcf_addons_settings' ) !== false ) {
				// Redirect if setup is incomplete
				if ( 'complete' !== get_option( 'wcf_addons_setup_wizard' ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=wcf_addons_setup_page' ) );
					exit; // Always exit after redirection
				}
			}
		});
		
		// Once we get here, We have passed all validation checks so we can safely include our plugin
		require_once 'class-plugin.php';
		require_once 'inc/AtomicWidgets/class-atomic.php';

		//wcf plugin loaded
		do_action('wcf_plugins_loaded');
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {
	     
		if ( !is_plugin_active('elementor/elementor.php') ) {		
			echo '<div class="notice notice-error" id="elementor-install-notice">';
			echo '<p><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M14.0002 25.6666C20.4435 25.6666 25.6668 20.4433 25.6668 14C25.6668 7.55666 20.4435 2.33331 14.0002 2.33331C7.55684 2.33331 2.3335 7.55666 2.3335 14C2.3335 20.4433 7.55684 25.6666 14.0002 25.6666Z" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M14 9.33331V14.5833" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M14 18.653V18.6647" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
				</svg> <strong>Animation Addons for Elementor</strong> requires <strong>Elementor</strong> plugin to be installed and activated.</p>';
				echo '<button name="animation-addons-for-elementor" slug="animation-addons-for-elementor/animation-addons-for-elementor.php" id="wcf-install-elementor" class="button button-primary"><svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M6.96475 6.85674L13.5055 0.315979L14.684 1.49449L13.5055 2.673L15.5679 4.7354L14.3894 5.9139L12.327 3.85151L11.1485 5.03002L12.9163 6.79782L11.7378 7.97632L9.97 6.20857L8.14325 8.03524C9.21509 9.65307 9.03833 11.8542 7.61292 13.2796C5.98576 14.9068 3.34758 14.9068 1.72039 13.2796C0.0932021 11.6524 0.0932021 9.01424 1.72039 7.38707C3.14578 5.96165 5.34694 5.7849 6.96475 6.85674ZM6.43442 12.1011C7.41075 11.1247 7.41075 9.5419 6.43442 8.56557C5.45813 7.58924 3.87521 7.58924 2.8989 8.56557C1.92259 9.5419 1.92259 11.1247 2.8989 12.1011C3.87521 13.0774 5.45813 13.0774 6.43442 12.1011Z" fill="white"/>
				</svg>Activate</button>';
			echo '</div>';
		}
	}
	
	/**
	 * The one admin rule that belongs on every screen: our own item in
	 * #adminmenu, which WordPress prints on every page. Two declarations,
	 * inlined, so no stylesheet is requested on screens that need nothing
	 * else from us. It reaches only our own menu item.
	 */
	public function print_admin_menu_icon_style() {

		echo '<style id="aae-admin-menu-icon">#adminmenu .toplevel_page_wcf_addons_page .wp-menu-image img{opacity:1;padding:7px 0 0}</style>';
	}

	/**
	 * Admin assets for the "Elementor is missing" notice.
	 *
	 * Loads on the screens that need it and nowhere else: this stylesheet used
	 * to be enqueued on every admin page, which is asking every screen in
	 * WordPress to download our CSS for nothing.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_elementor_install_script($hook) {

		// wp-admin/includes/plugin.php is loaded on admin screens, but this
		// runs on a hook other plugins can fire early, so do not assume it.
		if ( ! function_exists('is_plugin_active') ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$needs_notice = ! is_plugin_active('elementor/elementor.php');

		$screen     = function_exists('get_current_screen') ? get_current_screen() : null;
		$our_screen = $screen && false !== strpos( (string) $screen->id, '_page_wcf_addons_' );

		// The notice prints on every admin screen while Elementor is missing,
		// so its styles have to follow it. Otherwise this is ours alone.
		if ( ! $needs_notice && ! $our_screen ) {
			return;
		}

		wp_enqueue_style(
			'aaeaddon-common',
			WCF_ADDONS_URL . 'assets/css/wcf-admin.min.css',
			[],
			WCF_ADDONS_VERSION
		);

		if ( $needs_notice ) {

			wp_enqueue_script(
				'wcf-install-elementor-script',
				plugin_dir_url(__FILE__) . 'assets/js/install-elementor.js',
				['jquery'],
				WCF_ADDONS_VERSION,
				true
			);

			wp_localize_script('wcf-install-elementor-script', 'wcfelementorAjax', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('wcfinstall_elementor_nonce'),
			]);
		}
	}
	
	function install_elementor_plugin_handler() {
		// Verify the AJAX nonce for security
		check_ajax_referer('wcfinstall_elementor_nonce', '_ajax_nonce');

		if (!current_user_can('activate_plugins')) {
			wp_send_json_error(['message' => esc_html__('Plugin Activation Permission Required, Contact Admin', 'animation-addons-for-elementor')]);
        }
		
		// Include required WordPress files
		if (!class_exists('Plugin_Upgrader')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if (!class_exists('WP_Ajax_Upgrader_Skin')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}
		if (!function_exists('plugins_api')) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Include the plugins_api function
		}
	
		$plugin_slug = 'elementor';
		$plugin_file = 'elementor/elementor.php';
	
		// Check if the plugin is already active
		if (is_plugin_active($plugin_file)) {
			wp_send_json_success(['message' => esc_html__('Plugin is already active.', 'animation-addons-for-elementor')]);
		}
		
		// Fetch plugin information dynamically using the WordPress Plugin API
		$api = plugins_api('plugin_information', [
			'slug'   => $plugin_slug,
			'fields' => [
				'sections' => false,
			],
		]);
	
		if (is_wp_error($api)) {
			wp_send_json_error(['message' => esc_html__('Failed to retrieve plugin information.', 'animation-addons-for-elementor')]);
		}
	
		// Get the download URL for the plugin
		$download_url = $api->download_link;
	
		if (empty($download_url)) {
			wp_send_json_error(['message' => esc_html__('Failed to retrieve plugin download URL.', 'animation-addons-for-elementor')]);
		}
	
		// Install the plugin using the retrieved download URL
		$upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
		$installed = $upgrader->install($download_url);
	
		if (is_wp_error($installed)) {			
			wp_send_json_error(['message' => $installed->get_error_message()]);
		}
	
		// Activate the plugin if installed successfully
		if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
			$activated = activate_plugin($plugin_file);
	
			if (is_wp_error($activated)) {			
				wp_send_json_error(['message' => $activated->get_error_message()]);
			}
	
			wp_send_json_success(['message' => esc_html__('Elementor has been successfully installed and activated.', 'animation-addons-for-elementor')]);
		}
	
		// If the plugin file is not found, send an error
		wp_send_json_error(['message' => esc_html__('Plugin installation failed.', 'animation-addons-for-elementor')]);
	}
	
	

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {
		if (!current_user_can('activate_plugins')) {
            return;
        }

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'animation-addons-for-elementor' ),
			'<strong>' . esc_html__( 'Animation Addons for Elementor', 'animation-addons-for-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'animation-addons-for-elementor' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {
		if (!current_user_can('activate_plugins')) {
            return;
        }

		$message = sprintf(
		/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'animation-addons-for-elementor' ),
			'<strong>' . esc_html__( 'Animation Addons for Elementor', 'animation-addons-for-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'animation-addons-for-elementor' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	
}


// ✅ Register hooks here (outside class)
register_activation_hook( WCF_ADDONS_FILE, ['WCF_ADDONS_Plugin', 'plugin_activation_hook'] );
register_deactivation_hook( WCF_ADDONS_FILE, ['WCF_ADDONS_Plugin', 'plugin_deactivation_hook'] );
register_uninstall_hook( WCF_ADDONS_FILE, ['WCF_ADDONS_Plugin', 'plugin_unregister_hook'] );

// Instantiate WCF_ADDONS_Plugin.
new WCF_ADDONS_Plugin();





