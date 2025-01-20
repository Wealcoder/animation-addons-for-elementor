<?php

namespace WCF_ADDONS\Admin;
use Elementor\Modules\ElementManager\Options;
use Elementor\Plugin;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class WCF_Admin_Init {
	use \WCF_ADDONS\WCF_Extension_Widgets_Trait;
	/**
	 * Parent Menu Page Slug
	 */
	const MENU_PAGE_SLUG = 'wcf_addons_page';

	/**
	 * Menu capability
	 */
	const MENU_CAPABILITY = 'manage_options';

	/**
	 * [$parent_menu_hook] Parent Menu Hook
	 * @var string
	 */
	static $parent_menu_hook = '';

	/**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [Woolentor_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->remove_all_notices();
		$this->include();
		$this->init();
	}

	function admin_classes( $classes ) {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wcf_addons_settings' ) {
			$classes .= ' wcf-anim2024';
		}
	
		return $classes;
	}

	/**
	 * [init] Assets Initializes
	 * @return [void]
	 */
	public function init() {
	
		add_action( 'admin_menu', [ $this, 'add_menu' ], 25 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_save_settings_with_ajax', [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_wcf_dashboard_notice_store', [ $this, 'notice_store' ] );
		add_action( 'wp_ajax_wcf_get_changelog_data', [ $this, 'get_changelog' ] );
		add_action( 'wp_ajax_wcf_get_notice_data', [ $this, 'get_notice' ] );
		add_action( 'wp_ajax_save_settings_with_ajax_dashboard', [ $this, 'save_settings_dashboard' ] );
		
		add_action( 'wp_ajax_save_smooth_scroller_settings', [ $this, 'save_smooth_scroller_settings' ] );	
		add_action( 'wp_ajax_wcf_addon_custom_font_settings', [ $this, 'custom_font_settings' ] );	
		add_filter( 'admin_body_class', [$this,'admin_classes'],100 ); 	
		add_filter( 'wcf_addons_dashboard_config', [ $this, 'dashboard_db_widgets_config'], 11 );
		add_filter( 'wcf_addons_dashboard_config', [ $this, 'dashboard_db_extnsions_config'], 10 );		
		add_filter( 'wcf_addons_dashboard_config', [ $this, 'dashboard_integrations_config'], 10 );		
		
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
		
	}
	/**
	 * Summary of elementor_disabled_elements
	 * @return void
	 */
	public function disable_widgets_by_element_manager(){
	
		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option( 'wcf_save_widgets' );
		$pattern         = '/^wcf--\w+/';
		
		if(is_array($disable_widgets) && is_array($saved_widgets)){	
		
			foreach($disable_widgets as $item)
			{
				
				if (preg_match($pattern, $item)) 
				{
					
				    $toberemove  = trim($item,'wcf--');
				    if(isset($saved_widgets[$toberemove]))
				    {
						unset($saved_widgets[$toberemove]);
				    }					
				} 
			}
			
			update_option('wcf_save_widgets',$saved_widgets);
		}
		
	}
	
	public function sync_widgets_by_element_manager(){
	    $namefixs = [
					'post-paginate'      => 'wcf--blog--post--paginate',
					'post-social-share'  => 'wcf--blog--post--social-share',
					'post-title'         => 'wcf--blog--post--title',
					'search-form'        => 'wcf--blog--search--form',
					'search-query'       => 'wcf--blog--search--query',
					'text-hover-image'   => 'wcf--t-h-image',
					'post-meta-info'     => 'wcf--blog--post--meta-info',
					'post-excerpt'       => 'wcf--blog--post--excerpt',
					'post-feature-image' => 'wcf--theme-post-image',
					'social-icons'       => 'social-icons',
	    ];
		$disable_widgets = Options::get_disabled_elements();
		$saved_widgets   = get_option( 'wcf_save_widgets' );
		
		if(is_array($disable_widgets) && is_array($saved_widgets))
		{	
		
			foreach($saved_widgets as $key => $state)
			{					
			
				$index = false;
				$index = array_search('wcf--'.$key, $disable_widgets); // Find the index of the element
				if ($index !== false) {
					unset($disable_widgets[$index]); // Remove element if found
				}
				
				$index = array_search('wcf--blog--'.$key, $disable_widgets); // Find the index of the element
				if ($index !== false) {
					unset($disable_widgets[$index]); // Remove element if found
				}
				
				if(array_key_exists($key,$namefixs)){
				
					$slug = $namefixs[$key];					
					$index = array_search($slug, $disable_widgets); // Find the index of the element
					if ($index !== false) {
						unset($disable_widgets[$index]); // Remove element if found
					}
				}
				 
			}
		
			array_unshift($disable_widgets);
		
			Options::update_disabled_elements( $disable_widgets );		
			
		}
		
	}
	/**
	 * merge database saved data with dasboard widgets config
	 * @return [void]
	 */
	public function dashboard_db_widgets_config($configs)
	{
		$wgt           = get_option( 'wcf_save_widgets' );
		$saved_widgets = is_array($wgt) ? array_keys( $wgt ) : [];
		$widgets       = $configs['widgets'];
		wcf_get_db_updated_config($widgets,$saved_widgets);	
		$configs['widgets'] = $widgets;
		return $configs;
	}
	
	/**
	 * merge database saved data with dasboard ext config
	 * @return [void]
	 */
	public function dashboard_db_extnsions_config($configs){
		$ext        = get_option( 'wcf_save_extensions' );
		$saved_ext  = is_array($ext) ? array_keys( $ext ) : [];
		$extensions = $configs['extensions'];
		wcf_get_db_updated_config($extensions,$saved_ext);	
		$configs['extensions'] = $extensions;
		return $configs;
	}

	/**
	 * [include] Load Necessary file
	 * @return [void]
	 */
	public function include() {
		require_once( 'template-functions.php' );
		require_once( 'plugin-installer.php' );
	}

	/**
	 * [add_menu] Admin Menu
	 */
	public function add_menu() {

		self::$parent_menu_hook = add_menu_page(
			esc_html__( 'Animation Addon', 'animation-addons-for-elementor' ),
			esc_html__( 'Animation Addon', 'animation-addons-for-elementor' ),
			self::MENU_CAPABILITY,
			self::MENU_PAGE_SLUG,
			'',
			WCF_ADDONS_URL . '/assets/images/wcf.png',
			8
		);

		add_submenu_page(
			self::MENU_PAGE_SLUG,
			esc_html__( 'Settings', 'animation-addons-for-elementor' ),
			esc_html__( 'Settings', 'animation-addons-for-elementor' ),
			'manage_options',
			'wcf_addons_settings',
			[ $this, 'plugin_dashboard_entry_page' ]
		);

		// Remove Parent Submenu
		remove_submenu_page( self::MENU_PAGE_SLUG, self::MENU_PAGE_SLUG );

	}

	/**
	 * [enqueue_scripts] Add Scripts Base Menu Slug
	 *
	 * @param  [string] $hook
	 *
	 * @return [void]
	 */
	public function enqueue_scripts( $hook ) {
		$total_extensions = $total_widgets = 0;
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wcf_addons_settings' ) {
			//sync element manager
		    $this->disable_widgets_by_element_manager();
			// CSS
			wp_enqueue_style( 'wcf-admin',plugins_url('dashboard/build/index.css', __FILE__));	
			
			wp_enqueue_script( 'wcf-admin' , plugin_dir_url( __FILE__ ) . 'dashboard/build/index.js' , array( 'react', 'react-dom', 'wp-element' , 'wp-i18n' ), time(), true );
			wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['extensions'], $total_extensions);
			wcf_get_total_config_elements_by_key($GLOBALS['wcf_addons_config']['widgets'], $total_widgets);
			
			$widgets       = get_option( 'wcf_save_widgets' );
			$saved_widgets = is_array($widgets) ? array_keys( $widgets ) : [];
			wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['widgets'], $saved_widgets, $foundKeys, $awidgets);
			
			$extensions = get_option( 'wcf_save_extensions' );
			$saved_extensions = is_array($extensions) ? array_keys( $extensions ) : [];		  
            wcf_get_search_active_keys($GLOBALS['wcf_addons_config']['extensions'], $saved_extensions, $foundext, $activeext);
		    $active_widgets = self::get_widgets(); 
		    $active_ext = self::get_extensions(); 
	
			$localize_data = [
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'wcf_admin_nonce' ),
				'addons_config'       => apply_filters('wcf_addons_dashboard_config', $GLOBALS['wcf_addons_config']),
				'adminURL'            => admin_url(),
				'smoothScroller'      => json_decode( get_option( 'wcf_smooth_scroller' ) ),
				'load_in_head' 				=> get_option('wcf_custom_font_setting'),
				'extensions'          => ['total' => $total_extensions,'active' => is_array($active_ext) ? count($active_ext): 0],
				'widgets'             => ['total' =>$total_widgets,'active' => is_array($active_widgets) ? count($active_widgets): 0],
				'global_settings_url' => $this->get_elementor_active_edit_url(),
				'theme_builder_url'   => admin_url('edit.php?post_type=wcf-addons-template'),
				'user_role'           => wcfaddon_get_current_user_roles(),
				'version'             => WCF_ADDONS_VERSION				
			];
			
			wp_localize_script( 'wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data );

		}
	}
	
	function dashboard_integrations_config( $configs ){
	
		if(!isset($configs['integrations']['plugins']['elements'])){
			return $configs;
		}
		
		$action = '';
		$data_base = '';
		foreach($configs['integrations']['plugins']['elements'] as &$plugin){
		
			if ( wcf_addons_get_local_plugin_data( $plugin['basename'] ) === false ) {
				$action = 'Download';
				$data_base = $plugin['download_url'];
			} else {
				if ( is_plugin_active( $plugin['basename'] ) ) {
					$action = 'Activated';
				} else {
					$action = 'Active';
					$data_base = $plugin['basename'];
				}
			}
			$plugin['action'] = $action;
			$plugin['data_base'] = $data_base;
		}
		
		return $configs;
	}
	
	function add_type_to_script($tag, $handle, $source){
		if ('wcf-admin' === $handle) {
			$tag = '<script type="text/javascript" src="'. $source .'" type="module"></script>';
		} 
		return $tag;
	}

	/**
	 * get Settings tabs to admin panel.
	 *
	 * @param array $tabs Array of tabs.
	 *
	 * @return bool|true|void
	 */
	protected function get_settings_tab() {
		$settings_tab = [
			'home'         => [
				'title'    => esc_html__( 'Home', 'animation-addons-for-elementor' ),
				'callback' => 'wcf_admin_settings_home_tab',
			],
			'widgets'      => [
				'title'    => esc_html__( 'Widgets', 'animation-addons-for-elementor' ),
				'callback' => 'wcf_admin_settings_widget_tab',
			],
			'extensions'   => [
				'title'    => esc_html__( 'Extensions', 'animation-addons-for-elementor' ),
				'callback' => 'wcf_admin_settings_extension_tab',
			],
			'integrations' => [
				'title'    => esc_html__( 'Integrations', 'animation-addons-for-elementor' ),
				'callback' => 'wcf_admin_settings_integrations_tab',
			],
		];

		return apply_filters( 'wcf_settings_tabs', $settings_tab );
	}
	
	public function get_elementor_active_edit_url(){
	
		if (defined('ELEMENTOR_VERSION') && class_exists('\Elementor\Plugin')) {
			// Fetch the active kit ID from Elementor settings
			$active_kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
			
			$elementor_edit_url = add_query_arg([
				'post'            =>  $active_kit_id,
				'action'          => 'elementor',
				'active-document' => $active_kit_id,
			], admin_url('post.php'));
			
			return $elementor_edit_url;
		}
		
		return false;
	}

	/**
	 * [plugin_page] Load plugin page template
	 * @return [void]
	 */
	public function plugin_page() {
		?>
        <div class="wrap wcf-admin-wrapper">

			<?php
			$tabs = $this->get_settings_tab();

			if ( ! empty( $tabs ) ) {
				?>
                <div class="wcf-admin-tab">
					<?php
					foreach ( $tabs as $key => $el ) {
						?>
                        <button class="tablinks <?php echo esc_attr( $key ); ?>-tab"
                                data-target="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $el['title'] ); ?></button>
						<?php
					}
					?>
                </div>

                <div class="wcf-admin-tab-content">
					<?php
					foreach ( $tabs as $key => $el ) {
						?>
                        <div id="<?php echo esc_attr( $key ); ?>" class="wcf-tab-pane">
							<?php
							if ( isset( $el['callback'] ) ) {
								call_user_func( $el['callback'], $key, $el );
							}
							?>
                        </div>
						<?php
					}
					?>
                </div>
				<?php
			}
			?>
            <div class="wcf-settings-footer">
                <a href="https://support.crowdytheme.com/" class="wcf-admin-btn"><?php echo esc_html__('View Documentation', 'animation-addons-for-elementor') ?></a>
                <div class="footer-right">
                </div>
            </div>
        </div>
		<?php
	}

	public function admin_footer()
    {
        if (is_admin()) {
					if ( isset( $_GET['page'] ) && $_GET['page'] == 'wcf_addons_settings' ) {
             echo '<div id="wcf-admin-toast"></div>';
    			}
        }
    }
	
	public function plugin_dashboard_entry_page(){
		?>
		<div class="wrap wcf-admin-wrapper" id="wcf-admin-ds-cr-js">			
		</div>
		<?php
	}

	/**
	 * [remove_all_notices] remove addmin notices
	 * @return [void]
	 */
	public function remove_all_notices() {
		add_action( 'in_admin_header', function () {
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'wcf_addons_settings' ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}, 1000 );
	}

	/**
	 * Save Settings
	 * Save EA settings data through ajax request
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function save_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['fields'] ) ) {
			return;
		}
		
		$actives       = $foundkeys = [];
		$option_name   = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';
		$sanitize_data = wp_unslash( sanitize_text_field($_POST['fields']) );
		$settings      = json_decode( $sanitize_data , true );
	    wcf_get_nested_config_keys($settings,$foundkeys, $actives);	
	    
		// update new settings
		if ( ! empty( $option_name ) )
		{
		
			$updated = update_option( $option_name, $actives );
			
			if($option_name == 'wcf_save_widgets'){
				$this->sync_widgets_by_element_manager();
				update_option('wcf_widget_dashboardv2', true);
			}else{
				update_option('wcf_extension_dashboardv2', true);
			}
			$elements = get_option($option_name);
			$return_message = [
				'status'  => $updated,
				'total' => is_array($elements) ? count($elements) : 0
				
		  ];
			wp_send_json( $return_message );
		}
		
		wp_send_json( esc_html__( 'Option name not found!', 'animation-addons-for-elementor' ) );
	}

	public function notice_store() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['notice'] ) ) {
			return;
		}
		
		$sanitize_data = wp_unslash( sanitize_text_field($_POST['notice']) );
		update_option('wcf_notice_data', $sanitize_data);
			
		$return_message = [
				'message' => 'Notice Updated'
		  ];
		wp_send_json( $return_message );
	}

	public function get_changelog() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		$transient = get_transient( 'wcf_changelog_notice_cache3' );
		$return_message = [
			'changelog' => ''
		];
			// Yep!  Just return it and we're done.
		if( $transient !== false ) {			
			$return_message['changelog'] = $transient;				
			} else {		
				$url = 'https://store.wealcoder.com/wp-json/userdata/v1/changelog?p=768';						
				$args = array(
					'timeout' => 60,
					'sslverify' => false,					
					'headers' => array(
						'Accept' => 'application/json'
					));				
				$out = wp_remote_get( $url, $args );
				$body = wp_remote_retrieve_body($out);	
				$decode_data = json_decode($body);			
				$return_message['changelog'] = $decode_data;				
				set_transient( 'wcf_changelog_notice_cache3', $decode_data, 12 * HOUR_IN_SECONDS ); 
			}
			
		wp_send_json( $return_message );
	}

	public function get_notice() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		$return_message = [
				'notice' => json_decode(get_option('wcf_notice_data'))
		  ];
		wp_send_json( $return_message );
	}

	public function save_settings_dashboard() {
		
		
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['fields'] ) ) {
			return;
		}
		
		$actives       = [];
		$option_name   = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';
		$sanitize_data = wp_unslash( sanitize_text_field($_POST['fields']) );
		$settings      = json_decode( $sanitize_data , true );
		$actives    = get_option('wcf_save_widgets');
	    
	    if(is_array($actives)){
	        foreach($settings as $slug => $item ){
	     		
	            if(array_key_exists($slug, $actives) && !$item['is_active']){
	                unset($actives[$slug]);
	            }
	          
	            if( !array_key_exists($slug, $actives) && $item['is_active']){
					$actives[$slug] = true;
					error_log($slug);
	            }
	          
	        }
	    }
		// update new settings
		if ( ! empty( $option_name ) ) {
		
			$updated = update_option( $option_name, $actives );
			
			if($option_name == 'wcf_save_widgets'){
				$this->sync_widgets_by_element_manager();
			}
			$elements = get_option($option_name);
		
			$return_message = [
				'status'  => $updated,
				'total' => is_array($elements) ? count($elements) : 0
			];	
			wp_send_json( $return_message );
		}
		wp_send_json( esc_html__( 'Option name not found!', 'animation-addons-for-elementor' ) );
	}

	/**
	 * Save smooth scroller Settings
	 * settings data through ajax request
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function save_smooth_scroller_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['smooth'] ) ) {
			return;
		}

		$settings = [
			'smooth' => sanitize_text_field( wp_unslash( $_POST['smooth'] ) ),
		];
		
		if ( isset( $_POST['mobile'] ) ) {
			$settings['mobile'] = sanitize_text_field( wp_unslash( $_POST['mobile'] ) );
		}
		if ( isset( $_POST['media'] ) ) {
			$settings['media'] = sanitize_text_field( wp_unslash( $_POST['media'] ) );
		}

		$option = wp_json_encode( $settings );

		// update new settings
		if ( ! empty( $_POST['smooth'] ) ) {
		
			update_option( 'wcf_smooth_scroller', $option );
			wp_send_json( $option );
		}

		wp_send_json( esc_html__( 'Option name not found!', 'animation-addons-for-elementor' ) );
	}

	public function custom_font_settings() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['load_in_head'] ) ) {
			return;
		}

		$load_in_head = $_POST['load_in_head'];
	  update_option( 'wcf_custom_font_setting', $load_in_head );
	  wp_send_json( $load_in_head );
	}


}

WCF_Admin_Init::instance();
