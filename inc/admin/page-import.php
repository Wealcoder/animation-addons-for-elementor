<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

/**
 * Plugin Name: AAE Admin Buttons
 * Description: Adds a custom button and loads JS on Pages list & Page edit screens.
 */

defined('ABSPATH') || exit;

final class AAE_Admin_Page_Importer
{

    const HANDLE = 'aae-admin-actions';

    public function __construct()
    {

        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_enqueue_scripts', [$this, 'importer_assets']);

        add_action('admin_menu', array($this, 'add_menu'), 25);

        add_action('admin_print_scripts', [$this, 'clear_notices_for_importer']);
        add_filter('admin_body_class', array($this, 'admin_classes'), 100);
        add_filter('views_edit-page', [$this, 'custom_page_tab']);
        add_action('pre_get_posts', [$this, 'custom_page_filter']);
    }

    function custom_page_tab($views)
    {
        global $wpdb;

        // Cache the imported-page count; this renders on every admin pages-list load.
        $count = wp_cache_get('aae_imported_page_count', 'aae_page_import');
        if (false === $count) {
            // Fixed aggregate query with no user input; $wpdb is required for the COUNT.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $count = $wpdb->get_var("
                SELECT COUNT(*) FROM $wpdb->posts
                WHERE post_type = 'page'
                AND post_status = 'publish'
                AND ID IN (
                    SELECT post_id FROM $wpdb->postmeta
                    WHERE meta_key = 'aae_imported' AND meta_value = '1'
                )
            ");
            wp_cache_set('aae_imported_page_count', $count, 'aae_page_import', MINUTE_IN_SECONDS);
        }

        // Read-only list-table view filter from a navigation link; no nonce required.
        $current_view = isset($_GET['aae-latest-import']) ? sanitize_key(wp_unslash($_GET['aae-latest-import'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $class        = ('import' === $current_view) ? 'current' : '';
        $url   = add_query_arg('aae-latest-import', 'import', admin_url('edit.php?post_type=page'));

        // Built with sprintf rather than interpolated: this is returned into $views,
        // which the list table echoes as-is -- it escapes nothing on our behalf. The
        // label goes through esc_html__ because a string written straight into the
        // markup here cannot be translated, and this one never was.
        $views['latest-import'] = sprintf(
            '<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$s)</span></a>',
            esc_url($url),
            esc_attr(trim($class . ' aae-imported-view')),
            esc_html__('AAE Imported', 'animation-addons-for-elementor'),
            esc_html(number_format_i18n($count))
        );
        return $views;
    }
    function custom_page_filter($query)
    {
        global $pagenow;

        if (is_admin() && $pagenow == 'edit.php' && $query->get('post_type') == 'page') {
            // Read-only list-table view filter from a navigation link; no nonce required.
            $current_view = isset($_GET['aae-latest-import']) ? sanitize_key(wp_unslash($_GET['aae-latest-import'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ('import' === $current_view) {
                $query->set('meta_key', 'aae_imported');
                $query->set('meta_value', '1');
            }
        }
    }
    public function clear_notices_for_importer()
    {
        $screen = get_current_screen();

       if ($screen && strpos($screen->id, '_page_aae-page-importer') !== false) {
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
        if ($screen && strpos($screen->id, '_page_aae-page-importer') !== false) {
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
            'wcf_addons_page',                 // 👈 null keeps it hidden from UI
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
        if ($hook_suffix === 'edit.php' && $screen->post_type === 'page') {

            $should_load = true;
        }

        if (! $should_load) {
            return;
        }

        // Register + enqueue your JS
        wp_register_script(
            self::HANDLE,
            WCF_ADDONS_URL . 'assets/js/aae-admin-actions.min.js',
            ['jquery'],
            wcf_asset_version(),
            true
        );

        $is_importer_page = ( $screen &&  strpos($screen->id, '_page_aae-page-importer') !== false );

        wp_localize_script(self::HANDLE, 'AAE_PAGE_IMPORT', [
            'nonce'    => wp_create_nonce('aae_admin_nonce'),
            'screen'   => $is_importer_page ? 'animation-addon_page_aae-page-importer' : '',
            'post_id'  => $post_id,
            'logo'     => WCF_ADDONS_URL . 'assets/images/wcf-2.png',
            'label'    => __('Import Page', 'animation-addons-for-elementor'),
            'page_url' => esc_url(admin_url('admin.php?page=aae-page-importer')),
        ]);

        wp_enqueue_script(self::HANDLE);

        // The button is injected next to "Add Page" by aae-admin-actions.js; its look
        // is owned here so it reads as a brand action rather than a bare link.
        wp_add_inline_style('wp-admin', $this->heading_button_css());
    }

    /**
     * Styles for the "Import Page" action on the Pages list.
     *
     * Rides on `.page-title-action` for size and alignment (so it sits level with
     * "Add Page" on every WordPress version) and overrides only colour and shape.
     */
    private function heading_button_css(): string
    {
        return '
        .wrap .page-title-action.aae-import-page-action {
            margin-left: 6px;
            padding-left: 10px;
            padding-right: 12px;
            border: 1px solid #fc6848;
            border-radius: 4px;
            background: #fc6848;
            color: #fff;
            font-weight: 500;
            box-shadow: none;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease;
        }
        .wrap .page-title-action.aae-import-page-action img {
            width: 16px;
            height: 16px;
            margin-right: 6px;
            vertical-align: -4px;
        }
        .wrap .page-title-action.aae-import-page-action:hover,
        .wrap .page-title-action.aae-import-page-action:focus {
            background: #e85a3c;
            border-color: #e85a3c;
            color: #fff;
        }
        .wrap .page-title-action.aae-import-page-action:focus {
            box-shadow: 0 0 0 1px #fff, 0 0 0 3px #fc6848;
            outline: 2px solid transparent;
        }
        .wrap .page-title-action.aae-import-page-action:active {
            background: #d4502f;
            border-color: #d4502f;
        }
        .subsubsub a.aae-imported-view,
        .subsubsub a.aae-imported-view .count {
            color: #fc6848;
            font-weight: 500;
        }
        ';
    }

   public function importer_assets($hook)
    {
        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        if (strpos($screen->id, '_page_aae-page-importer') !== false) {

            // Load config once
            $config = wcf_get_config();

            // CSS
            wp_enqueue_style(
                'aae-page-importer-admin',
                WCF_ADDONS_URL . 'assets/build/modules/page-import/index.css',
                array(),
                wcf_asset_version()
            );

            wp_enqueue_script(
                'aae-page-importer-admin',
                WCF_ADDONS_URL . 'assets/build/modules/page-import/index.js',
                array('wp-element', 'wp-i18n'),
                wcf_asset_version(),
                true
            );

            $localize_data = array(
                'ajaxurl'      => admin_url('admin-ajax.php'),
                'nonce'        => wp_create_nonce('wcf_admin_nonce'),

                'addons_config' => apply_filters(
                    'wcf_addons_dashboard_config',  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                    $config
                ),

                'adminURL'     => admin_url(),
                'page_url'     => esc_url(admin_url('edit.php?post_type=page')),
                'user_role'    => wcfaddon_get_current_user_roles(),

                'version'            => WCF_ADDONS_VERSION,
                'st_template_domain' => WCF_TEMPLATE_STARTER_BASE_URL,
                'home_url'           => home_url('/'),
            );

            wp_localize_script('aae-page-importer-admin', 'WCF_ADDONS_ADMIN', $localize_data);
        }
    }

}
if (is_admin()) {
    new AAE_Admin_Page_Importer();
}
