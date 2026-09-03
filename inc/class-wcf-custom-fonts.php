<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Extensions;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

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
     * Families whose @font-face has already gone out this request.
     *
     * The font list is emitted from more than one place (wp_enqueue_scripts for
     * the always-on global fonts, then wp_head:7 and again wp_footer for the
     * families Elementor actually resolved), so every family is tracked here to
     * keep the same @font-face from being printed twice.
     *
     * @var array<string,true>
     */
    private $printed_fonts = [];

    /**
     * Guards the two get_posts() lookups so they run at most once per request.
     */
    private $fonts_loaded         = false;
    private $global_fonts_cache   = null;
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
		//add_filter( 'upload_mimes', [$this ,'wcf_addon_pro_allow_custom_font_uploads'], 100);
        add_filter( 'wcf_addin_pro_custom_webfonts' , [ $this, '_custom_webfonts' ] , 4 , 2 );
        add_filter( 'wcf_addin_pro_custom_webfonts' , [ $this, 'global_custom_webfonts' ] , 9 );
		add_filter( 'elementor/fonts/additional_fonts' , [ $this, 'elementor_additional_fonts' ] , 12 );
        add_filter( 'elementor/fonts/groups' , [ $this, 'elementor_fonts_group' ] , 12 );
		add_filter( 'wp_check_filetype_and_ext', [ $this , 'font_correct_filetypes' ] , 10 , 5 );

        /*
         * Ask Elementor which families this request needs instead of guessing.
         *
         * Both hooks fire from Frontend::print_fonts_links() on wp_head:7 — after
         * Elementor has walked local element styles, global classes AND global
         * variables and resolved every family through Frontend::enqueue_font(),
         * but before wp_print_styles() on wp_head:8. So the list is complete and
         * still in time to be attached to a stylesheet, in the SAME request.
         *
         * This replaces the old `elementor/frontend/before_get_builder_content`
         * scan, which looked for the family name inside the raw `_elementor_data`
         * string. Under Elementor V4 that string no longer holds the name — a
         * font set on a global class lives in the `e-global-class` CPT and one set
         * through a variable resolves from the kit's `_elementor_global_variables`
         * meta — so the scan silently matched nothing. It was also a substring
         * test ("Inter" matched our own `data-interaction-id` markup), and it
         * wrote its result AFTER the CSS for the same request had been built,
         * which is why a freshly assigned font only appeared on the second load.
         *
         * `register_styles` (Elementor 3.29+) hands over the whole list at once;
         * `print_font_links/{group}` (Elementor 2.0+) arrives one family at a time
         * and covers older versions. Both are safe to run — printed_fonts keeps
         * the overlap from emitting anything twice.
         */
        add_action( 'elementor/fonts/register_styles', [ $this, 'register_font_styles' ] );
        add_action( 'elementor/fonts/print_font_links/' . $this->font_group_key, [ $this, 'print_font_link' ] );

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

    /**
     * Add the families Elementor resolved for the current request.
     *
     * $requested is the list Elementor is about to print. When it is not an array
     * the filter was fired from a call site that runs before Elementor has
     * resolved anything (push_dynamic_style, on wp_enqueue_scripts), so there is
     * nothing to contribute there and only the global fonts go out.
     *
     * This used to read a `wcf_addon_custom_fonts` meta record written by a
     * `_elementor_data` scan on the *previous* request, which is why a newly
     * assigned font only showed up on the second page load.
     *
     * @param array      $return_fonts Accumulated family => weight => sources.
     * @param array|null $requested    Families Elementor asked for.
     */
    function _custom_webfonts( $return_fonts, $requested = null ){

        if ( ! is_array( $requested ) ) {
            return $return_fonts;
        }

        $this->ensure_fonts_loaded();

        foreach ( $requested as $item ) {
            $family = $this->match_family( $item );

            if ( null !== $family ) {
                $return_fonts[ $family ] = $this->configs[ $family ];
            }
        }

        return $return_fonts;
    }

    /**
     * Resolve a family name Elementor handed us to one of our own font posts.
     *
     * CSS family names match case-insensitively and the value stored in an atomic
     * prop may be quoted, so normalise before giving up.
     *
     * @return string|null Key into $configs, or null when the family isn't ours.
     */
    private function match_family( $font ) {

        if ( ! is_string( $font ) ) {
            return null;
        }

        $font = trim( $font, " \t\n\r\0\x0B\"'" );

        if ( '' === $font ) {
            return null;
        }

        if ( isset( $this->configs[ $font ] ) ) {
            return $font;
        }

        foreach ( array_keys( $this->configs ) as $family ) {
            if ( 0 === strcasecmp( (string) $family, $font ) ) {
                return $family;
            }
        }

        return null;
    }
    
    /**
     * Whether the @font-face rules should be echoed into <head> rather than
     * attached to the always-enqueued inline stylesheet.
     */
    private function is_load_in_head() {
        return is_array( $this->gl_settings )
            && ! empty( $this->gl_settings['load_in_head'] );
    }

    /**
     * Elementor 3.29+ — the complete family list for this request, at once.
     *
     * @param string[] $fonts_to_enqueue
     */
    public function register_font_styles( $fonts_to_enqueue = [] ) {
        $this->emit_font_faces( (array) $fonts_to_enqueue );
    }

    /**
     * Elementor 2.0+ — one family at a time, only for our own font group.
     * Covers installs older than the register_styles hook.
     */
    public function print_font_link( $font ) {
        $this->emit_font_faces( [ $font ] );
    }

    /**
     * wp_head:20. Only reached when "load in head" is on; the per-request
     * families were already emitted from register_font_styles() on wp_head:7.
     */
    public function wp_push_style() {
        if ( ! $this->is_load_in_head() ) {
            return;
        }

        $this->emit_font_faces( [] );
    }

    /**
     * wp_enqueue_scripts:20. Elementor has not resolved the page's fonts yet at
     * this point, so this only carries the fonts flagged "Enable For Global".
     */
    public function push_dynamic_style() {
        if ( $this->is_load_in_head() ) {
            return;
        }

        $this->emit_font_faces( [] );
    }

    /**
     * Build and output the @font-face rules for a set of requested families.
     *
     * @param array $requested Families Elementor resolved, or [] for globals only.
     */
    private function emit_font_faces( array $requested ) {

        $fontlist = apply_filters( 'wcf_addin_pro_custom_webfonts', [], $requested ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

        if ( ! is_array( $fontlist ) || empty( $fontlist ) ) {
            return;
        }

        $custom_css = '';

        foreach ( $fontlist as $font_family => $fonts ) {

            // The list is assembled from several passes (globals early, the
            // page's own families on wp_head:7, late templates on wp_footer),
            // so the same family can be offered more than once per request.
            if ( isset( $this->printed_fonts[ $font_family ] ) ) {
                continue;
            }

            $rules = $this->build_font_faces( $font_family, $fonts );

            if ( '' === $rules ) {
                continue;
            }

            $this->printed_fonts[ $font_family ] = true;
            $custom_css                         .= $rules;
        }

        if ( '' === $custom_css ) {
            return;
        }

        /*
         * Always-enqueued inline carrier — see Plugin::INLINE_STYLE_HANDLE.
         * Attaching to wcf--addons would drop these @font-face rules whenever
         * the legacy stylesheet isn't loaded.
         *
         * Once wp_print_styles() has run on wp_head:8 the handle is already on
         * the page, so anything discovered after that — Elementor calls
         * print_fonts_links() a second time on wp_footer for templates rendered
         * below the head — has to be echoed instead.
         */
        $handle = \WCF_ADDONS\Plugin::INLINE_STYLE_HANDLE;

        if ( ! $this->is_load_in_head() && ! wp_style_is( $handle, 'done' ) ) {
            wp_add_inline_style( $handle, $custom_css );

            return;
        }

        printf( '<style id="wcf-custom-fonts">%s</style>', wp_strip_all_tags( $custom_css ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Turn one family's stored variations into @font-face rules.
     *
     * @param string $font_family
     * @param array  $fonts       weight => list of [ src, format, style ]
     */
    private function build_font_faces( $font_family, $fonts ) {

        $font_family = trim( str_replace( '"', '', (string) $font_family ) );

        if ( '' === $font_family || ! is_array( $fonts ) ) {
            return '';
        }

        $css = '';

        foreach ( $fonts as $weight => $font_sources ) {

            if ( ! is_array( $font_sources ) ) {
                continue;
            }

            $urls  = [];
            $style = 'normal';

            foreach ( $font_sources as $font ) {

                if ( empty( $font['src'] ) ) {
                    continue;
                }

                $urls[] = sprintf( "url('%s') %s", esc_url_raw( $font['src'] ), $font['format'] );

                if ( ! empty( $font['style'] ) ) {
                    $style = $font['style'];
                }
            }

            if ( empty( $urls ) ) {
                continue;
            }

            $css .= sprintf(
                '@font-face{font-family:"%s";src:%s;font-weight:%s;font-style:%s;font-display:swap;}%s',
                $font_family,
                implode( ',', $urls ),
                preg_replace( '/[^0-9a-z ]/i', '', (string) $weight ),
                preg_replace( '/[^a-z]/i', '', (string) $style ),
                PHP_EOL
            );
        }

        return $css;
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
	
	
	function elementor_fonts_group($group){
        $group[ $this->font_group_key ] = $this->font_group_label;
        return $group;
    }
	
	function elementor_additional_fonts($fonts){  
	
        $this->ensure_fonts_loaded();
        foreach( $this->configs as $font => $value ){
            $fonts[ $font ] = $this->font_group_key; 
        }        
       return $fonts;
    }
	
	/**
	 * Load every font post into $configs, once per request.
	 *
	 * Both the Elementor font control and the frontend @font-face builder need
	 * this, and either one can be the first to ask.
	 */
	public function ensure_fonts_loaded(){

		if ( ! $this->fonts_loaded ) {
			$this->fonts_loaded = true;
			$this->get_custom_font_from_user();
		}

		return $this->configs;
	}

	public function get_custom_font_from_user(){

        $arr = $this->collect_fonts( false );

        if( is_array($arr) ){
            $this->configs = array_merge($this->configs, $arr);
        }

        return $arr;
    }

    /**
     * Collect the uploaded variations of every font post.
     *
     * @param bool $global_only Restrict to posts with "Enable For Global" on.
     * @return array family => weight => list of [ src, format, style ]
     */
    private function collect_fonts( $global_only ){

        $args = array(
            // Was capped at 15, which silently hid every font past the fifteenth.
            'numberposts' => -1,
            'post_status' => ['draft','publish','pending'],
            'post_type'   => $this->post_type
        );

        $latest_posts = get_posts( $args );

        if( ! is_array($latest_posts) || empty($latest_posts) ){
            return [];
        }

        // Upload slot => the @font-face format() hint the browser needs for it.
        $formats = [
            'ttf'   => "format('truetype')",
            'eot'   => "format('embedded-opentype')",
            'woff2' => "format('woff2')",
            'woff'  => "format('woff')",
            'otf'   => "format('opentype')",
        ];

        $arr = [];

        foreach($latest_posts as $item){

            $family = trim( (string) $item->post_title );

            // Drafts are included, and an untitled one would otherwise register
            // itself as `font-family: ""`.
            if( '' === $family ){
                continue;
            }

            $variation = get_post_meta( $item->ID , 'wcf_addon_custom_fonts', true);

            if( ! is_array($variation) ){
                continue;
            }

            if( $global_only ){
                $has_global = get_post_meta( $item->ID , 'custom_font_global', true);

                if( 'true' !== $has_global ){
                    continue;
                }
            }

            foreach($variation as $font){

                $weight = isset($font['fontWeight']['value']) ? $font['fontWeight']['value'] : '';

                if( '' === $weight ){
                    continue;
                }

                $style = ( isset($font['style']['value']) && '' !== $font['style']['value'] )
                    ? $font['style']['value']
                    : 'normal';

                foreach( $formats as $key => $format ){

                    if( empty($font[ $key ]['file']['url']) ){
                        continue;
                    }

                    $arr[ $family ][ $weight ][] = [
                        'src'    => $font[ $key ]['file']['url'],
                        'format' => $format,
                        // Was collected in the editor but never reached the CSS,
                        // so italics collapsed onto the upright face.
                        'style'  => $style,
                    ];
                }
            }
        }

        return $arr;
    }
    
    public function get_custom_font_from_user_globally(){

        if( null === $this->global_fonts_cache ){
            $this->global_fonts_cache = $this->collect_fonts( true );
        }

        return $this->global_fonts_cache;
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
			wp_enqueue_style( 'wcf-addon-pro-custom-fonts', WCF_ADDONS_URL . 'assets/build/modules/custom-font/main.css', array(), WCF_ADDONS_VERSION );
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