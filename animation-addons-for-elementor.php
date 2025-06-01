<?php
/**
 * Plugin Name: Animation Addons for Elementor
 * Description: Animation Addons for Elementor comes with GSAP Animation Builder, Customizable Widgets, Header Footer, Single Post, Archive Page Builder, and more.
 * Plugin URI:  https://animation-addons.com/
 * Version:     2.3.2
 * Author:      Wealcoder
 * Author URI:  https://animation-addons.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: animation-addons-for-elementor
 * Domain Path: /languages
 * Requires Plugins: elementor
 * Elementor tested up to: 3.29.0
 * Elementor Pro tested up to: 3.29.0
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
	define( 'WCF_ADDONS_VERSION', '2.3.2' );
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
	define( 'WCF_TEMPLATE_STARTER_BASE_URL', 'https://themecrowdy.com/' );
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
	const VERSION = '2.3.1';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.25.0';

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

		register_activation_hook( WCF_ADDONS_BASE, [ __CLASS__, 'plugin_activation_hook' ] );
		register_uninstall_hook( WCF_ADDONS_BASE, [ __CLASS__, 'plugin_deactivation_hook' ] );
		add_action('admin_enqueue_scripts', [$this,'enqueue_elementor_install_script']);
		add_action('wp_ajax_wcf_install_elementor_plugin', [$this,'install_elementor_plugin_handler']);
		// Init Plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );		
		add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
	}

	/**
	 * Plugin activation hook
	 *
	 * @since 1.0.0
	 */
	public static function plugin_activation_hook() {
		//set setup wizard
		if ( !get_option( 'wcf_addons_version' ) && !get_option( 'wcf_addons_setup_wizard' ) ) {
			update_option( 'wcf_addons_setup_wizard', 'redirect' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 *
	 * @since 1.0.0
	 */
	public static function plugin_deactivation_hook() {

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
			}
		
			// Sanitize and check the 'page' parameter
			
		} );
		
		add_action( 'current_screen', function ( $screen ) {
			// Check if user has required capabilities
			
			if ( current_user_can( 'manage_options' ) && $screen->id === 'animation-addon_page_wcf_addons_settings' ) {
				// Redirect if setup is incomplete
				if ( 'complete' !== get_option( 'wcf_addons_setup_wizard' ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=wcf_addons_setup_page' ) );
					exit; // Always exit after redirection
				}
			}
		});
		
		// Once we get here, We have passed all validation checks so we can safely include our plugin
		require_once 'class-plugin.php';

		//wcf plugin loaded
		do_action( 'wcf_plugins_loaded' );
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
			echo '<p><strong>Animation Addons for Elementor</strong> requires Elementor plugin to be installed and activated.</p>';
			echo '<p><button id="wcf-install-elementor" class="button button-primary">Install and Activate Elementor</button></p>';
			echo '</div>';
		}
	}
	
	function enqueue_elementor_install_script() {
		// Check if the plugin is not active
		wp_enqueue_style( 'aaeaddon-common', WCF_ADDONS_URL . 'assets/css/wcf-admin.min.css' );
		if ( !is_plugin_active('elementor/elementor.php') ) {
			wp_enqueue_script(
				'wcf-install-elementor-script',
				plugin_dir_url(__FILE__) . 'assets/js/install-elementor.js', // Replace with your JS file path
				['jquery'], // Dependencies
				'2.1', // Version
				true // Load in footer
			);
	
			// Localize script to pass AJAX data
			wp_localize_script('wcf-install-elementor-script', 'wcfelementorAjax', [
				'ajax_url'    => admin_url('admin-ajax.php'),
				'nonce'       => wp_create_nonce('wcfinstall_elementor_nonce'),
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

// Instantiate WCF_ADDONS_Plugin.
new WCF_ADDONS_Plugin();
