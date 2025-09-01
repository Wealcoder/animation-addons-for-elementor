<?php

namespace WCF_ADDONS\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(class_exists('\WCF_ADDONS\Extensions\CustomFonts')){
    return;
}

if (defined('WCF_ADDONS_PRO_VERSION') && version_compare(WCF_ADDONS_PRO_VERSION, '2.4.11', '<=')) {
    return;
}

Class CustomFonts_Lite{
	public $elementor_local_font = [];
    public $configs              = [];
    public $font_group_key       = 'wcf-anim-addon-font';
    public $font_group_label     = 'animation-addon';
    public $meta_key             = 'wcf_addon_custom_fonts';
    public $post_type            = 'wcf-custom-fonts';
    public $gl_settings            = [];
   	/**
	 * Instance
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.2.0
	 * @access public
	 */
	public static function instance() {
	
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self();
		}

		return self::$instance;
	} 
	
		/**
	 *  Plugin class constructor
	 *
	 * Register plugin action hooks and filters
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct() {
	
		add_action( 'init', [ $this,'custom_post_type' ]);
		add_action( 'admin_menu', [ $this, 'register_sub_menu_post' ] , 30);
		add_action( 'add_meta_boxes', [$this ,'custom_metabox' ]);
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'wp_ajax_wcf_save_custom_fonts', [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_wcf_save_custom_fonts_settings', [ $this, 'save_global_settings' ] );
		add_filter( 'upload_mimes', [$this ,'wcf_addon_pro_allow_custom_font_uploads'], 100);
        add_filter( 'wcf_addin_pro_custom_webfonts' , [ $this, '_custom_webfonts' ] , 4 );
        add_filter( 'wcf_addin_pro_custom_webfonts' , [ $this, 'global_custom_webfonts' ] , 9 );
		add_filter( 'elementor/fonts/additional_fonts' , [ $this, 'elementor_additional_fonts' ] , 12 );
        add_filter( 'elementor/fonts/groups' , [ $this, 'elementor_fonts_group' ] , 12 );       
        add_action( 'elementor/frontend/before_get_builder_content' , [ $this, 'before_get_builder_content' ] , 15 );
		add_filter( 'wp_check_filetype_and_ext', [ $this , 'font_correct_filetypes' ] , 10 , 5 );
        add_action( 'wp_enqueue_scripts',  array( $this, 'push_dynamic_style' ) , 20 ); 
        add_action( 'wp_head',  array( $this, 'wp_push_style' ) , 20 ); 
        add_action( 'wp_ajax_wcf_addon_custom_font_settings', [ $this, 'custom_font_settings' ] );
        $this->gl_settings = aae_validate_content_json( wp_unslash( get_option('wcf_custom_font_setting')) );
        add_filter( 'post_row_actions', [$this,'remove_quick_edit_button'], 10, 2 );
		add_filter( 'display_post_states', [$this,'remove_post_states'], 10, 2);
	}

    function remove_post_states($states, $post) {		
		if (isset($post->post_type) && $post->post_type === $this->post_type) {
			return []; 
		}
		return $states;
	}

	function remove_quick_edit_button($actions, $post) {
		// Replace 'your_custom_post_type' with your actual custom post type slug
		if ($post->post_type === $this->post_type) {
			unset($actions['inline hide-if-no-js']); // Remove the Quick Edit button
			unset($actions['edit']); // Remove the Quick Edit button
		}
		return $actions;
	}
	
	
	public function custom_font_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action','animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['settings'] ) ) {
			return;
		}
	
		$settings = sanitize_text_field( wp_unslash( $_POST['settings'] ) );
			
		update_option( 'wcf_custom_font_setting', $settings );
		wp_send_json( $settings );
	}
	
	function global_custom_webfonts($return_fonts){
        $configs = $this->get_custom_font_from_user_globally();
        if( is_array($configs) ){
            $return_fonts = array_merge($return_fonts, $configs);
        }  

	    return $return_fonts;
	}
    function _custom_webfonts( $return_fonts ){
       
        // Frontend Elementor 
        if(is_archive() || is_tax()){
            $elementor_fonts = get_term_meta(get_queried_object_id(),$this->meta_key,true);
        }else if( is_search() ){
              $elementor_fonts = get_option($this->meta_key.'_search');               
        }else if( is_404() ){
            $elementor_fonts = get_option($this->meta_key.'_error');            
        } else{
            $elementor_fonts = get_post_meta(get_queried_object_id(),$this->meta_key,true);
        }
        
        if(is_array($elementor_fonts)){
          foreach($elementor_fonts as $item){
            if(isset($this->configs[ $item ])){
                $return_fonts[ $item ] = $this->configs[ $item ];
            }
           
          }
        }
      
        return $return_fonts;
    }
    
    public function wp_push_style() {        
        
        if(is_array($this->gl_settings) && isset($this->gl_settings['load_in_head']) && $this->gl_settings['load_in_head'] == true){
        
            $custom_css = '';
            $fontlist   = apply_filters('wcf_addin_pro_custom_webfonts',[]);       
            foreach ($fontlist as $font_family => $fonts) 
            {
                foreach ($fonts as $weight => $font_sources)
                {  
                
                    $url = '';
                    
                    foreach ($font_sources as $font) 
                    {
                        $url .= sprintf("url('%s') %s,", $font['src'], $font['format']); // Correct array usage
                    }
                    
                    $custom_css .= sprintf(
                        '@font-face {
                            font-family: "%s";
                            src: %s;
                            font-weight: %s;
                            font-display: swap;
                        }%s',
                        $font_family,
                        rtrim($url, ','), // Remove trailing comma
                        $weight,
                        PHP_EOL
                    );
                    
                }
            }
            if ( $custom_css === '' ) {
                return;
            }
            echo wp_kses('<style>'.$custom_css.'</style>');
        
        }
         
      
    }  
    public function push_dynamic_style() {  
        if(is_array($this->gl_settings) && isset($this->gl_settings['load_in_head']) && $this->gl_settings['load_in_head'] == true){
            return;
        }
        
        $custom_css = '';
        $fontlist   = apply_filters('wcf_addin_pro_custom_webfonts',[]);   
     
        foreach ($fontlist as $font_family => $fonts) 
        {
            foreach ($fonts as $weight => $font_sources)
            {  
            
                $url = '';
                
                foreach ($font_sources as $font) 
                {
             
                    $url .= sprintf("url('%s') %s,", $font['src'], $font['format']); // Correct array usage
                }
                
                $custom_css .= sprintf(
                    '@font-face {
                        font-family: "%s";
                        src: %s;
                        font-weight: %s;
                        font-display: swap;
                    }%s',
                    $font_family,
                    rtrim($url, ','), // Remove trailing comma
                    $weight,
                    PHP_EOL
                );
                
            }
        }

        if ( $custom_css === '' ) {
            return;
        }
       
       wp_add_inline_style( 'wcf--addons', $custom_css );
    }
	
	function font_correct_filetypes( $data, $file, $filename, $mimes, $real_mime ) {

        if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
            return $data;
        }
        
        $wp_file_type = wp_check_filetype( $filename, $mimes );
        
        if ( 'ttf' === $wp_file_type['ext'] ) {
            $data['ext'] = 'ttf';
            $data['type'] = 'font/ttf';
        } 
        
        if ( 'otf' === $wp_file_type['ext'] ) {
            $data['ext'] = 'otf';
            $data['type'] = 'font/otf';
        } 
        
        if ( 'woff' === $wp_file_type['ext'] ) {
            $data['ext'] = 'woff';
            $data['type'] = 'font/woff';
        } 
        
        if ( 'woff2' === $wp_file_type['ext'] ) {
            $data['ext'] = 'woff2';
            $data['type'] = 'font/woff2';
        } 
        
        if ( 'eot' === $wp_file_type['ext'] ) {
            $data['ext'] = 'eot';
            $data['type'] = 'font/eot';
        } 
        
        return $data;
    }
	
	public function before_get_builder_content( $document ){ 
    
        $_elementor_data = get_post_meta($document->get_post()->ID,'_elementor_data', true);  
       
        foreach( $this->configs as $font => $val ){
            if( is_string($_elementor_data) && str_contains($_elementor_data,$font) ){
                $this->elementor_local_font[$font] = $font;
            }
        }    
        
        if( is_archive() )
        {
            update_term_meta(get_queried_object_id() , $this->meta_key , $this->elementor_local_font );            
        }
        else if( is_search() )
        {
            update_option( $this->meta_key.'_search' , $this->elementor_local_font );
            
        }
        else if( is_404() )
        {
            update_option( $this->meta_key.'_error' , $this->elementor_local_font );
        }
        else
        {
            if ( get_queried_object_id() ) {
                update_post_meta( get_queried_object_id() , $this->meta_key , $this->elementor_local_font ); 
            }             
        }          
     
    }
	
	function elementor_fonts_group($group){
        $group[ $this->font_group_key ] = $this->font_group_label;
        return $group;
    }
	
	function elementor_additional_fonts($fonts){  
	
        $this->get_custom_font_from_user();       
        foreach( $this->configs as $font => $value ){
            $fonts[ $font ] = $this->font_group_key; 
        }        
       return $fonts;
    }
	
	public function get_custom_font_from_user(){
    
        $args = array(
            'numberposts' => 15,
            'post_status' => ['draft','publish','pending'],
            'post_type'   => $this->post_type
        );
          
        $latest_posts = get_posts( $args );
      
        if(!is_array($latest_posts)){
            return [];
        }
       
        if(empty($latest_posts)){
            return [];
        }
 
        $arr = [];
        
        foreach($latest_posts as $item){
           
            $variation = get_post_meta( $item->ID , 'wcf_addon_custom_fonts', true);
		
            if(is_array($variation)){
           
              foreach($variation as $key => $font){
			
                if($font['fontWeight']['value'] !== ''){
                
                    if(isset($font['ttf']['file']['url']) && $font['ttf']['file']['url'] !='' )
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                           
                            'src' => $font['ttf']['file']['url'],
                            'format' => "format('truetype')"
                        ];
                    }
                
                    if(isset($font['eot']['file']['url']) && $font['eot']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                           
                            'src' => $font['eot']['file']['url'],
                            'format' => "format('embedded-opentype')"
                        ];
                    }
                    
                    if(isset($font['woff2']['file']['url']) && $font['woff2']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                            
                            'src' => $font['woff2']['file']['url'],
                             'format' => "format('woff2')"
                        ];
                    }
                
                    if(isset($font['woff']['file']['url']) && $font['woff']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                          
                            'src' => $font['woff']['file']['url'],
                            'format' => "format('woff')"
                        ];
                    }
                    
					if(isset($font['otf']['file']['url']) && $font['otf']['file']['url'] !='')
					{
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                            
                            'src' => $font['otf']['file']['url'],
                             'format' => "format('opentype')"
                        ];
                    }
                    
                }
              }  
              
            }         
          
        }
        
        if( is_array($arr) ){
            $this->configs = array_merge($this->configs, $arr);
        }        
	
        return $arr;
    }
    
    public function get_custom_font_from_user_globally(){
    
        $args = array(
            'numberposts' => 15,
            'post_status' => ['draft','publish','pending'],
            'post_type'   => $this->post_type
        );
          
        $latest_posts = get_posts( $args );
       
        if(!is_array($latest_posts)){
            return [];
        }
       
        if(empty($latest_posts)){
            return [];
        }
 
        $arr = [];
      
        foreach($latest_posts as $item){
           
            $variation = get_post_meta( $item->ID , 'wcf_addon_custom_fonts', true);           
            $has_global = get_post_meta( $item->ID , 'custom_font_global', true);
  		
            if(is_array($variation) && $has_global && $has_global == 'true'){
           
              foreach($variation as $key => $font){
			   
                if($font['fontWeight']['value'] !== ''){
                
                    if(isset($font['ttf']['file']['url']) && $font['ttf']['file']['url'] !='' )
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                           
                            'src' => $font['ttf']['file']['url'],
                            'format' => "format('truetype')"
                        ];
                          
                    }
                
                    if(isset($font['eot']['file']['url']) && $font['eot']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                           
                            'src' => $font['eot']['file']['url'],
                            'format' => "format('embedded-opentype')"
                        ];
                                                
                    }
                    
                    if(isset($font['woff2']['file']['url']) && $font['woff2']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                            
                            'src' => $font['woff2']['file']['url'],
                             'format' => "format('woff2')"
                        ];                        
                        
                    }
                
                    if(isset($font['woff']['file']['url']) && $font['woff']['file']['url'] !='')
                    {
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                          
                            'src' => $font['woff']['file']['url'],
                            'format' => "format('woff')"
                        ];                        
                       
                    }
                    
					if(isset($font['otf']['file']['url']) && $font['otf']['file']['url'] !='')
					{
                         
                        $arr[$item->post_title][$font['fontWeight']['value']][] = [                            
                            'src' => $font['otf']['file']['url'],
                             'format' => "format('opentype')"
                        ];                        
                        
                    }
                    
                }
              }  
              
            }        
          
        }      
      
        return $arr;
    }
	
	
	function wcf_addon_pro_allow_custom_font_uploads($mime_types) {
		// Add support for font file types
		$mime_types['woff'] = 'font/woff';
		$mime_types['woff2'] = 'font/woff2';
        $mime_types['ttf'] = 'font/ttf';
		$mime_types['otf'] = 'font/otf';
		$mime_types['eot'] = 'application/vnd.ms-fontobject'; // Add support for .eot
        $mime_types['zip']  = 'application/zip';
        $mime_types['x-zip'] = 'application/x-zip-compressed';
		return $mime_types;
	}
	
	public function save_global_settings() {
        check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['custom_font_global'] ) ) {
			return;
		}
        
        if ( ! isset( $_POST['id'] ) ) {
			return;
		}
        $sanitize_id = sanitize_text_field( wp_unslash($_POST['id']) );
        $sanitize_data = sanitize_text_field( wp_unslash($_POST['custom_font_global']) );
        update_post_meta($sanitize_id, 'custom_font_global', $sanitize_data);
		wp_send_json( esc_html__( 'Updated', 'animation-addons-for-elementor' ) );
    }
	public function save_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['fields'] ) ) {
			return;
		}

		if ( ! isset( $_POST['id'] ) ) {
			return;
		}

		$sanitize_data = sanitize_text_field( wp_unslash($_POST['fields']) );
		$sanitize_id = sanitize_text_field( wp_unslash($_POST['id']) );
		$data = json_decode($sanitize_data, true);
		update_post_meta($sanitize_id, 'wcf_addon_custom_fonts', $data);
		wp_send_json( esc_html__( 'Updated', 'animation-addons-for-elementor' ) );
	}

	public function admin_scripts() {
		$current_screen = get_current_screen();
		
		if(isset($current_screen->id) && $current_screen->id == 'wcf-custom-fonts'){
			wp_enqueue_media();
			wp_enqueue_style( 'wcf-addon-pro-custom-fonts', WCF_ADDONS_URL . 'assets/build/modules/custom-font/main.css' );
			wp_enqueue_script( 'wcf-addon-pro-custom-fonts', WCF_ADDONS_URL . 'assets/build/modules/custom-font/main.js', array(
				'react', 'react-dom', 'wp-element' , 'wp-i18n'
			), WCF_ADDONS_VERSION, true );
            $font = get_post_meta(get_the_id(),'wcf_addon_custom_fonts',true);
            if(is_array($font)){
                $font = json_encode($font);
            } else {
                $font = json_encode([]);
            }
            
			$localize_data = [
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wcf_admin_nonce' ),
				'data' => wp_unslash( $font ),
				'id'		 => get_the_id(),
				'custom_font_global'		 =>get_post_meta(get_the_id(),'custom_font_global', true)
				
			];
			
			wp_localize_script( 'wcf-addon-pro-custom-fonts', 'WCF_ADDONS_ADMIN', $localize_data );
		}
	
	}

	function custom_metabox() {

		add_meta_box(
			'wcf_proaddon_custom_fonts_metabox',          
			esc_html__('Custom Fonts','animation-addons-for-elementor'),      
			[$this,'metabox_callback'],    
			$this->post_type,                  
			'normal',                   
			'high'                
		);

        add_meta_box(
			'wcf_proaddon_custom_fonts_metabox_settings',          
			esc_html__('Settings','animation-addons-for-elementor'),      
			[$this,'metabox_side_settings_callback'],    
			$this->post_type,                  
			'side',                   
			'high'                
		);

	}
	public function metabox_callback(){
		echo '<div id="wcf--custom-fonts-meta-box">Loading</div>';
	}
	public function metabox_side_settings_callback(){
		echo '<div id="wcf--custom-fonts-meta-box-side-setting">Loading</div>';
	}
	public function register_sub_menu_post() { 
	
        add_submenu_page( 'wcf_addons_page' , esc_html__('Custom Fonts', 'animation-addons-for-elementor') , esc_html__('Custom Fonts', 'animation-addons-for-elementor') , 'manage_options' , "edit.php?post_type=$this->post_type", null );      
    }
	function custom_post_type(){
   
		$labels = array(
			'name'                  => _x( 'Fonts', 'Post type general name', 'animation-addons-for-elementor' ),
			'singular_name'         => _x( 'Font', 'Post type singular name', 'animation-addons-for-elementor' ),
			'menu_name'             => _x( 'Fonts', 'Admin Menu text', 'animation-addons-for-elementor' ),
			'name_admin_bar'        => _x( 'Font', 'Add New on Toolbar', 'animation-addons-for-elementor' ),
			'add_new'               => __( 'Add New', 'animation-addons-for-elementor' ),
			'add_new_item'          => __( 'Add New Font', 'animation-addons-for-elementor' ),
			'new_item'              => __( 'New Font', 'animation-addons-for-elementor' ),
			'edit_item'             => __( 'Edit Font', 'animation-addons-for-elementor' ),
			'view_item'             => __( 'View Font', 'animation-addons-for-elementor' ),
			'all_items'             => __( 'All Fonts', 'animation-addons-for-elementor' ),
			'search_items'          => __( 'Search Font', 'animation-addons-for-elementor' ),
			'parent_item_colon'     => __( 'Parent Fonts:', 'animation-addons-for-elementor' ),
			'not_found'             => __( 'No font found.', 'animation-addons-for-elementor' ),
			'not_found_in_trash'    => __( 'No fonts found in Trash.', 'animation-addons-for-elementor' ),
			'featured_image'        => _x( 'Font Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor'),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor' ),
			'archives'              => _x( 'Font archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'animation-addons-for-elementor' ),
			'insert_into_item'      => _x( 'Insert into Font', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'animation-addons-for-elementor'),
			'uploaded_to_this_item' => _x( 'Uploaded to this Font', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'animation-addons-for-elementor' ),
			'filter_items_list'     => _x( 'Filter Fonts list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'animation-addons-for-elementor' ),
			'items_list_navigation' => _x( 'Fonts list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'animation-addons-for-elementor' ),
			'items_list'            => _x( 'Fonts list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'animation-addons-for-elementor' ),
		);
        register_post_type($this->post_type,
          array(
            'labels'      => $labels,
              'public'              => true,
              'menu_icon'           => 'dashicons-text-page',
              'supports'            => [ 'title'],
              'exclude_from_search' => true,
              'has_archive'         => false,
              'publicly_queryable'  => false,
              'hierarchical'        => false,
              'show_in_menu'        => false,
              'show_in_nav_menus'   => false,
              'show_in_rest'        => false,
              'show_in_admin_bar'   => false,
          )
        );        
       
    }
}

CustomFonts_Lite::instance();