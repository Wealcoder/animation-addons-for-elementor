<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Plugin;

if(function_exists('wcf_set_postview')){
	add_action( 'wp_head', 'wcf_set_postview' );
}

function aaeaddonlight_hk_allow_svg_uploads($mimes) {
    // Allow SVG files
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml'; // Compressed SVG
    return $mimes;
}

add_filter('upload_mimes', 'aaeaddonlight_hk_allow_svg_uploads');

function aae_handle_aae_post_shares_count() {
    if(!isset($_POST['nonce'])){
        exit( 'No naughty business please . Provide Security Code' );  
    }
    $nonce =  sanitize_text_field( wp_unslash($_POST['nonce']));
	if ( ! wp_verify_nonce( $nonce , 'wcf-addons-frontend' ) ) {
		exit( 'No naughty business please' );
	}
	
    if ( isset( $_POST['post_id'] ) && isset($_POST['social'])) {
        $post_id = intval( sanitize_text_field( wp_unslash($_POST['post_id'])) );
        $social = sanitize_text_field( wp_unslash($_POST['social']) );
        
        // Retrieve current share count, increment it, or set it if it doesn't exist
        $current_shares = get_post_meta( $post_id, 'aae_post_shares', true );
        if ( ! is_array( $current_shares ) ) {
            $current_shares = [];
        }
        if ( isset( $current_shares[ $social ] ) ) {
            $current_shares[ $social ]++;
        } else {
            $current_shares[ $social ] = 1;
        }
        
        $shares_count = array_sum( array_values($current_shares) );
	
    	foreach($current_shares as $k=> $single){
    		update_post_meta( $post_id, 'aae_post_shares_'.$k, $single );
    	}
	
        update_post_meta( $post_id, 'aae_post_shares_count', $shares_count );
        update_post_meta( $post_id, 'aae_post_shares', $current_shares );      

        // Return updated share count as a response
        wp_send_json_success( array(
            'share_count' => $shares_count,
            'post_shares' => $current_shares
        ) );
    } else {
        wp_send_json_error( 'Invalid post ID' );
    }
}
add_action( 'wp_ajax_aae_post_shares', 'aae_handle_aae_post_shares_count' ); // For logged-in users
add_action( 'wp_ajax_nopriv_aae_post_shares', 'aae_handle_aae_post_shares_count' ); // For non-logged-in users

function aaeaddon_disable_comments_for_custom_post_type() {
    remove_post_type_support( 'wcf-addons-template', 'comments' );
}
add_action( 'init', 'aaeaddon_disable_comments_for_custom_post_type' , 100);

function aaeaddon_custom_hide_admin_notices_for_specific_page() {   
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

