<?php

namespace WCF_ADDONS\Admin;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
} // Exit if accessed directly

class WCF_Plugin_Installer {

    public function __construct( $reload = false ) {
		if(!$reload){
			add_action('wp_ajax_wcf_install_plugin', [$this, 'ajax_install_plugin']);
			add_action('wp_ajax_wcf_active_plugin', [$this, 'ajax_activate_plugin']);
			add_action('wp_ajax_activate_from_editor_plugin', [$this, 'activate_from_editor_plugin']);
			add_action('wp_ajax_wcf_deactive_plugin', [$this, 'ajax_deactivate_plugin']);				
		}     	
    }

    /**
     * Install a plugin from a given source.
     *
     * @param string $slug   Plugin slug or download URL.
     * @param string $source Plugin source: 'wordpress' or 'self_hosted'.
     * @param bool   $active Activate plugin after installation.
     * 
     * @return mixed WP_Error | bool
     */
    public function install_plugin($slug = '', $source = '', $active = false) {
        if (empty($slug) || empty($source)) {
            return new WP_Error('empty_arg', __('Arguments should not be empty.', 'animation-addons-for-elementor'));
        }

        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

        $download_link = '';
        
        // Handle source conditions
        if ($source === 'wordpress') {
            $plugin_data = $this->get_remote_plugin_data($slug, $source);
            if (is_wp_error($plugin_data)) {
                return $plugin_data;
            }
            $download_link = $plugin_data->download_link;
        } elseif ($source === 'self_host') {
            if (filter_var($slug, FILTER_VALIDATE_URL)) {
                $download_link = $slug;
            } else {
                return new WP_Error('invalid_url', __('Invalid download URL.', 'animation-addons-for-elementor'));
            }

            // Validate the file type
            $file_headers = wp_remote_head($download_link);
            if (is_wp_error($file_headers) || !isset($file_headers['headers']['content-type']) || strpos($file_headers['headers']['content-type'], 'application/zip') === false) {
                return new WP_Error('invalid_file_type', __('Provided URL is not a valid plugin ZIP file.', 'animation-addons-for-elementor'));
            }
        } else {
            return new WP_Error('invalid_source', __('Invalid source specified.', 'animation-addons-for-elementor'));
        }

        // Perform plugin installation
        $upgrader = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());
        $install = $upgrader->install($download_link);

        if (is_wp_error($install)) {
            return $install;
        }

        // Activate plugin if requested
        if ($install === true && $active) {
			if($source === 'self_host'){
				$activate = activate_plugin($upgrader->plugin_info());
			}else{
				$activate = activate_plugin($upgrader->plugin_info(), '', false, true);
			}
           
            if (is_wp_error($activate)) {
                return $activate;
            }
            return $activate === null;
        }

        return $install;
    }

    public function ajax_install_plugin() {
        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $slug   = isset($_POST['plugin_url']) ? esc_url_raw($_POST['plugin_url']) : '';
        $source = isset($_POST['plugin_source']) ? sanitize_text_field($_POST['plugin_source']) : '';

        $result = $this->install_plugin($slug, $source);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Plugin installed successfully!', 'animation-addons-for-elementor'));
    }

    public function ajax_activate_plugin() {
        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $basename = isset($_POST['action_base']) ? sanitize_text_field($_POST['action_base']) : '';
        $result = activate_plugin($basename, '', false, true);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Plugin activated successfully!', 'animation-addons-for-elementor'));
    }

    public function activate_from_editor_plugin() {
        check_ajax_referer('wcf-template-library', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $basename = isset($_POST['action_base']) ? sanitize_text_field($_POST['action_base']) : '';
        $result = activate_plugin($basename, '', false, true);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Plugin activated successfully!', 'animation-addons-for-elementor'));
    }

    public function ajax_deactivate_plugin() {
        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $basename = isset($_POST['action_base']) ? sanitize_text_field($_POST['action_base']) : '';
        $result = deactivate_plugins([$basename], true);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Plugin deactivated successfully!', 'animation-addons-for-elementor'));
    }

    private function get_remote_plugin_data($slug = '', $source = '') {
        if (empty($slug) || empty($source)) {
            return new WP_Error('empty_arg', __('Arguments should not be empty.', 'animation-addons-for-elementor'));
        }

        if ('wordpress' === $source) {
            $response = wp_remote_post(
                'http://api.wordpress.org/plugins/info/1.0/',
                [
                    'body' => [
                        'action'  => 'plugin_information',
                        'request' => serialize((object) [
                            'slug'   => $slug,
                            'fields' => ['version' => false],
                        ]),
                    ],
                ]
            );

            if (is_wp_error($response)) {
                return $response;
            }

            $data = unserialize(wp_remote_retrieve_body($response));

            if (!$data) {
                return new WP_Error('plugin_data_error', __('Failed to fetch plugin data.', 'animation-addons-for-elementor'));
            }

            return $data;
        }

        return new WP_Error('invalid_source', __('Unsupported source.', 'animation-addons-for-elementor'));
    }
}

new WCF_Plugin_Installer();
