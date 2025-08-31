<?php
/**
 * Animation Addons for Elementor — Dashboard Idle Image Cache Prefetch
 *
 * Drops into your plugin and enqueues a tiny JS that waits for user inactivity
 * (keyboard/mouse idle) on admin screens, then fetches your template library API
 * and warms browser cache for discovered image URLs. Progress is tracked in
 * localStorage so each day it only warms once (configurable).
 */

namespace WCF_ADDONS\Admin\Dashboard;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Editor_Image_Preload {

    const HANDLE  = 'aae-editor-image-preload';
    const VERSION = '1.2.0';

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue'] );
    }

    public function permission($hook) {
          // Allowlist of admin pages where script should run
        $allowed_hooks = [
            'plugins.php',                     // Dashboard
            'index.php',                     // Dashboard
            'edit.php',                      // Posts & Pages list
            'post.php',                      // Post/Page editor
            'post-new.php',                  // New Post/Page editor
            'animation-addon_page_wcf_addons_settings', // /admin.php?page=wcf_addons_settings
            'themes.php',                    // Themes screen
        ];       
  
        if (in_array($hook, $allowed_hooks)) {          
            return true;
        }

        return false;
    }

    public function enqueue($hook) {
        if (!$this->permission($hook)) {
            return; // Not allowed on this admin page
        }
       
        wp_register_script(
            self::HANDLE,
            WCF_ADDONS_URL .'assets/js/image-cache.js',
            [],
            time(),
            true
        );

      wp_localize_script(self::HANDLE, 'AAE_EDITOR_PRELOAD', [
            'idleMs'         => 6000,
            'maxConcurrency' => 8,
            'simulateScroll' => true,
            'scrollStep'     => 800,
            'dailyOnce'      => true,
            'apiUrls'        => [
                'https://block.animation-addons.com/wp-json/wp/v2/wcf-templates?page=1&per_page=100&subtype=block',
                'https://www.themecrowdy.com/wp-json/wp/v2/starter-templates?page=1&per_page=40',
            ],
            'debug'          => apply_filters('aae_editor_preload_debug', false),
        ]);


        wp_enqueue_script(self::HANDLE);

        $uploads = wp_get_upload_dir();
        $hosts   = [];
        if (!empty($uploads['baseurl'])) {
            $hosts[] = wp_parse_url($uploads['baseurl'], PHP_URL_HOST);
        }
        $hosts[] = wp_parse_url('https://block.animation-addons.com', PHP_URL_HOST);
        $hosts   = array_unique(array_filter($hosts));

        add_action('admin_print_styles', function() use ($hosts) {
            foreach ($hosts as $h) {
                printf('<link rel="dns-prefetch" href="//%s">' . "\n", esc_attr($h));
                printf('<link rel="preconnect" href="https://%s" crossorigin>' . "\n", esc_attr($h));
            }
        });
    }
}

new Editor_Image_Preload();
  
