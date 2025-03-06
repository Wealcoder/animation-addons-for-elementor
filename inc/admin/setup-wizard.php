<?php

namespace WCF_ADDONS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class WCF_Setup_Wizard_Init {
    use \WCF_ADDONS\WCF_Extension_Widgets_Trait;
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
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->remove_all_notices();

		$this->init();
	}

	/**
	 * [init] Assets Initializes
	 * @return [void]
	 */
	public function init() {
      
		add_action( 'admin_menu', [ $this, 'add_menu' ], 999 );		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_save_setup_wizard_settings', [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_wcf_installer_theme', [ $this ,'ajax_install_theme' ]);
		add_action( 'wp_ajax_wcf_activate_theme', [ $this ,'activate_theme' ]);
	
		 // Hook to check the admin screen after it's loaded
		 add_action('current_screen', [$this, 'maybe_remove_admin_footer']);
		
	}

	/**
	 * Remove Admin Footer Text if on the correct page.
	 */
	public function maybe_remove_admin_footer($screen) {
	
		if (!is_object($screen) || empty($screen->id)) {
			return;
		}

		if ($screen->id === 'animation-addon_page_wcf_addons_setup_page') {
			add_filter('admin_footer_text', '__return_empty_string');
			add_filter('update_footer', '__return_empty_string', 11);
		}
	}
	
	public function theme_status($theme_slug){
	
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
	
    function activate_theme() {
        check_ajax_referer('wcf_admin_nonce', 'nonce');
    
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You are not allowed to do this action', 'animation-addons-for-elementor')]);
        }
    
        // Get the theme slug
        $theme_slug = isset($_POST['theme_slug']) ? sanitize_text_field(wp_unslash($_POST['theme_slug'])) : '';
        if (!$theme_slug) {
            wp_send_json_error(['message' => esc_html__('Theme slug is missing.', 'animation-addons-for-elementor')]);
        }
        
        $active_theme = wp_get_theme();
        if ($active_theme->get_stylesheet() === $theme_slug) {
            wp_send_json_error(['message' => esc_html__('The theme is already active.', 'animation-addons-for-elementor')]);
        }       
        switch_theme($theme_slug);
        wp_send_json_success(['message' => esc_html__('The theme is active.', 'animation-addons-for-elementor')]);
        wp_die();
    }
    function ajax_install_theme() {
        // Verify nonce
        check_ajax_referer('wcf_admin_nonce', 'nonce');
    
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You are not allowed to do this action', 'animation-addons-for-elementor')]);
        }
    
        // Get the theme slug
        $theme_slug = isset($_POST['theme_slug']) ? sanitize_text_field(wp_unslash($_POST['theme_slug'])) : '';
        if (!$theme_slug) {
            wp_send_json_error(['message' => esc_html__('Theme slug is missing.', 'animation-addons-for-elementor')]);
        }
    
        // Check if the theme is already active
        $active_theme = wp_get_theme();
        if ($active_theme->get_stylesheet() === $theme_slug) {
            wp_send_json_error(['message' => esc_html__('The theme is already active.', 'animation-addons-for-elementor')]);
        }
    
        // Check if the theme is already installed
        $installed_themes = wp_get_themes();
        if (array_key_exists($theme_slug, $installed_themes)) {
            wp_send_json_error(['message' => esc_html__('The theme is already installed.', 'animation-addons-for-elementor')]);
        }
    
        // Include necessary WordPress files
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
    
        // Start output buffering
        ob_start();
    
        // Initialize Theme Upgrader
        $upgrader = new \Theme_Upgrader();
        $upgrader->init(); // Ensure upgrader is initialized properly
        $result = $upgrader->install("https://downloads.wordpress.org/theme/{$theme_slug}.zip");
        if (is_wp_error($result)) {
           echo "404 failed";
        }    
        if (!$result) {
            echo "404 failed";
        }    
        echo "200 ok";
        wp_die();
    }
    

	/**
	 * [add_menu] Admin Menu
	 */
	public function add_menu() {
	   
		add_submenu_page(
			'wcf_addons_page',
			esc_html__( 'Setup', 'animation-addons-for-elementor' ),
			esc_html__( 'Setup', 'animation-addons-for-elementor' ),
			self::MENU_CAPABILITY,
			self::MENU_PAGE_SLUG,
			[ $this, 'render_wizard' ]
		);
	
	}

	/**
	 * [enqueue_scripts] Add Scripts Base Menu Slug
	 *
	 * @param  [string] $hook
	 *
	 * @return [void]
	 */
	public function enqueue_scripts( $hook ) {
        $total_extensions = $total_widgets = 0;	
		if ( $hook == 'animation-addon_page_wcf_addons_setup_page' ) {

			// CSS
			wp_enqueue_style( 'wcf-admin', WCF_ADDONS_URL . 'inc/admin/dashboard/build/wizardSetup.css' );
			// JS
			wp_enqueue_script( 'wcf-admin', WCF_ADDONS_URL . 'inc/admin/dashboard/build/wizardSetup.js', array('wp-data', 'react', 'react-dom', 'wp-element' , 'wp-i18n' ), WCF_ADDONS_VERSION, true );

            wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['extensions'], $total_extensions);
			wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['widgets'], $total_widgets);

            $widgets       = get_option( 'wcf_save_widgets' );
			$saved_widgets = is_array($widgets) ? array_keys( $widgets ) : [];
			wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['widgets'], $saved_widgets, $foundKeys, $awidgets);
			
			$extensions      = get_option( 'wcf_save_extensions' );
			$saved_extensions = is_array($extensions) ? array_keys( $extensions ) : [];		  
            wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['extensions'], $saved_extensions, $foundext, $activeext);

            $active_widgets = self::get_widgets(); 
		    $active_ext = self::get_extensions(); 
            $current_user = wp_get_current_user();
			$localize_data = [
                'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'wcf_admin_nonce' ),
                'addons_config' => apply_filters( 'wcf_addons_dashboard_config' , $GLOBALS[ 'wcf_addons_config' ]),
                'extensions'    => [ 'total' => $total_extensions , 'active' => is_array($active_ext) ? count($active_ext): 0],
                'widgets'       => [ 'total' =>$total_widgets , 'active' => is_array($active_widgets) ? count($active_widgets): 0],
                'adminURL'      => admin_url(),
                'version'       => WCF_ADDONS_VERSION,
                'theme_status' => $this->theme_status('hello-animation'),
                'user' => [
                    'email'    => $current_user->user_email,
                    'roles'    => $current_user->roles,
                    'display_name' => $current_user->display_name,
                    'f_name' => $current_user->first_name
                ]
			];
			wp_localize_script( 'wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data );

		}
	}

	/**
	 * Save Settings
	 * Save EA settings data through ajax request
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function save_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['settings'] ) ) {
			return;
		}

		if ( empty( $_POST['settings'] ) ) {
			wp_send_json( esc_html__( 'Option name not found!', 'animation-addons-for-elementor' ) );
		}

		$all_settings = array_map( 'sanitize_text_field', wp_unslash( $_POST['settings'] ) );

		foreach ( $all_settings as $key => $setting ) {

			$option_name = $key;

			wp_parse_str( $setting, $settings );

			$settings = array_fill_keys( array_keys( $settings ), true );

			// update new settings
			if ( ! empty( $option_name ) ) {
				update_option( $option_name, $settings );
			}
		}

		update_option( 'wcf_addons_setup_wizard', 'complete' );
    
		wp_send_json_success( [ 'redirect_url' => esc_url( admin_url( 'admin.php?page=wcf_addons_settings' ) ) ] );

	}

	/**
	 * Render wizard
	 * @return [void]
	 */
	public function render_wizard() {
		?>
        <div class="wrap wcf-admin-wrapper" id="wcf-animation-addon-wizard">          
        </div>
		<?php
	}
	

	/**
	 * [remove_all_notices] remove addmin notices
	 * @return [void]
	 */
	public function remove_all_notices() {

		add_action('in_admin_header', function () {
			$screen = get_current_screen();		
			if ($screen && in_array($screen->id, ['animation-addon_page_wcf_addons_setup_page', 'animation-addon_page_wcf-cpt-builder'], true)) {
				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');
			}
		}, 1000);
		
	}

}

WCF_Setup_Wizard_Init::instance();
