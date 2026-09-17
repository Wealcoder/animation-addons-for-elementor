<?php

namespace Wealcoder\AnimationAddons\Extensions;

use Wealcoder\AnimationAddons\Nonce;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * The Custom Fonts extension's wp-admin half.
 *
 * Split out of class-custom-fonts.php. The font CPT registration and every
 * elementor/fonts/* filter + @font-face printer stay on CustomFonts_Lite (they
 * run on the front end); this file holds only the wp-admin menu/metaboxes/list
 * columns and the three admin-ajax writers (save_settings, save_global_settings,
 * custom_font_settings). CustomFonts_Lite requires it under is_admin(), so a
 * visitor's request never parses it; admin-ajax IS is_admin(), so the writers
 * still answer.
 *
 * Deliberately NOT PSR-4 named: it sits beside the class it was cut from and the
 * one require_once in that constructor is the gate.
 */
class CustomFonts_Fonts_Admin
{
	public $elementor_local_font = [];
    public $configs              = [];
    public $font_group_key       = 'wcf-anim-addon-font';
    public $font_group_label     = 'animation-addon';
    public $meta_key             = 'wcf_addon_custom_fonts';
    public $post_type            = 'wcf-custom-fonts';
    public $gl_settings            = [];
    private $printed_fonts = [];
    private $fonts_loaded         = false;
    private $global_fonts_cache   = null;

	/** @var CustomFonts_Fonts_Admin|null */
	private static $instance = null;

	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_sub_menu_post' ], 30 );
		add_action( 'add_meta_boxes', [ $this, 'custom_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		// Deprecated admin-ajax aliases (a cached admin bundle posts the old name) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_save_custom_fonts', 'aaeaddon_save_custom_fonts', [ $this, 'save_settings' ] );
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_save_custom_fonts_settings', 'aaeaddon_save_custom_fonts_settings', [ $this, 'save_global_settings' ] );
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_addon_custom_font_settings', 'aaeaddon_custom_font_settings', [ $this, 'custom_font_settings' ] );
		add_filter( 'post_row_actions', [ $this, 'remove_quick_edit_button' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'remove_post_states' ], 10, 2 );
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

		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action','animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['settings'] ) ) {
			return;
		}
	
		$settings = sanitize_text_field( wp_unslash( $_POST['settings'] ) );
			
		update_option( 'aaeaddon_custom_font_setting', $settings );
		wp_send_json( $settings );
	}
	
	
	function allow_custom_font_uploads($mime_types) {
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
        check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

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

		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

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
			wp_enqueue_style( 'wcf-addon-pro-custom-fonts', AAEADDON_URL . 'assets/build/modules/custom-font/main.css', array(), AAEADDON_VERSION );
			wp_enqueue_script( 'wcf-addon-pro-custom-fonts', AAEADDON_URL . 'assets/build/modules/custom-font/main.js', array(
				'react', 'react-dom', 'wp-element' , 'wp-i18n'
			), AAEADDON_VERSION, true );
            $font = get_post_meta(get_the_id(),'wcf_addon_custom_fonts',true);
            if(is_array($font)){
                $font = wp_json_encode($font);
            } else {
                $font = wp_json_encode([]);
            }
            
			$localize_data = [
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => Nonce::create( Nonce::ADMIN ),
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
	
        add_submenu_page( 'aaeaddon_page' , esc_html__('Custom Fonts', 'animation-addons-for-elementor') , esc_html__('Custom Fonts', 'animation-addons-for-elementor') , 'manage_options' , "edit.php?post_type=$this->post_type", null );      
    }
}
