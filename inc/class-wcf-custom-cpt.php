<?php

namespace WCF_ADDONS\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if(class_exists('\WCF_ADDONS\Extensions\CustomCpt_Pro')){    
    return;
}

if (defined('WCF_ADDONS_PRO_VERSION') && version_compare(WCF_ADDONS_PRO_VERSION, '2.4.11', '<=')) {
    return;
}

class CustomCpt_Lite {

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
    public $cache_key = 'aae_cpts_032153';
    public $cache_tax_key = 'aae_taxs_933153';
    
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
    private static $instance = null;

    /**
     * Get instance
     *
     * Ensures only one instance of the class is loaded.
     *
     * @return self
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * Registers hooks and actions.
     */
    public function __construct() {
       
      
        add_action( 'admin_menu', [ $this, 'register_sub_menu' ], 30 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'wp_ajax_save_global_settings', [ $this, 'save_global_settings' ] );
        add_action( 'wp_ajax_aae_add_or_update_new_post_type_builder', [ $this, 'aae_add_or_update' ] );
        add_action( 'wp_ajax_aae_delete_post_type_builder', [ $this, 'aae_delete_post_type' ] );
        add_action( 'wp_ajax_aae_post_type_builder_list', [ $this, 'aae_list' ] );
        add_action( 'wp_ajax_aae_post_type_builder_single_item', [ $this, 'aae_single_item' ] );
        add_action( 'wp_ajax_aae_post_type_exist', [ $this, 'post_type_exist' ] );
        add_action( 'wp_ajax_aae_add_or_update_new_taxonomy_builder', [ $this, 'aae_add_or_update_taxonomy' ] );
        add_action( 'wp_ajax_aae_delete_taxonomy_builder', [ $this, 'aae_delete_taxonomy' ] );
        add_action( 'wp_ajax_aae_taxonomy_builder_list', [ $this, 'aae_taxonomy_list' ] );
        add_action( 'wp_ajax_aae_taxonomy_builder_single_item', [ $this, 'aae_taxonomy_single_item' ] );
        add_action( 'wp_ajax_aae_taxonomy_exist', [ $this, 'taxonomy_exist' ] );
        add_action( 'init', [$this,'setup_post_type'], 8 );
        add_action( 'init', [$this,'register_cpt'] , 100);
        add_action( 'init', [$this,'register_taxonomes'] , 60);
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

    public function register_cpt() {
       $cpt_posts = get_option($this->cache_key);
       if(is_array($cpt_posts) && !empty($cpt_posts)) {
        $this->generate_post_types($cpt_posts); 
        return;
       }
 
       $cpt_posts = $this->latest_data($this->post_type);
       $cpt_posts = isset($cpt_posts['data']) ? $cpt_posts['data'] : [];   
       $posts_types = [];
       if(is_array($cpt_posts))
       {
            foreach($cpt_posts as $item)
            {   
                if(isset($item['meta_data']['post_type_key']) && $item['meta_data']['post_type_key'] !=''){
                    $meta = $item['meta_data'];                   
                    $args = [
                        'public' => true
                    ]; 
                  
                    $this->plabels['name'] = esc_html($item['post_title']);
                    $this->plabels['all_items'] = sprintf(
                        '%s %s',
                        esc_html__('All', 'animation-addons-for-elementor'),
                        esc_html($meta['singular_name'])
                    );
                   
                    $this->plabels['singular_name'] = $meta['singular_name'];  
                    $args['labels'] = $this->plabels;
                    
                    if(isset($meta['label']) && is_array($meta['label'])){
                        $args['labels'] = array_merge( $this->plabels , $meta['label'] );
                        unset($meta['label']);
                    }
                    
                    if(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'no_permalink'){
                        $meta['rewrite'] = false; 
                    }elseif(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'post_type_key'){
                        $meta['rewrite']['slug'] = $item['meta_data']['post_type_key'];
                    }elseif(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'custom_permalink'){
                      
                    }
                   
                    // Archive Slug
                    if(isset($meta['has_archive']) && isset($meta['has_archive_slug']) && $meta['has_archive_slug'] !=''){
                        $args['has_archive'] = $meta['has_archive_slug'];
                    }elseif(isset($meta['has_archive'])){
                        $args['has_archive'] = true;
                    }
                
                    // Query var
                    if(isset($meta['query_var']) && $meta['query_var'] =='custom_query_variable' && isset($meta['query_var_data']) && $meta['query_var_data'] !=''){
                        $meta['query_var'] = $meta['query_var_data']; 
                    }elseif(isset($meta['query_var']) && $meta['query_var'] == 'post_type_key'){
                        $meta['query_var'] = true;
                    }elseif(isset($meta['query_var']) && $meta['query_var'] == 'no_query_variable_support'){
                        $meta['query_var'] = false;
                    }
                    
                    if(isset($meta['exclude_from_search']) && $meta['exclude_from_search'] == 1){
                        $args['exclude_from_search'] = true;
                    }elseif(isset($meta['exclude_from_search']) && $meta['exclude_from_search'] == ''){                       
                        $args['exclude_from_search'] = false;                      
                    }
                    
                    if(isset($meta['menu_position']) && $meta['menu_position'] !='')
                    {
                        $args['menu_position'] = (int) $meta['menu_position'];
                    }elseif(isset($meta['menu_position']) && $meta['menu_position'] ==''){
                        unset($meta['menu_position']);
                    }
                   
                    if(isset($meta['show_in_rest']) && $meta['show_in_rest'] == 1){
                    
                        if(isset($meta['template']) && $meta['template'] !='')
                        {
                             // Gutenberg support
                            if($template = aae_validate_content_json($meta['template'])){
                                $args['template'] = $template;                          
                                unset($meta['template']);
                            }
                            
                        }
                        
                        $args['show_in_rest'] = true;
                        
                        if(isset($meta['rest_base']) && $meta['rest_base'] !=''){
                            $args['rest_base'] = $meta['rest_base'];    
                        }
                        
                        if(isset($meta['rest_controller_class']) && $meta['rest_controller_class'] !=''){
                            $args['rest_controller_class'] = $meta['rest_controller_class'];    
                        }elseif(isset($meta['rest_controller_class']) && $meta['rest_controller_class'] ==''){
                            unset($meta['rest_controller_class']);
                        }
                    }
                   
                    // Submenu
                    
                    if(isset($meta['show_in_menu']) && isset($meta['admin_menu_parent']) && $meta['admin_menu_parent'] !== '')
                    {
                        $args['show_in_menu'] = $meta['admin_menu_parent'];
                        if(isset($meta['menu_position'])){
                            unset($meta['menu_position']);
                        }                      
                    }
                    
                    if(isset($meta['register_meta_box_cb']) && $meta['register_meta_box_cb'] !='')
                    {
                        if(!function_exists($meta['register_meta_box_cb'])){
                            unset($meta['register_meta_box_cb']);
                        }
                    }
                   
                    if(isset($meta['capability']) && $meta['capability'] == 1){
                        
                        if(isset($meta['capability_singular']) && $meta['capability_singular'] !='' && isset($meta['capability_plural']) && $meta['capability_plural'] !=''){
                            
                            foreach ($this->singular_caps as $k => &$sng) {                                
                                $sng = str_replace('post', $meta['capability_singular'], $sng);
                            }
                            
                            foreach ($this->plural_caps as $k => &$plrg) {                              
                                $plrg = str_replace('posts', $meta['capability_plural'], $plrg);
                            }
                            
                            $args['capabilities'] = array_merge($this->singular_caps, $this->plural_caps);
                        }
                       
                    }
                    
                    // 
                    if(isset($meta['active']) && $meta['active']){
                        $posts_types[$item['meta_data']['post_type_key']] = array_merge($meta, $args);      
                    }           
                    
                }                
            }
           
       }
      
       if(is_array($posts_types))
       {
            update_option($this->cache_key,$posts_types);
            $this->generate_post_types($posts_types); 
       }
       
    }
    
    public function register_taxonomes(){
        $cpt_posts = get_option($this->cache_tax_key);
       
        if(is_array($cpt_posts)) {
             $this->generate_taxonomy_types($cpt_posts); 
             return;
        } 
        
        $cpt_posts = $this->latest_data($this->tax_type);
      
        $cpt_posts = isset($cpt_posts['data']) ? $cpt_posts['data'] : [];   
        $posts_types = [];
       
        if(is_array($cpt_posts))
        {
             foreach($cpt_posts as $item)
             {  
               
                 if(isset($item['meta_data']['taxonomy_key']) && $item['meta_data']['taxonomy_key'] !=''){
                     $meta = $item['meta_data'];                   
                     $args = [
                         'public' => true
                     ]; 
                   
                     $this->plabels['name'] = esc_html($item['post_title']);
                     $this->plabels['all_items'] = sprintf(
                         '%s %s',
                         esc_html__('All', 'animation-addons-for-elementor'),
                         esc_html($meta['singular_name'])
                     );
                    
                     $this->plabels['singular_name'] = $meta['singular_name'];  
                     $args['labels'] = $this->plabels;
                     
                     if(isset($meta['label']) && is_array($meta['label'])){
                         $args['labels'] = array_merge( $this->plabels , $meta['label'] );
                         unset($meta['label']);
                     }
                     
                     if(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'no_permalink'){
                         $meta['rewrite'] = false; 
                     }elseif(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'post_type_key'){
                         $meta['rewrite']['slug'] = $item['meta_data']['post_type_key'];
                     }elseif(isset($meta['rewrite']['permalink_type']) && $meta['rewrite']['permalink_type'] === 'custom_permalink'){
                       
                     }
                    
                     // Archive Slug
                     if(isset($meta['has_archive']) && isset($meta['has_archive_slug']) && $meta['has_archive_slug'] !=''){
                         $args['has_archive'] = $meta['has_archive_slug'];
                     }elseif(isset($meta['has_archive'])){
                         $args['has_archive'] = true;
                     }
                 
                     // Query var
                     if(isset($meta['query_var']) && $meta['query_var'] =='custom_query_variable' && isset($meta['query_var_data']) && $meta['query_var_data'] !=''){
                         $meta['query_var'] = $meta['query_var_data']; 
                     }elseif(isset($meta['query_var']) && $meta['query_var'] == 'post_type_key'){
                         $meta['query_var'] = true;
                     }elseif(isset($meta['query_var']) && $meta['query_var'] == 'no_query_variable_support'){
                         $meta['query_var'] = false;
                     }
                     
                     if(isset($meta['exclude_from_search']) && $meta['exclude_from_search'] == 1){
                         $args['exclude_from_search'] = true;
                     }elseif(isset($meta['exclude_from_search']) && $meta['exclude_from_search'] == ''){                       
                         $args['exclude_from_search'] = false;                      
                     }
                     
                     if(isset($meta['menu_position']) && $meta['menu_position'] !='')
                     {
                         $args['menu_position'] = (int) $meta['menu_position'];
                     }elseif(isset($meta['menu_position']) && $meta['menu_position'] ==''){
                         unset($meta['menu_position']);
                     }
                    
                     if(isset($meta['show_in_rest']) && $meta['show_in_rest'] == 1){
                        
                         $args['show_in_rest'] = true;
                         
                         if(isset($meta['rest_base']) && $meta['rest_base'] !=''){
                             $args['rest_base'] = $meta['rest_base'];    
                         }
                         
                         if(isset($meta['rest_controller_class']) && $meta['rest_controller_class'] !=''){
                             $args['rest_controller_class'] = $meta['rest_controller_class'];    
                         }elseif(isset($meta['rest_controller_class']) && $meta['rest_controller_class'] ==''){
                             unset($meta['rest_controller_class']);
                         }
                     }
                    
                     // Submenu
                     
                     if(isset($meta['show_in_menu']) && isset($meta['admin_menu_parent']) && $meta['admin_menu_parent'] !== '')
                     {
                         $args['show_in_menu'] = $meta['admin_menu_parent'];
                         if(isset($meta['menu_position'])){
                             unset($meta['menu_position']);
                         }                      
                     }
                     
                     if(isset($meta['register_meta_box_cb']) && $meta['register_meta_box_cb'] !='')
                     {
                         if(!function_exists($meta['register_meta_box_cb'])){
                             unset($meta['register_meta_box_cb']);
                         }
                     }
                    
                     if(isset($meta['capability']) && $meta['capability'] == 1){
                         
                         if(isset($meta['capability_plural']) && $meta['capability_plural'] !=''){
                             
                             foreach ($this->plural_term_caps as $k => &$plrg) {                              
                                 $plrg = str_replace('terms', $meta['capability_plural'], $plrg);
                             }
                             
                             $args['capabilities'] = $this->plural_term_caps;
                         }
                        
                     }
                     
                     // 
                     if(isset($meta['active']) && $meta['active']){
                         $posts_types[$item['meta_data']['taxonomy_key']] = array_merge($meta, $args);      
                     }           
                     
                 }                
             }            
        }
     
        if(is_array($posts_types)) {             
            update_option($this->cache_tax_key,$posts_types);
            $this->generate_taxonomy_types($posts_types); 
        }
      
    }
    public function generate_taxonomy_types($posts_types)
    {
        try{
            foreach($posts_types as $ky => $pargs)
            {
                $obj = isset($pargs['post_types']) && is_array($pargs['post_types']) ? $pargs['post_types'] : [];                
                register_taxonomy( $ky,$obj,$pargs); 
            } 
        }catch(\Exception $e){}
        
    }
    public function generate_post_types($posts_types)
    {
        try{
            foreach($posts_types as $ky => $pargs)
            {
                register_post_type( $ky, $pargs ); 
            } 
        }catch(\Exception $e){}        
    }
    function setup_post_type() {
        $args = array(
            'public'    => false,
            'label'     => __( 'Post type', 'animation-addons-for-elementor' ),
            'menu_icon' => 'dashicons-admin-site-alt2',
        );
        register_post_type( $this->post_type, $args );
    }

    /**
     * Register submenu
     *
     * Adds a submenu under a parent menu.
     */
    public function register_sub_menu() {
        add_submenu_page(
            'wcf_addons_page',
            esc_html__( 'CPT Builder', 'animation-addons-for-elementor' ),
            esc_html__( 'CPT Builder', 'animation-addons-for-elementor' ),
            'manage_options',
            'wcf-cpt-builder',
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
        echo '<div id="wcf-cpt-builder"></div>';
        echo '</div>';
    }

    /**
     * Save global settings via AJAX
     *
     * Handles AJAX requests to save global settings.
     */
    public function save_global_settings() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : null;
        $custom_font_global = isset( $_POST['wcfcustom_cpt_global'] ) ? sanitize_text_field( wp_unslash( $_POST['wcfcustom_cpt_global'] ) ) : null;

        if ( empty( $id ) || empty( $custom_font_global ) ) {
            wp_send_json_error( esc_html__( 'Invalid data provided.', 'animation-addons-for-elementor' ) );
        }

        update_post_meta( $id, 'wcfcustom_cpt_global', $custom_font_global );
        wp_send_json_success( esc_html__( 'Settings updated successfully.', 'animation-addons-for-elementor' ) );
    }

    public function latest_data($post_type) {
        $posts = get_posts( array( 'post_type' => $post_type, 'post_status' => array( 'hidden' ) ) );
        $result = array();
        $taxonomies = get_taxonomies();
        $post_types = $this->get_post_type();
        $meta_key = $post_type === 'aaetaxebilder' ? $this->tax_meta_key : $this->meta_key;
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $post_data = array(
                    'ID'          => $post->ID,
                    'post_title'       => get_the_title($post),                        
                    'meta_data'   => get_post_meta($post->ID, $meta_key , true),
                );
                $result[] = $post_data;
            }      
        } 
        
        if($post_type === 'aaetaxebilder') {
            return ['data' => $result, 'post_types' => $post_types];
        } else {
            return ['data' => $result, 'taxonomies' => $taxonomies];
        }       
    }


    public function aae_add_or_update() {

        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        delete_option($this->cache_key);       
        delete_option($this->cache_tax_key);       
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
        $data         = $this->latest_data($this->post_type);
        $data['post'] = $createdPost;
        wp_send_json_success( $data );
       }else if(is_numeric($id)) {
        $my_post = array(
            'ID'           => $id,
            'post_title'   =>  wp_kses_post( $title )         
        );  
        update_post_meta($id, $this->meta_key, $post_meta); 
        wp_update_post( $my_post );
        wp_send_json_success( $this->latest_data($this->post_type) );
       }    
        
    }

    public function aae_delete_post_type() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        delete_option($this->cache_key);
        $id = isset( $_POST['post_type_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type_id'] ) ) : null;
       
        if(is_numeric($id)){
            wp_delete_post($id);
            delete_post_meta($id, $this->meta_key);
        }
               
        wp_send_json_success( $this->latest_data($this->post_type) );
    }
    
    

    public function aae_list() {   

        $nonce = isset($_REQUEST['wcf_nonce']) ? sanitize_text_field( wp_unslash( $_REQUEST['wcf_nonce'] ) ) : null;
        if ( ! wp_verify_nonce( $nonce, 'wcf_admin_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce', 'animation-addons-for-elementor' ) );
        } 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        delete_option($this->cache_key);
        delete_option($this->cache_tax_key);
        wp_send_json_success( $this->latest_data($this->post_type) );
    }

    public function aae_single_item() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

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

        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : null;
        $exists = \post_type_exists( $post_type );       
        wp_send_json( ['hasExist' => $exists ] );
        
    }

    public function aae_add_or_update_taxonomy() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

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
        $data            = $this->latest_data($this->tax_type);
        $data['post']    = $createdTaxonomy;
        wp_send_json_success( $data );
        
       } else if(is_numeric($id)) {
        $my_taxonomy = array(
            'ID'           => $id,
            'post_title'   =>  wp_kses_post( $title ),           
        );  
        update_post_meta($id, $this->tax_meta_key, $taxonomy_meta);   
        wp_update_post( $my_taxonomy );
        
        wp_send_json_success( $this->latest_data($this->tax_type) );
       }           
   
        
    }

    public function aae_delete_taxonomy() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['taxonomy_id'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_id'] ) ) : null;
        if(is_numeric($id)){
            wp_delete_post($id);          
            delete_post_meta($id, $this->tax_meta_key);
        }
     
        wp_send_json_success( $this->latest_data($this->tax_type) );
    }
    

    public function aae_taxonomy_list() {
     
        $nonce = isset($_REQUEST['wcf_nonce']) ? sanitize_text_field( wp_unslash($_REQUEST['wcf_nonce']) ) : null;
        if ( ! wp_verify_nonce( $nonce, 'wcf_admin_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce', 'animation-addons-for-elementor' ) );
        } 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }
        delete_option($this->cache_key);
        wp_send_json_success( $this->latest_data($this->tax_type) );
    }

    public function aae_taxonomy_single_item() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'animation-addons-for-elementor' ) );
        }

        $id = isset( $_POST['taxonomy_id'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_id'] ) ) : null;
        $post_types = $this->get_post_type();
        if(is_numeric($id)){
            $title = get_the_title( $id );
            $meta = get_post_meta($id, $this->tax_meta_key, true);
            wp_send_json_success( ['title' => $title, 'post_types' => $post_types,'meta' => $meta, 'caps' => $this->get_all_flat_caps() ] );
        }
        
        wp_send_json( ['title' => $id, 'post_types' => $post_types,'meta' => '', 'caps' => $this->get_all_flat_caps() ] );
        wp_die();
    }

    public function get_post_type() {
        $post_types = get_post_types( array( 'public' => true,  'show_ui' => true ) , 'names' , 'and'  );
        // Filter out unwanted post types
        $post_types = array_filter( $post_types, function( $post_type ) {
            // Exclude specific post types
            return $post_type !== 'attachment' &&
                $post_type !== 'e-floating-buttons' &&
                strpos( $post_type, 'wcf-' ) !== 0 &&
                strpos( $post_type, 'templately' ) !== 0 &&
                strpos( $post_type, 'acf-' ) !== 0 &&
                strpos( $post_type, 'animation-addons-for-elementor' ) !== 0;
        });
       return $post_types;
    }

    public function taxonomy_exist() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

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
  
        if ( $hook === 'animation-addon_page_wcf-cpt-builder' ) {
            wp_enqueue_style(
                'wcf-addon-pro-cpt-builder',
                WCF_ADDONS_URL . 'assets/build/modules/cpt-builder/main.css'
            );

            wp_enqueue_script(
                'wcf-addon-pro-cpt-builder',
                WCF_ADDONS_URL . 'assets/build/modules/cpt-builder/main.js',
                [ 'react', 'react-dom', 'wp-element', 'wp-i18n' ],
                WCF_ADDONS_VERSION,
                true
            );

            wp_localize_script( 'wcf-addon-pro-cpt-builder', 'WCF_ADDONS_ADMIN', [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wcf_admin_nonce' ),
            ] );
        }
    }
}

// Initialize the class.
CustomCpt_Lite::instance();
