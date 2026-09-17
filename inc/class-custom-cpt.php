<?php

namespace Wealcoder\AnimationAddons\Extensions;

use Wealcoder\AnimationAddons\Nonce;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if(class_exists('\Wealcoder\AnimationAddons\Extensions\CustomCpt_Pro')){    
    return;
}

if (aaeaddon_pro_defined( 'VERSION' ) && version_compare(aaeaddon_pro_constant( 'VERSION' ), '2.4.11', '<=')) {
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
       
      
        add_action( 'init', [$this,'setup_post_type'], 8 );
        add_action( 'init', [$this,'register_cpt'] , 100);
        add_action( 'init', [$this,'register_taxonomes'] , 60);

        // A demo's post types and taxonomies arrive INSIDE the content
        // import, as ordinary items of the definition post types above. By
        // then register_cpt() has already run for this request, from a
        // cache built before those rows existed -- so every item of the new
        // type that follows in the same file fails process_post()'s
        // get_post_type_object() test and is dropped without a word.
        // Measured on a real demo: definition at item #11, its six posts at
        // #163, none imported. Re-register as soon as a definition is written.
        add_action( 'wxr_importer.processed.post', [ $this, 'register_from_import' ], 10, 2 );

        if ( is_admin() ) {
            require_once __DIR__ . '/class-custom-cpt-admin.php';
            CustomCpt_Cpt_Admin::instance();
        }
    }


    /**
     * Re-read the definitions and register everything they declare, now.
     *
     * Drops both caches first: register_cpt() returns early on a non-empty
     * cache, so a stale one from before the import would keep answering for
     * the rest of the request -- and, measured, for every later request too,
     * until someone opened the CPT Builder screen.
     */
    public function refresh_registrations() {
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_key );
        \Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $this->cache_tax_key );
        $this->register_taxonomes();
        $this->register_cpt();
    }

    /**
     * Importer callback: a definition post has just been written, meta
     * included (the hook fires after process_post_meta()).
     *
     * @param int   $post_id New post ID.
     * @param array $data    Raw imported post data.
     */
    public function register_from_import( $post_id, $data ) {
        $type = isset( $data['post_type'] ) ? $data['post_type'] : '';
        if ( $this->post_type !== $type && $this->tax_type !== $type ) {
            return;
        }
        $this->refresh_registrations();
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
                            if($template = aaeaddon_validate_content_json($meta['template'])){
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


    public function latest_data($post_type) {
        // get_posts() defaults to numberposts = 5, so a site with six or more
        // definitions never registered the oldest ones. Every row.
        //
        // Definitions are stored with post_status 'hidden', which nothing
        // registers -- deliberately: a generic name in the global status
        // namespace is a collision waiting to happen, and every demo export
        // and Pro's PresetRequires already carry the literal. WP_Query keeps
        // only statuses get_post_stati() knows, so asking for 'hidden' by
        // name carried NO status clause and returned trash too. 'any' is the
        // honest spelling: every row except the internal statuses (trash,
        // auto-draft), an unregistered one included.
        $posts = get_posts( array(
            'post_type'   => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
        ) );
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
}

CustomCpt_Lite::instance();
