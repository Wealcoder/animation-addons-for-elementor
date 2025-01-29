<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if(function_exists('wcf_set_postview')){
	add_filter( 'single_template', 'wcf_set_postview' );
}

function aae_handle_aae_post_shares_count() {

	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'wcf-addons-frontend' ) ) {
		exit( 'No naughty business please' );
	}
	
    if ( isset( $_POST['post_id'] ) ) {
        $post_id = intval( $_POST['post_id'] );
        
        // Retrieve current share count, increment it, or set it if it doesn't exist
        $current_shares = get_post_meta( $post_id, 'aae_post_shares', true );
        $current_shares = is_numeric( $current_shares ) ? $current_shares : 0;
        $new_share_count = $current_shares + 1;

        // Update share count in post meta
        update_post_meta( $post_id, 'aae_post_shares', $new_share_count );

        // Return updated share count as a response
        wp_send_json_success( array(
            'share_count' => $new_share_count
        ) );
    } else {
        wp_send_json_error( 'Invalid post ID' );
    }
}
add_action( 'wp_ajax_aae_post_shares', 'aae_handle_aae_post_shares_count' ); // For logged-in users
add_action( 'wp_ajax_nopriv_aae_post_shares', 'aae_handle_aae_post_shares_count' ); // For non-logged-in users
