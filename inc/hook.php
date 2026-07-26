<?php
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use Elementor\Plugin;

if (function_exists('wcf_set_postview')) {
   add_action('wp', 'wcf_set_postview');
}

function aae_handle_aae_post_shares_count()
{

    if (!isset($_POST['nonce'])) {
        exit('No naughty business please . Provide Security Code');
    }

    $nonce =  sanitize_text_field(wp_unslash($_POST['nonce']));

    if (! wp_verify_nonce($nonce, 'wcf-addons-frontend')) {
        exit('No naughty business please');
    }

    if (isset($_POST['post_id']) && isset($_POST['social'])) {
        $post_id = intval(sanitize_text_field(wp_unslash($_POST['post_id'])));
        $social = sanitize_text_field(wp_unslash($_POST['social']));

        // Retrieve current share count, increment it, or set it if it doesn't exist
        $current_shares = get_post_meta($post_id, 'aae_post_shares', true);

        if (! is_array($current_shares)) {
            $current_shares = [];
        }
        if (isset($current_shares[$social])) {
            $current_shares[$social]++;
        } else {
            $current_shares[$social] = 1;
        }

        $shares_count = array_sum(array_values($current_shares));

        foreach ($current_shares as $k => $single) {
            update_post_meta($post_id, 'aae_post_shares_' . $k, $single);
        }

        update_post_meta($post_id, 'aae_post_shares_count', $shares_count);
        update_post_meta($post_id, 'aae_post_shares', $current_shares);

        // Return updated share count as a response
        wp_send_json_success(array(
            'share_count' => $shares_count,
            'post_shares' => $current_shares
        ));

    } else {
        wp_send_json_error('Invalid post ID');
    }

}
add_action('wp_ajax_aae_post_shares', 'aae_handle_aae_post_shares_count'); // For logged-in users
add_action('wp_ajax_nopriv_aae_post_shares', 'aae_handle_aae_post_shares_count'); // For non-logged-in users

function aaeaddon_disable_comments_for_custom_post_type()
{
    remove_post_type_support('wcf-addons-template', 'comments');
}
add_action('init', 'aaeaddon_disable_comments_for_custom_post_type', 100);

function aaeaddon_custom_hide_admin_notices_for_specific_page()
{
    $screen = get_current_screen();
    // ist of admin pages where you want to disable notices
    $pages_to_hide_notices = array(
        'wcf-custom-fonts',
        'wcf-custom-icons',
        'animation-addon_page_wcf-cpt-builder',
        'edit-wcf-addons-template',
        'animation-addon_page_wcf_addons_settings',
        'animation-addon_page_wcf_addons_setup_page'
    );

    // Check if current screen ID matches any in the list
    if (in_array($screen->id, $pages_to_hide_notices)) {
        // Remove core and plugin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
}
add_action('admin_head', 'aaeaddon_custom_hide_admin_notices_for_specific_page');

// Global preset interactions — button presets driven by generic .aae-btn-gen
// markup (Text-flip, Border-divide, Group Swap, etc). Runs on the live
// frontend and inside the Elementor builder's preview iframe, since
// preset-driven markup can appear in either context. Free/pro tiers are
// compiled as separate bundles (see webpack.config.js -> getAtomicWidgetEntries).
if (!function_exists('aaeaddon_enqueue_global_preset_assets')) {
    function aaeaddon_enqueue_global_preset_assets()
    {
        $free_js  = 'assets/atomic/js/global-preset-free.js';
        $free_css = 'assets/atomic/js/global-preset-free.css';
        $pro_js   = 'assets/atomic/js/global-preset-pro.js';
        $pro_css  = 'assets/atomic/js/global-preset-pro.css';

        wp_enqueue_style(
            'aae-global-preset-free',
            WCF_ADDONS_URL . $free_css,
            [],
            file_exists(WCF_ADDONS_PATH . $free_css) ? filemtime(WCF_ADDONS_PATH . $free_css) : WCF_ADDONS_VERSION
        );

        wp_enqueue_script(
            'aae-global-preset-free',
            WCF_ADDONS_URL . $free_js,
            [],
            file_exists(WCF_ADDONS_PATH . $free_js) ? filemtime(WCF_ADDONS_PATH . $free_js) : WCF_ADDONS_VERSION,
            true
        );

        // TODO: gate behind the pro license check once one exists for this plugin.
        wp_enqueue_style(
            'aae-global-preset-pro',
            WCF_ADDONS_URL . $pro_css,
            [],
            file_exists(WCF_ADDONS_PATH . $pro_css) ? filemtime(WCF_ADDONS_PATH . $pro_css) : WCF_ADDONS_VERSION
        );

        // 'gsap' powers the Oval magnetic-movement effect (ovalMagneticSetup).
        // Declaring it as a dep is safe even when it's never registered (e.g.
        // Pro not installed) — WP just skips the missing handle, matching the
        // `typeof gsap === 'undefined'` guard already in global-preset-pro.js.
        wp_enqueue_script(
            'aae-global-preset-pro',
            WCF_ADDONS_URL . $pro_js,
            ['gsap'],
            file_exists(WCF_ADDONS_PATH . $pro_js) ? filemtime(WCF_ADDONS_PATH . $pro_js) : WCF_ADDONS_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'aaeaddon_enqueue_global_preset_assets');
add_action('elementor/preview/enqueue_scripts', 'aaeaddon_enqueue_global_preset_assets');
// post reaction ajax handeler

if (!function_exists('aaeaddon_post_lite_reaction_ajax')) {
    function aaeaddon_post_lite_reaction_ajax()
    {
        
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field( wp_unslash($_REQUEST['nonce']) ) : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcf-addons-frontend' ) ) {
            // For JSON endpoints:
            if ( defined('DOING_AJAX') && DOING_AJAX ) {
                wp_send_json_error(['message' => __('Invalid request.', 'animation-addons-for-elementor')], 403);
            }
            // For normal requests:
            wp_die( esc_html__('Invalid request.', 'animation-addons-for-elementor'), 403 );
        }

        $post_id = isset($_POST['post_id']) ? absint(sanitize_text_field( wp_unslash( $_POST['post_id'] ) )) : '';
        $reaction = isset($_POST['reaction']) ? sanitize_text_field(wp_unslash( $_POST['reaction'] )) : [];

        if (! $post_id || ! $reaction) {
            wp_send_json_error('Invalid data');
        }

        $reactions = get_post_meta($post_id, 'aaeaddon_post_reactions', true);
        if (! is_array($reactions)) {
            $reactions = [];
        }

        if (isset($reactions[$reaction])) {
            $reactions[$reaction]++;
        } else {
            $reactions[$reaction] = 1;
        }

        $reactions_count = array_sum(array_values($reactions));

        foreach ($reactions as $k => $single) {
            update_post_meta( $post_id, 'aaeaddon_post_reactions_' . $k, $single);
        }
        update_post_meta($post_id, 'aaeaddon_post_reactions', $reactions);
        update_post_meta($post_id, 'aaeaddon_post_total_reactions', $reactions_count);
        wp_send_json_success($reactions);
    }
    add_action('wp_ajax_nopriv_aaeaddon_post_reaction', 'aaeaddon_post_lite_reaction_ajax');
    add_action('wp_ajax_aaeaddon_post_reaction', 'aaeaddon_post_lite_reaction_ajax');
}









