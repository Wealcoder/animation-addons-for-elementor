<?php
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use Elementor\Plugin;

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
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

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

/**
 * Should views by logged-in users be counted? Pro turns this off.
 */
function aae_post_views_count_logged_in()
{
    return (bool) apply_filters('aae_post_views_count_logged_in', true);
}

/**
 * Post view counter endpoint, hit by the beacon printed in the footer below.
 *
 * Views used to be counted on `wp` during the page render — a meta write on
 * every single view, double-counted when Pro ran its own counter, and never
 * counted at all once a page cache served the HTML. Counting here keeps the
 * render write-free and cacheable.
 *
 * No nonce on purpose: the beacon ships inside cached HTML, where a nonce
 * goes stale. The post is validated and the write throttled per visitor
 * instead, same as the share/reaction counters.
 */
function aae_handle_post_view()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public, cache-safe counter; see above.
    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;

    if (is_user_logged_in() && ! aae_post_views_count_logged_in()) {
        wp_send_json_success(['counted' => false]);
    }

    // Real, published, publicly viewable post of a counted type only.
    $post = $post_id ? get_post($post_id) : null;
    if (
        ! $post
        || 'publish' !== $post->post_status
        || ! is_post_type_viewable($post->post_type)
        || ! in_array($post->post_type, wcf_post_views_post_types(), true)
    ) {
        wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'animation-addons-for-elementor')], 400);
    }

    // One view per visitor per post per hour (the client dedupes too).
    if (aae_public_counter_throttled($post_id, 'view')) {
        wp_send_json_success(['counted' => false]);
    }

    wcf_set_postview($post_id);

    wp_send_json_success(['counted' => true]);
}
add_action('wp_ajax_aae_post_view', 'aae_handle_post_view');
add_action('wp_ajax_nopriv_aae_post_view', 'aae_handle_post_view');

/**
 * Print the view beacon on counted single views.
 *
 * Inline and tiny, so it needs no enqueue. Dedupes per post per hour in
 * localStorage (replacing the old server-set cookie, whose Set-Cookie header
 * stopped page caches from storing the page), skips headless/automated
 * browsers, and sends via sendBeacon with a fetch keepalive fallback.
 */
function aae_print_post_view_beacon()
{
    if (! is_singular(wcf_post_views_post_types()) || is_preview()) {
        return;
    }

    if (is_user_logged_in() && ! aae_post_views_count_logged_in()) {
        return;
    }

    // Elementor's editor preview renders the post too — not a view.
    if (class_exists('\Elementor\Plugin') && isset(Plugin::$instance->preview) && Plugin::$instance->preview->is_preview_mode()) {
        return;
    }

    $post_id = get_queried_object_id();
    if (! $post_id) {
        return;
    }

    $script = sprintf(
        '(function(){var id=%1$d,k="aae_pv_"+id,n=Date.now();if(navigator.webdriver)return;'
        . 'try{var t=+localStorage.getItem(k);if(t&&n-t<36e5)return;localStorage.setItem(k,n);}catch(e){}'
        . 'var u=%2$s,d=new FormData();d.append("action","aae_post_view");d.append("post_id",id);'
        . 'if(!(navigator.sendBeacon&&navigator.sendBeacon(u,d))&&window.fetch){fetch(u,{method:"POST",body:d,credentials:"same-origin",keepalive:true});}})();',
        $post_id,
        wp_json_encode(admin_url('admin-ajax.php'))
    );

    wp_print_inline_script_tag($script, ['id' => 'aae-post-view']);
}
add_action('wp_footer', 'aae_print_post_view_beacon');

function aae_handle_aae_post_shares_count()
{
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';

    if (! $nonce || ! wp_verify_nonce($nonce, 'wcf-addons-frontend')) {
        wp_send_json_error(['message' => esc_html__('Security check failed.', 'animation-addons-for-elementor')], 403);
    }

    if (isset($_POST['post_id']) && isset($_POST['social'])) {
        $post_id = absint(sanitize_text_field(wp_unslash($_POST['post_id'])));
        $social  = sanitize_key(wp_unslash($_POST['social']));

        // The vendor list is a fixed SELECT in the Social Share widget, and
        // this value becomes a POST META KEY. Left open, one unauthenticated
        // client can mint an unbounded number of distinct meta rows on a post
        // nobody is actually sharing. Filterable, so adding a network stays a
        // one-line change rather than a reason to leave it open.
        $allowed_social = apply_filters(
            'aae_post_share_networks',
            array( 'facebook', 'twitter', 'linkedin', 'pinterest', 'tumblr', 'blogger', 'reddit' )
        );

        if (! in_array($social, (array) $allowed_social, true)) {
            wp_send_json_error(['message' => esc_html__('Invalid network.', 'animation-addons-for-elementor')]);
        }

        // The target must be a real, published post — otherwise this writes
        // share meta onto arbitrary or non-existent ids.
        $post = get_post($post_id);
        if (! $post || 'publish' !== $post->post_status) {
            wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'animation-addons-for-elementor')]);
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
        wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'animation-addons-for-elementor')]);
    }

}
add_action('wp_ajax_aae_post_shares', 'aae_handle_aae_post_shares_count'); // For logged-in users
add_action('wp_ajax_nopriv_aae_post_shares', 'aae_handle_aae_post_shares_count'); // For non-logged-in users

function aaeaddon_disable_comments_for_custom_post_type()
{
    remove_post_type_support('wcf-addons-template', 'comments');
}
add_action('init', 'aaeaddon_disable_comments_for_custom_post_type', 100);

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

        // Same reason as the share vendors: this value becomes a meta key,
        // and the widget's Type control is a fixed SELECT — anything else
        // arriving here was not sent by the widget.
        $allowed_reactions = apply_filters(
            'aae_post_reaction_types',
            array( 'emoji', 'like', 'dislike', 'funny', 'wow', 'love', 'sad', 'angry' )
        );

        if (! in_array($reaction, (array) $allowed_reactions, true)) {
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









