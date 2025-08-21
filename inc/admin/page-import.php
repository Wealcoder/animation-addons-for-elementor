<?php

namespace WCF_ADDONS\Admin;

/**
 * Plugin Name: AAE Admin Buttons
 * Description: Adds a custom button and loads JS on Pages list & Page edit screens.
 */

defined('ABSPATH') || exit;

final class AAE_Admin_Buttons
{

    const HANDLE = 'aae-admin-actions';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_enqueue_scripts', [$this, 'importer_assets']);

        add_action('admin_menu', array($this, 'add_menu'), 25);
        add_action('admin_print_scripts', [$this, 'clear_notices_for_importer']);
        add_filter('admin_body_class', array($this, 'admin_classes'), 100);
    }
    public function clear_notices_for_importer()
    {
        $screen = get_current_screen();

        if ($screen && $screen->id === 'admin_page_aae-page-importer') {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
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
        if ($screen && $screen->id === 'admin_page_aae-page-importer') {
            $classes .= ' wcf-anim2024';
        }

        return $classes;
    }

    /**
     * [add_menu] Admin Menu
     */
    public function add_menu()
    {
        if (! (current_user_can('manage_options'))) {
            return;
        }
        add_submenu_page(
            null,                   // 👈 null keeps it hidden from UI
            'Page Import',          // Page title
            'Page Import',          // Menu title (ignored since it's hidden)
            'manage_options',       // Capability
            'aae-page-importer',       // Slug
            [$this, 'page_html']   // Callback
        );
    }

    function page_html()
    {
        echo '<div id="aae-page-importer"></div>';
    }

    /** Load JS only on the screens we care about */
    public function enqueue($hook_suffix)
    {
        if (! current_user_can('edit_pages')) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        $should_load = false;
        $post_id     = 0;

        // 1) Pages list: edit.php + post_type=page
        if ($screen->post_type === 'page') {
            $should_load = true;
        }

        // 2) Single edit screen: post.php with post_type=page (optionally only a certain post ID)
        if ($hook_suffix === 'post.php' && $screen->post_type === 'page') {
            $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
            $should_load = true;
        }

        if (! $should_load) {
            return;
        }

        // Register + enqueue your JS
        wp_register_script(
            self::HANDLE,
            WCF_ADDONS_URL . 'assets/js/aae-admin-actions.js',
            ['jquery'],
            time(),
            true
        );

        wp_localize_script(self::HANDLE, 'AAE_ADMIN', [
            'nonce'   => wp_create_nonce('aae_admin_nonce'),
            'screen'  => $screen->id,
            'post_id' => $post_id,
            'page_url'   => esc_url(admin_url('admin.php?page=aae-page-importer')),
        ]);

        wp_enqueue_script(self::HANDLE);
    }

    public function importer_assets($hook)
    {

        if ($hook == 'admin_page_aae-page-importer') {
            // CSS
            wp_enqueue_style(
                'aae-page-importer-admin', // Handle for the stylesheet
                WCF_ADDONS_URL . 'assets/build/modules/page-import/index.css',
                array(), // Dependencies (none in this case)
                time()
            );
            wp_enqueue_script('aae-page-importer-admin', WCF_ADDONS_URL . 'assets/build/modules/page-import/index.js', array('wp-element', 'wp-i18n'), time(), true);
            $localize_data = array(
                'ajaxurl'             => admin_url('admin-ajax.php'),
                'nonce'               => wp_create_nonce('wcf_admin_nonce'),
                'addons_config'       => apply_filters('wcf_addons_dashboard_config', $GLOBALS['wcf_addons_config']),
                'adminURL'            => admin_url(),
                'page_url'   => esc_url(admin_url('edit.php?post_type=page')),
                'user_role'           => wcfaddon_get_current_user_roles(),
                'version'             => WCF_ADDONS_VERSION,
                'st_template_domain'  => WCF_TEMPLATE_STARTER_BASE_URL,
                'home_url'            => home_url('/'),
            );

            wp_localize_script('aae-page-importer-admin', 'WCF_ADDONS_ADMIN', $localize_data);
        }
    }
}
if (is_admin()) {
    new AAE_Admin_Buttons();
}
