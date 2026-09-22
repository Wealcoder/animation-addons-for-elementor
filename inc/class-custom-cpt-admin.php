<?php

namespace Wealcoder\AnimationAddons\Extensions;

use Wealcoder\AnimationAddons\Nonce;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * The Post Type Builder extension's wp-admin half.
 *
 * Split out of class-custom-cpt.php. The CPT/taxonomy REGISTRATION machinery
 * (register_cpt / register_taxonomes / setup_post_type / generate_* / latest_data
 * / get_post_type / refresh_registrations / the import re-register) stays on
 * CustomCpt_Lite -- it runs on init on every front-end request. This file holds
 * only the builder screen: the admin menu, its enqueue, and the ten admin-ajax
 * writers/readers. CustomCpt_Lite requires it under is_admin(); admin-ajax IS
 * is_admin(), so the writers still answer. The handlers reach the shared
 * read helpers through CustomCpt_Lite::instance()->latest_data() / ->get_post_type().
 *
 * Deliberately NOT PSR-4 named: it sits beside the class it was cut from and the
 * one require_once in that constructor is the gate.
 */
class CustomCpt_Cpt_Admin {

    /**
     * Configurations
     *
     * @var array
     */
    public $configs = [];
    public $post_type = 'aaeptypebilder';
    public $tax_type = 'aaetaxebilder';
    public $meta_key = 'aae_ptypebilder_meta';
    public $tax_meta_key = 'aae_ptaxbilder_meta';
    public $cache_key = 'aaeaddon_cpts_cache';
    public $cache_tax_key = 'aaeaddon_taxs_cache';
    
    public $plabels =  array(
            'name'          => '',
            'all_items'     => 'All',
            'singular_name' => ''
    );
    
    public $singular_caps = [
        'edit_post'          => 'edit_post',       // Singular
        'read_post'          => 'read_post',
        'delete_post'        => 'delete_post',     
        'create_post'        => 'create_post',     
    ];
    
    public $plural_caps = [    
        'edit_posts'         => 'edit_posts',      // Plural
        'edit_others_posts'  => 'edit_others_posts',
        'delete_posts'       => 'delete_posts',
        'delete_others_posts'=> 'delete_others_posts',
        'publish_posts'      => 'publish_posts',
        'read_private_posts' => 'read_private_posts',
        'create_posts' => 'create_posts',
    ]; 
    
    public $plural_term_caps = [    
        'manage_terms' => 'manage_categories',   // Plural
        'edit_terms'   => 'manage_categories',
        'delete_terms' => 'manage_categories',
        'assign_terms' => 'edit_posts',
    ];

    /**
     * Singleton instance
     *
     * @var self|null
     */

    /** @var CustomCpt_Cpt_Admin|null */
    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_sub_menu' ], 30 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        // Deprecated admin-ajax aliases (a cached admin bundle posts the old name) -- remove in 4.3.
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_add_or_update_new_post_type_builder', 'aaeaddon_add_or_update_new_post_type_builder', [ $this, 'add_or_update_post_type' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_delete_post_type_builder', 'aaeaddon_delete_post_type_builder', [ $this, 'delete_post_type' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_post_type_builder_list', 'aaeaddon_post_type_builder_list', [ $this, 'post_type_list' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_post_type_builder_single_item', 'aaeaddon_post_type_builder_single_item', [ $this, 'post_type_single_item' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_post_type_exist', 'aaeaddon_post_type_exist', [ $this, 'post_type_exist' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_add_or_update_new_taxonomy_builder', 'aaeaddon_add_or_update_new_taxonomy_builder', [ $this, 'add_or_update_taxonomy' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_delete_taxonomy_builder', 'aaeaddon_delete_taxonomy_builder', [ $this, 'delete_taxonomy' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_taxonomy_builder_list', 'aaeaddon_taxonomy_builder_list', [ $this, 'taxonomy_list' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_taxonomy_builder_single_item', 'aaeaddon_taxonomy_builder_single_item', [ $this, 'taxonomy_single_item' ] );
        \Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_taxonomy_exist', 'aaeaddon_taxonomy_exist', [ $this, 'taxonomy_exist' ] );
    }

    
    public function get_all_flat_caps() {
        // Initialize an empty array to store all capabilities
        $all_caps = [];
    
        // Get user roles and their capabilities
        $user_roles = get_editable_roles();
        foreach ($user_roles as $role_name => $role_info) {
            foreach ($role_info['capabilities'] as $cap => $has_cap) {
                $all_caps[] = $cap; // Add the capability to the array
            }
        }
    
        // Get all post types and their capabilities
        $post_types = get_post_types([], 'objects');
        foreach ($post_types as $post_type_name => $post_type_object) {
            foreach ($post_type_object->cap as $cap => $cap_name) {
                $all_caps[] = $cap_name; // Add the capability to the array
            }
        }
    
        // Get all taxonomies and their capabilities
        $taxonomies = get_taxonomies([], 'objects');
        foreach ($taxonomies as $taxonomy_name => $taxonomy_object) {
            foreach ($taxonomy_object->cap as $cap => $cap_name) {
                $all_caps[] = $cap_name; // Add the tax to the array
            }
        }
    
        // Remove duplicate capabilities
        $all_caps = array_values( array_unique($all_caps) );
    
        return $all_caps;
    }

    /**
     * Register submenu
     *
     * Adds a submenu under a parent menu.
     */
    public function register_sub_menu() {
        add_submenu_page(
            'aaeaddon_page',
            esc_html__( 'CPT Builder', 'animation-addons-for-elementor' ),
            esc_html__( 'CPT Builder', 'animation-addons-for-elementor' ),
            'manage_options',
            'aaeaddon-cpt-builder',
            [ $this, 'cpt_callback' ]
        );
    }

    /**
     * Render submenu
     *
     * Outputs the submenu content.
     */
    public function cpt_callback() {
        echo '<div class="wrap">';
        // translate="no": browser page translation wraps every text node in <font>,
        // and React's next commit then removes a node that has moved -- it throws and
        // unmounts the whole root, i.e. a blank screen. Reported on a WPML site, whose
        // admin <html lang> is not English, so Chrome offers to translate this page.
        // See plugin_dashboard_entry_page() in inc/admin/dashboard.php.
                echo '<div id="aaeaddon-cpt-builder" class="notranslate" translate="no"></div>';
        echo '</div>';
    }


    public function add_or_update_post_type() {

        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_key );       
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_tax_key );       
        $id        = isset( $_POST['post_type_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type_id'] ) ) : null;
        $post_meta = isset( $_POST['post_meta'] ) ? sanitize_text_field( wp_unslash( $_POST['post_meta'] ) ) : null;
        $title     = isset( $_POST['post_type_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type_title'] ) ) : null;
        $post_meta = json_decode($post_meta ?? '{}', true);

       if(is_null($id)) {
        
        $my_post = array(
            'post_title'    => $title,
            'post_content'  => '',
            'post_status'   => 'hidden',
            'post_type'   =>$this->post_type,
        );
            
        // Insert the post into the database
        $createdPost  = wp_insert_post( $my_post );
        $data         = CustomCpt_Lite::instance()->latest_data($this->post_type);
        $data['post'] = $createdPost;
        wp_send_json_success( $data );
       }else if(is_numeric($id)) {
        $my_post = array(
            'ID'           => $id,
            'post_title'   =>  wp_kses_post( $title )         
        );  
        update_post_meta($id, $this->meta_key, $post_meta); 
        wp_update_post( $my_post );
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->post_type) );
       }    
        
    }

    public function delete_post_type() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_key );
        $id = isset( $_POST['post_type_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type_id'] ) ) : null;
       
        if(is_numeric($id)){
            wp_delete_post($id);
            delete_post_meta($id, $this->meta_key);
        }
               
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->post_type) );
    }
    
    

    public function post_type_list() {   

        $nonce = isset($_REQUEST['wcf_nonce']) ? sanitize_text_field( wp_unslash( $_REQUEST['wcf_nonce'] ) ) : null;
        if ( ! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::ADMIN ) ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce', 'animation-addons-for-elementor' ) );
        } 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_key );
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_tax_key );
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->post_type) );
    }

    public function post_type_single_item() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['post_type_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type_id'] ) ) : null;
        $taxonomies = get_taxonomies();
        if(is_numeric($id)){
            $title = get_the_title( $id );
            $meta = get_post_meta($id, $this->meta_key, true);
            wp_send_json_success( ['title' => $title, 'taxonomies' => $taxonomies,'meta' => $meta ] );
        }
        
    }

    public function post_type_exist() {

        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : null;
        $exists = \post_type_exists( $post_type );       
        wp_send_json( ['hasExist' => $exists ] );
        
    }

    public function add_or_update_taxonomy() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id            = isset( $_POST['taxonomy_id'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_id'] ) ) : null;
        $taxonomy_meta = isset( $_POST['taxonomy_meta'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_meta'] ) ) : null;
        $title         = isset( $_POST['taxonomy_title'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_title'] ) ) : null;
        $taxonomy_meta = json_decode($taxonomy_meta, true);
     
       if(is_null($id)) { 
        $my_taxonomy = array(
            'post_title'    => $title,
            'post_content'  => '',
            'post_status'   => 'hidden',
            'post_type'   =>$this->tax_type,
            );
            
        // Insert the post into the database
        $createdTaxonomy = wp_insert_post( $my_taxonomy );
        $data            = CustomCpt_Lite::instance()->latest_data($this->tax_type);
        $data['post']    = $createdTaxonomy;
        wp_send_json_success( $data );
        
       } else if(is_numeric($id)) {
        $my_taxonomy = array(
            'ID'           => $id,
            'post_title'   =>  wp_kses_post( $title ),           
        );  
        update_post_meta($id, $this->tax_meta_key, $taxonomy_meta);   
        wp_update_post( $my_taxonomy );
        
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->tax_type) );
       }           
   
        
    }

    public function delete_taxonomy() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['taxonomy_id'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_id'] ) ) : null;
        if(is_numeric($id)){
            wp_delete_post($id);          
            delete_post_meta($id, $this->tax_meta_key);
        }
     
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->tax_type) );
    }
    

    public function taxonomy_list() {
     
        $nonce = isset($_REQUEST['wcf_nonce']) ? sanitize_text_field( wp_unslash($_REQUEST['wcf_nonce']) ) : null;
        if ( ! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::ADMIN ) ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce', 'animation-addons-for-elementor' ) );
        } 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_key );
        wp_send_json_success( CustomCpt_Lite::instance()->latest_data($this->tax_type) );
    }

    public function taxonomy_single_item() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['taxonomy_id'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_id'] ) ) : null;
        $post_types = CustomCpt_Lite::instance()->get_post_type();
        if(is_numeric($id)){
            $title = get_the_title( $id );
            $meta = get_post_meta($id, $this->tax_meta_key, true);
            wp_send_json_success( ['title' => $title, 'post_types' => $post_types,'meta' => $meta, 'caps' => $this->get_all_flat_caps() ] );
        }
        
        wp_send_json( ['title' => $id, 'post_types' => $post_types,'meta' => '', 'caps' => $this->get_all_flat_caps() ] );
        wp_die();
    }

    public function taxonomy_exist() {
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $post_type = isset( $_POST['taxonomy_key'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_key'] ) ) : null;
        $exists = \taxonomy_exists( $post_type );       
        wp_send_json( ['hasExist' => $exists ] );
        
    }

    /**
     * Enqueue admin scripts
     *
     * Loads necessary styles and scripts for the admin panel.
     */
    public function admin_scripts( $hook ) {
        $screen = get_current_screen();
  	if ( ! $screen || strpos($screen->id, '_page_aaeaddon-cpt-builder') === false) {
			return;
		}
        //if ( $hook === 'animation-addon_page_aaeaddon-cpt-builder' ) {
            wp_enqueue_style(
                'wcf-addon-pro-cpt-builder',
                AAEADDON_URL . 'assets/build/modules/cpt-builder/main.css',
                array( \Wealcoder\AnimationAddons\Aaeaddon_Fonts::ensure() ),
                AAEADDON_VERSION
            );

            wp_enqueue_script(
                'wcf-addon-pro-cpt-builder',
                AAEADDON_URL . 'assets/build/modules/cpt-builder/main.js',
                [ 'react', 'react-dom', 'wp-element', 'wp-i18n' ],
                AAEADDON_VERSION,
                true
            );

            wp_localize_script( 'wcf-addon-pro-cpt-builder', 'WCF_ADDONS_ADMIN', [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => Nonce::create( Nonce::ADMIN ),
            ] );
        //}
    }
}
