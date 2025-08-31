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
        add_action( 'wp_ajax_aae_deactivate_feedback', [ $this, 'handle_deactivate_feedback' ] );
    }
    
    function _enqueue_admin_scripts($hook) {        
        if ($hook === 'plugins.php') {
            wp_enqueue_script('aaeaddon-plugin-deactivate', WCF_ADDONS_URL . 'assets/build/modules/dashboard/opt-out.js', [], time(), true);
            wp_enqueue_script('wcf-admin', WCF_ADDONS_URL . 'assets/js/wcf-admin.js', ['jquery'], null, true);
            wp_localize_script('aaeaddon-plugin-deactivate', 'aae_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aae_deactivate_feedback_nonce'),
                'logo_url' => WCF_ADDONS_URL . 'assets/images/aae-logo.png'
            ));          
            wp_enqueue_style('aae-plugins-styles', WCF_ADDONS_URL . 'assets/css/plugins.css', [], time(), 'all');
        }
    }

   

    /**
     * Handle deactivation feedback submission
     */
    public function handle_deactivate_feedback() {
        if (!isset($_POST['reason']) || !isset($_POST['other_text']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing parameters');
        }
        $nonce = sanitize_text_field(wp_unslash( $_POST['nonce'] ));
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'aae_deactivate_feedback_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check user permissions
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error('Permission denied');
        }

        $reason = sanitize_text_field(wp_unslash( $_POST['reason'] ));
        $other_text = sanitize_textarea_field(wp_unslash( $_POST['other_text'] ));

        // Log the feedback
        $feedback_data = array(
            'reason' => $reason,
            'other_text' => $other_text,
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url(),
            'timestamp' => current_time('mysql'),
            'plugin_version' => WCF_ADDONS_VERSION
        );

        // Store feedback in options
        $existing_feedback = get_option('aae_deactivation_feedback', array());
        $existing_feedback[] = $feedback_data;
        update_option('aae_deactivation_feedback', $existing_feedback);

        // Send feedback to external service (optional)
        $this->send_feedback_to_server($feedback_data);

        wp_send_json_success('Feedback submitted successfully');
    }

    /**
     * Send feedback to external server
     */
    private function send_feedback_to_server($feedback_data) {
        $api_url = 'https://data.animation-addons.com/wp-json/wmd/v1/feedback/deactivation';
        
        wp_remote_post($api_url, array(
            'timeout' => 5,
            'blocking' => false,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($feedback_data)
        ));
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
