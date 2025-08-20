<?php

namespace WCF_ADDONS\Admin;
/**
 * Plugin Name: AAE Admin Buttons
 * Description: Adds a custom button and loads JS on Pages list & Page edit screens.
 */

defined('ABSPATH') || exit;

final class AAE_Admin_Buttons {

    const HANDLE = 'aae-admin-actions';

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);    
    }

    /** Load JS only on the screens we care about */
    public function enqueue($hook_suffix) {
        if ( ! current_user_can('edit_pages') ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $should_load = false;
        $post_id     = 0;

        // 1) Pages list: edit.php + post_type=page
        if ($hook_suffix === 'edit.php' && $screen->post_type === 'page') {
            $should_load = true;
        }

        // 2) Single edit screen: post.php with post_type=page (optionally only a certain post ID)
        if ($hook_suffix === 'post.php' && $screen->post_type === 'page') {
            $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

            // If you only want for post 59, uncomment the next line:
            // $should_load = ($post_id === 59);

            // If you want for any page edit screen, use:
            $should_load = true;
        }

        if ( ! $should_load ) {
            return;
        }

        // Register + enqueue your JS
        wp_register_script(
            self::HANDLE,
            WCF_ADDONS_URL .'assets/js/aae-admin-actions.js',
            ['jquery'],
            time(),
            true
        );

        wp_localize_script(self::HANDLE, 'AAE_ADMIN', [
            'nonce'   => wp_create_nonce('aae_admin_nonce'),
            'screen'  => $screen->id,
            'post_id' => $post_id,
            'is_list' => ($hook_suffix === 'edit.php'),
        ]);

        wp_enqueue_script(self::HANDLE);
    }

}
if(is_admin()){
    new AAE_Admin_Buttons();
}

