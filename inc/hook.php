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

/**
 * A stable per-visitor key for the public counters (shares/reactions).
 *
 * HASHED, never a raw IP — the raw address is not stored anywhere, only used
 * to salt a one-way hash (auth-salted via wp_hash), matching the plugin's
 * "no raw IP without opt-in" rule. Good enough to stop trivial inflation from
 * one client; it is not identity, and is not meant to be.
 */
if (! function_exists('aae_public_counter_visitor_key')) {
    function aae_public_counter_visitor_key()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';

        return substr(wp_hash($ip . '|' . $ua), 0, 16);
    }
}

/**
 * Has this visitor already counted `$bucket` on `$post_id` within the window?
 *
 * These endpoints are public (wp_ajax_nopriv) and authorised only by the
 * shared front-end nonce every visitor holds, so without this a single client
 * — or a bot — can increment a post's share/reaction meta without limit, and
 * on ANY post id. The caller validates the post; this throttles the write to
 * once per visitor per bucket per window. Returns true when the write should
 * be SKIPPED (already counted), false when it is fresh (and marks it counted).
 *
 * @param int    $post_id Target post.
 * @param string $bucket  What is being counted, e.g. 'reaction' or 'share:facebook'.
 * @param int    $window  Seconds the vote is remembered for.
 * @return bool
 */
if (! function_exists('aae_public_counter_throttled')) {
    function aae_public_counter_throttled($post_id, $bucket, $window = HOUR_IN_SECONDS)
    {
        $key = 'aae_pc_' . md5($post_id . '|' . $bucket . '|' . aae_public_counter_visitor_key());

        if (false !== get_transient($key)) {
            return true;
        }

        set_transient($key, 1, $window);

        return false;
    }
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

        // The target must be a real, published post — otherwise this writes
        // share meta onto arbitrary or non-existent ids.
        $post = get_post($post_id);
        if (! $post || 'publish' !== $post->post_status) {
            wp_send_json_error('Invalid post ID');
        }

        // Retrieve current share count, increment it, or set it if it doesn't exist
        $current_shares = get_post_meta($post_id, 'aae_post_shares', true);

        if (! is_array($current_shares)) {
            $current_shares = [];
        }

        // One count per visitor per network per window — a repeat is a graceful
        // no-op returning the current totals, so the UI still reflects state
        // without letting one client inflate the number.
        if (aae_public_counter_throttled($post_id, 'share:' . $social)) {
            wp_send_json_success(array(
                'share_count' => array_sum(array_values($current_shares)),
                'post_shares' => $current_shares,
                'counted'     => false,
            ));
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

// Btn / BtnPro / Social Share preset interactions used to load unconditionally
// on every page via a shared "global preset" bundle. They're now registered
// like any other atomic widget (has_script/style_handle in class-atomic.php's
// widget table under 'aae-a-btn', 'aae-a-btn-pro', 'aae-a-social-share') and
// load on-demand — only enqueued when that specific element type actually
// renders on the page (register_atomic_scripts/register_atomic_styles +
// maybe_enqueue_widget_script), same as Button's and SocialShareMain's own
// bundles. The editor preview iframe still gets them blanket-enqueued via
// the existing generic enqueue_widget_scripts_in_preview() hook, so no
// separate wiring is needed here.
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

        // Real, published post only — no reaction meta on arbitrary ids.
        $post = get_post($post_id);
        if (! $post || 'publish' !== $post->post_status) {
            wp_send_json_error('Invalid data');
        }

        $reactions = get_post_meta($post_id, 'aaeaddon_post_reactions', true);
        if (! is_array($reactions)) {
            $reactions = [];
        }

        // One reaction per visitor per post per window; a repeat returns the
        // current tally unchanged instead of inflating it.
        if (aae_public_counter_throttled($post_id, 'reaction')) {
            wp_send_json_success($reactions);
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









