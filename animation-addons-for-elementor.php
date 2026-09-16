<?php
/**
 * Plugin Name:                Animation Addons
 * Description:                Animation Addons for Elementor comes with GSAP Animation Builder, Customizable Widgets, Header Footer, Single Post, Archive Page Builder, and more.
 * Plugin URI:                 https://animation-addons.com/
 * Version:                    4.2.0
 * Author:                     Wealcoder
 * Author URI:                 https://animation-addons.com/
 * License:                    GPL v2 or later
 * License URI:                https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:                animation-addons-for-elementor
 * Domain Path:                /languages
 * Requires at least: 		   6.6
 * Requires PHP:               7.4
 * Requires Plugins:           elementor
 * Elementor tested up to:     4.2.4
 * Elementor Pro tested up to: 4.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'aaeaddon_define' ) ) :
/**
 * Define one of this plugin's constants, honouring a pre-4.2 override.
 *
 * 4.2.0 renamed the define prefix from `WCF_ADDONS_` / `WCF_` to `AAEADDON_`
 * for WordPress.org's unique-prefix review. `wp-config.php` runs long before
 * any plugin, so a site that pins one of these — a staging site pointing the
 * template server somewhere else, a feature flag switched off, a self-hosted
 * feature-request endpoint — has ALREADY defined the old name by the time we
 * get here. Taking the built-in default instead would drop that override
 * silently: the site keeps working and quietly talks to the wrong server.
 *
 * So the old name wins when it is present, and is then defined under the new
 * name as well. `$legacy` is the pre-4.2 spelling, or null for a constant that
 * never had one.
 *
 * @since 4.2.0
 *
 * @param string      $name   The current constant name.
 * @param mixed       $value  Its built-in default.
 * @param string|null $legacy The pre-4.2 spelling to prefer, if defined.
 */
function aaeaddon_define( $name, $value, $legacy = null ) {
	if ( defined( $name ) ) {
		return;
	}
	if ( null !== $legacy && defined( $legacy ) ) {
		$value = constant( $legacy );
	}
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound -- every caller below passes an AAEADDON_ literal.
	define( $name, $value );
}
endif;

aaeaddon_define( 'AAEADDON_DASHBOARD_V2', true, 'WCF_ADDONS_DASHBOARD_V2' );

/**
 * Plugin Version.
 */
aaeaddon_define( 'AAEADDON_VERSION', '4.2.0', 'WCF_ADDONS_VERSION' );

/**
 * Plugin File Ref.
 */
aaeaddon_define( 'AAEADDON_FILE', __FILE__, 'WCF_ADDONS_FILE' );

/**
 * Plugin Base Name.
 */
aaeaddon_define( 'AAEADDON_BASE', plugin_basename( AAEADDON_FILE ), 'WCF_ADDONS_BASE' );

/**
 * Plugin Dir Ref.
 */
aaeaddon_define( 'AAEADDON_PATH', plugin_dir_path( AAEADDON_FILE ), 'WCF_ADDONS_PATH' );

/**
 * Plugin URL.
 */
aaeaddon_define( 'AAEADDON_URL', plugin_dir_url( AAEADDON_FILE ), 'WCF_ADDONS_URL' );

/**
 * Widgets Dir Ref.
 */
aaeaddon_define( 'AAEADDON_WIDGETS_PATH', AAEADDON_PATH . 'widgets/', 'WCF_ADDONS_WIDGETS_PATH' );

/**
 * Template server base.
 *
 * Overridden in wp-config.php on staging sites to point at a local copy — the
 * reason `aaeaddon_define()` prefers the old spelling.
 */
aaeaddon_define( 'AAEADDON_TEMPLATE_STARTER_BASE_URL', 'https://www.themecrowdy.com/', 'WCF_TEMPLATE_STARTER_BASE_URL' );

aaeaddon_define( 'AAEADDON_FEATURE_REQUEST_ENDPOINT', 'https://animation-addons.com/wp-json/aae/v1/request-new-feature', 'WCF_FEATURE_REQUEST_ENDPOINT' );

/**
 * Shared key the receiver checks, sent as the X-API-Key header.
 *
 * Must match AAEFR_API_KEY on the receiving side — change one without the
 * other and every submission comes back 401.
 *
 * Shared client key sent as the X-API-Key header to route and rate-limit
 * submissions on the feature request receiving endpoint.
 */
aaeaddon_define( 'AAEADDON_FEATURE_REQUEST_API_KEY', '0700c72d204521236f5af03011cb0cbb4f6229a6bbdc2ef041d76184e9a795b7', 'WCF_FEATURE_REQUEST_API_KEY' );

/*
 * The pre-4.2 spellings of the seven constants above, kept as aliases.
 *
 * `WCF_ADDONS_*` was the plugin's define prefix until 4.2.0 renamed it to
 * `AAEADDON_` for WordPress.org's unique-prefix review. These seven are the
 * ONLY ones that leave this plugin: the paid add-on reads `WCF_ADDONS_PATH` on
 * 37 lines (eleven of its loop-filter files `require_once` OUR files by that
 * path), `WCF_ADDONS_VERSION` on eleven — it is the add-on's whole
 * free-is-present-and-new-enough test — plus `_URL` and `_DASHBOARD_V2`. A
 * child theme or a site snippet may read any of them.
 *
 * So they are defined, not merely documented: the add-on is updated by hand,
 * weeks after this plugin auto-updates from WordPress.org, and an undefined
 * constant is a fatal in PHP 8. Same reasoning, and the same permanence, as
 * the `WCF_ADDONS_Plugin` class alias at the bottom of this file.
 *
 * `AAEADDON_TEMPLATE_STARTER_BASE_URL` and the two feature-request constants
 * get NO alias: nothing outside this plugin has ever read them.
 *
 * @since 4.2.0
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- the pre-4.2 names being aliased, defined from a keyed list.
foreach (
	array(
		'WCF_ADDONS_DASHBOARD_V2' => AAEADDON_DASHBOARD_V2,
		'WCF_ADDONS_VERSION'      => AAEADDON_VERSION,
		'WCF_ADDONS_FILE'         => AAEADDON_FILE,
		'WCF_ADDONS_BASE'         => AAEADDON_BASE,
		'WCF_ADDONS_PATH'         => AAEADDON_PATH,
		'WCF_ADDONS_URL'          => AAEADDON_URL,
		'WCF_ADDONS_WIDGETS_PATH' => AAEADDON_WIDGETS_PATH,
	) as $aaeaddon_legacy_const => $aaeaddon_const_value
) {
	if ( ! defined( $aaeaddon_legacy_const ) ) {
		define( $aaeaddon_legacy_const, $aaeaddon_const_value );
	}
}
unset( $aaeaddon_legacy_const, $aaeaddon_const_value );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}

/*
 * The pre-4.2 namespace (`WCF_ADDONS\`) is still answered, for the paid
 * add-on and for any site code that imported a class under the old name.
 * Must follow Composer's loader and precede everything else.
 */
require __DIR__ . '/inc/Compat/namespace-alias.php';

/*
 * Every option is `aaeaddon_…` in the code from 4.2 on; the database keeps
 * the pre-4.2 rows and the bridge answers both spellings from one live row
 * (inc/Compat/key-map.php is the list). Booted here, before the add-on or
 * anything else reads an option.
 */
\Wealcoder\AnimationAddons\Compat\Key_Bridge::boot();

/**
 * Main Aaeaddon_Plugin Class
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
final class Aaeaddon_Plugin {

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
		
		// register_activation_hook( AAEADDON_BASE, [ __CLASS__, 'plugin_activation_hook' ] );
		// register_deactivation_hook( AAEADDON_BASE, [ __CLASS__, 'plugin_deactivation_hook' ] );
		// register_uninstall_hook( AAEADDON_BASE, [ __CLASS__, 'plugin_unregister_hook' ] );
		add_action('admin_enqueue_scripts', [$this,'enqueue_admin_notice_style']);
		add_action('admin_head', [$this,'print_admin_menu_icon_style']);
		// The storage-name migration screen, notices and endpoints. Before
		// init() and NOT gated on Elementor: the bridge serves options either way.
		//
		// is_admin() only, because every hook Migration::init() registers is an
		// admin one -- admin_menu, admin_init, submenu_file, after_plugin_row_*,
		// five wp_ajax_* and the dashboard config filter. On a visitor's request
		// not one of them can fire, so loading the class there parsed 29 KB to
		// register callbacks nothing would ever call. The BRIDGE is what a
		// front-end request needs and that is a different file, booted above.
		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( '\Wealcoder\AnimationAddons\Compat\Migration', 'init' ), 5 );
		}
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

		// Decide the storage-name migration state FIRST: a fresh site is
		// complete from here, an existing database waits for consent. Every
		// option written below goes to whichever row that decision made live.
		\Wealcoder\AnimationAddons\Compat\Migration::on_activation();

		if ( ! get_option('aaeaddon_installed') ) {
			add_option('aaeaddon_installed', time(), '', false);
		}

		if ( ! get_option('aaeaddon_setup_wizard') ) {
			update_option('aaeaddon_setup_wizard', 'redirect', false);
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

		// The plugin's own bookkeeping. Each name is deleted under BOTH its
		// spellings (inc/Compat/key-map.php) — the only place the pre-4.2 rows
		// are ever removed. Settings, templates and post data are left alone,
		// as they always were.
		$options = [
			'aaeaddon_installed',
			'aaeaddon_setup_wizard',
			'aaeaddon_version',
			'aaeaddon_wizard_subscribed',
			'aaeaddon_migration_state',
			'aaeaddon_migration_log',
		];

		foreach ($options as $option) {
			\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option($option);
		}
		foreach (\Wealcoder\AnimationAddons\Compat\Key_Bridge::map()['dead'] as $option) {
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
			$previous_version = get_option( 'aaeaddon_version' );
			if ( $previous_version !== AAEADDON_VERSION ) {
				// Update plugin version
				update_option( 'aaeaddon_version', AAEADDON_VERSION );

				/*
				 * Decide the storage-name migration once per version: an
				 * existing database waits for consent, a fresh one is complete.
				 * One option write, no scan — see inc/Compat/Migration.php.
				 */
				\Wealcoder\AnimationAddons\Compat\Migration::on_version_change( is_string( $previous_version ) ? $previous_version : '' );

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
				if ( 'complete' !== get_option( 'aaeaddon_setup_wizard' ) ) {
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

		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			return;
		}

		// This plugin installs nothing and activates nothing on the user's
		// behalf. The notice says what is missing and hands the job to
		// WordPress's own screens, which do it with their own capability
		// checks and their own nonces -- so there is no installer, no
		// activator and no endpoint of ours behind this button.
		$installed = file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' );

		$action_url  = '';
		$action_text = '';

		if ( $installed && current_user_can( 'activate_plugins' ) ) {
			$action_url  = wp_nonce_url(
				self_admin_url( 'plugins.php?action=activate&plugin=elementor%2Felementor.php' ),
				'activate-plugin_elementor/elementor.php'
			);
			$action_text = __( 'Activate Elementor', 'animation-addons-for-elementor' );
		} elseif ( ! $installed && current_user_can( 'install_plugins' ) ) {
			$action_url  = self_admin_url( 'plugin-install.php?tab=search&type=term&s=elementor' );
			$action_text = __( 'Find Elementor', 'animation-addons-for-elementor' );
		}

		$message = $installed
			? __( 'requires <strong>Elementor</strong> to be activated.', 'animation-addons-for-elementor' )
			: __( 'requires the <strong>Elementor</strong> plugin to be installed and activated.', 'animation-addons-for-elementor' );

		echo '<div class="notice notice-error" id="elementor-install-notice">';
		echo '<p><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M14.0002 25.6666C20.4435 25.6666 25.6668 20.4433 25.6668 14C25.6668 7.55666 20.4435 2.33331 14.0002 2.33331C7.55684 2.33331 2.3335 7.55666 2.3335 14C2.3335 20.4433 7.55684 25.6666 14.0002 25.6666Z" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M14 9.33331V14.5833" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M14 18.653V18.6647" stroke="#FC6848" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
			</svg> <strong>Animation Addons for Elementor</strong> ' . wp_kses( $message, array( 'strong' => array() ) ) . '</p>';

		if ( $action_url ) {
			echo '<a href="' . esc_url( $action_url ) . '" id="wcf-elementor-action" class="button button-primary"><svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M6.96475 6.85674L13.5055 0.315979L14.684 1.49449L13.5055 2.673L15.5679 4.7354L14.3894 5.9139L12.327 3.85151L11.1485 5.03002L12.9163 6.79782L11.7378 7.97632L9.97 6.20857L8.14325 8.03524C9.21509 9.65307 9.03833 11.8542 7.61292 13.2796C5.98576 14.9068 3.34758 14.9068 1.72039 13.2796C0.0932021 11.6524 0.0932021 9.01424 1.72039 7.38707C3.14578 5.96165 5.34694 5.7849 6.96475 6.85674ZM6.43442 12.1011C7.41075 11.1247 7.41075 9.5419 6.43442 8.56557C5.45813 7.58924 3.87521 7.58924 2.8989 8.56557C1.92259 9.5419 1.92259 11.1247 2.8989 12.1011C3.87521 13.0774 5.45813 13.0774 6.43442 12.1011Z" fill="white"/>
			</svg>' . esc_html( $action_text ) . '</a>';
		}

		echo '</div>';
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
	 * Admin styles for the "Elementor is missing" notice.
	 *
	 * Loads on the screens that need it and nowhere else: this stylesheet used
	 * to be enqueued on every admin page, which is asking every screen in
	 * WordPress to download our CSS for nothing.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_notice_style($hook) {

		// The screen check is a string compare and decides the answer on its
		// own for our own pages, so it goes first. Everywhere else the
		// stylesheet is only wanted while the notice is printing, and that
		// is the one question worth loading a core file to answer.
		$screen     = function_exists('get_current_screen') ? get_current_screen() : null;
		$our_screen = $screen && false !== strpos( (string) $screen->id, '_page_wcf_addons_' );

		if ( ! $our_screen ) {

			// wp-admin/includes/plugin.php is loaded on admin screens, but
			// this runs on a hook other plugins can fire early, so do not
			// assume it. is_plugin_active() is called immediately below.
			if ( ! function_exists('is_plugin_active') ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// The notice prints on every admin screen while Elementor is
			// missing, so its styles have to follow it. With Elementor
			// active there is no notice and this is not our page.
			if ( is_plugin_active('elementor/elementor.php') ) {
				return;
			}
		}

		wp_enqueue_style(
			'aaeaddon-common',
			AAEADDON_URL . 'assets/css/wcf-admin.min.css',
			[],
			AAEADDON_VERSION
		);

		// No script goes with the notice: its button is an ordinary link to a
		// WordPress screen, so it works with nothing of ours loaded at all.
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
		if ( ! current_user_can( 'manage_options' ) ) {
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
		if ( ! current_user_can( 'manage_options' ) ) {
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

/*
 * The pre-4.2 name of the class above, and one of the two backward-compatible
 * shims this plugin keeps (the other is `wcf_addons_get_saved_template_list()`).
 *
 * `WCF_ADDONS_Plugin` is how everything outside this plugin asks whether it is
 * installed: the paid add-on gates its whole boot on `class_exists()` of it,
 * its own diagnostics screen reads it, and the Crowdy theme's "essential
 * plugins" panel names it. None of those is updated at the moment this plugin
 * auto-updates from WordPress.org, so the old name has to keep answering.
 *
 * Declared here rather than through the autoloader in `inc/Compat/`: this is a
 * GLOBAL class defined in the bootstrap, not a namespaced one Composer could
 * ever reach, and `class_exists( 'WCF_ADDONS_Plugin' )` has to be true from the
 * moment the plugin file has finished loading.
 *
 * @since 4.2.0
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassnameFound -- the pre-4.2 name being aliased.
class_alias( 'Aaeaddon_Plugin', 'WCF_ADDONS_Plugin' );

// ✅ Register hooks here (outside class)
register_activation_hook( AAEADDON_FILE, ['Aaeaddon_Plugin', 'plugin_activation_hook'] );
register_deactivation_hook( AAEADDON_FILE, ['Aaeaddon_Plugin', 'plugin_deactivation_hook'] );
register_uninstall_hook( AAEADDON_FILE, ['Aaeaddon_Plugin', 'plugin_unregister_hook'] );

// Instantiate Aaeaddon_Plugin.
new Aaeaddon_Plugin();


