<?php

namespace WCF_ADDONS\Admin;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
} // Exit if accessed directly

class AAEAddon_Row_Actions {

    /**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [AAEAddon_Row_Actions]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

    public function __construct() {
		add_filter( 'plugin_action_links', [ $this , 'add_plugin_link' ] , 10, 2 );		
        add_filter( 'plugin_row_meta', [ $this, '_plugin_row_meta' ], 10, 2 ); 
        add_action( 'admin_enqueue_scripts', [ $this , '_enqueue_admin_scripts' ] );    	
    }

    function _enqueue_admin_scripts($hook) {        
        if ($hook === 'plugins.php') {
            wp_enqueue_script('aaeaddon-plugin-deactivate', plugin_dir_url( __FILE__ ) . 'dashboard/build/optout.js', [], null, true);
        }
    }

    function _plugin_row_meta( $meta, $plugin_file ) {
        if ( basename(WCF_ADDONS_BASE) !== basename($plugin_file) ) {
			return $meta;
		}
        
        $meta[] = '<a href="https://animation-addons.com/docs/" target="_blank">' . esc_html__('Documentation', 'animation-addons-for-elementor' ) . '</a>';
        $meta[] = '<a href="https://crowdyflow.ticksy.com/submit/" target="_blank">' . esc_html__('Support', 'animation-addons-for-elementor') . '</a>';
        if ( !file_exists( WP_PLUGIN_DIR . '/' . 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php' ) ) {
            $meta[] = '<a href="https://animation-addons.com" style="color:#ff7a00; font-weight: bold;" target="_blank">' . esc_html__('Upgrade to Pro', 'animation-addons-for-elementor') . '</a>';
        }
		$meta[] = '<a href="https://wordpress.org/support/plugin/animation-addons-for-elementor/reviews/#new-post" target="_blank">' . esc_html__(' Rate the plugin ★★★★★', 'animation-addons-for-elementor' ) . '</a>';
        return $meta;
    }

    /**
	 * Add settings link to plugin actions
	 *
	 * @param  array  $plugin_actions
	 * @param  string $plugin_file
	 * @since  1.0
	 * @return array
	 */
	function add_plugin_link( $plugin_actions, $plugin_file ) {
	
	    $new_actions = array();	   
	    if ( basename(WCF_ADDONS_BASE) === basename($plugin_file) ) {
			$new_actions['aaeaddon-dsb-settings'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=wcf_addons_settings' ) ),
				esc_html__('Settings', 'animation-addons-for-elementor' )
			);
			
	    }
	
	    return array_merge( $new_actions, $plugin_actions );
	}

}

new AAEAddon_Row_Actions();
