<?php

namespace WCF_ADDONS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class WCF_Admin_Init {

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
		add_action( 'wp_ajax_save_smooth_scroller_settings', [ $this, 'save_smooth_scroller_settings' ] );

		//add_action( 'admin_footer', [ $this, 'render_popup' ] );
		add_filter( 'admin_body_class', [$this,'admin_classes'] ); 	
		//add_filter('script_loader_tag', [$this,'add_type_to_script'], 10, 3);
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
			esc_html__( 'WCF Addons', 'animation-addons-for-elementor' ),
			esc_html__( 'WCF Addons', 'animation-addons-for-elementor' ),
			self::MENU_CAPABILITY,
			self::MENU_PAGE_SLUG,
			'',
			WCF_ADDONS_URL . '/assets/images/wcf.png',
			100
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
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wcf_addons_settings' ) {
		
			// CSS
			wp_enqueue_style( 'wcf-admin',plugins_url('dashboard/build/index.css', __FILE__));	
			
			wp_enqueue_script( 'wcf-admin' , plugin_dir_url( __FILE__ ) . 'dashboard/build/index.js' , array( 'react', 'react-dom', 'wp-element' , 'wp-i18n' ), time(), true );

			$localize_data = [
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wcf_admin_nonce' ),
				'addons_config'  => $GLOBALS['wcf_addons_config'],
				'active_widgets'  => get_option('wcf_save_widgets'),
				'addon_tabs'     => $this->get_settings_tab(),
				'adminURL'       => admin_url(),
				'smoothScroller' => json_decode( get_option( 'wcf_smooth_scroller' ) )
			];
			
			wp_localize_script( 'wcf-admin', 'WCF_ADDONS_ADMIN', $localize_data );

		}
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
                <a href="https://support.crowdytheme.com/" class="wcf-admin-btn">View Documentation</a>

                <div class="footer-right">
                </div>
            </div>
        </div>
		<?php
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

		$option_name = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';

		wp_parse_str( sanitize_text_field( wp_unslash( $_POST['fields'] ) ), $settings );

		$settings = array_fill_keys( array_keys( $settings ), true );

		// update new settings
		if ( ! empty( $option_name ) ) {
			$updated = update_option( $option_name, $settings );
			wp_send_json( $updated );
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

		$option = wp_json_encode( $settings );

		// update new settings
		if ( ! empty( $_POST['smooth'] ) ) {
			update_option( 'wcf_smooth_scroller', $option );
			wp_send_json( $option );
		}

		wp_send_json( esc_html__( 'Option name not found!', 'animation-addons-for-elementor' ) );
	}

	/**
	 * Render PopupTemplate
	 *
	 * @access public
	 * @return  void
	 * @since 1.1.2
	 */
	public function render_popup() {
		?>
        <div class="wcf-addons-settings-popup">
            <div class="wcf-addons-settings-popup-overlay"></div>
            <div class="wcf-addons-settings-content">
            </div>
        </div>

        <script type="text/template" id="tmpl-wcf-settings-save">
            <div class="popup-status-wrapper">
                <div class="icon">
                    <# if( 'success' === data.icon) { #>
                        <div class="check-icon">
                            <span class="icon-line line-tip"></span>
                            <span class="icon-line line-long"></span>
                            <div class="icon-circle"></div>
                            <div class="icon-fix"></div>
                        </div>
                    <# } #>

                    <# if( 'error' === data.icon) { #>
                    <div class="error-icon">
                        <span class="icon-line line-tip"></span>
                        <span class="icon-line line-long"></span>
                        <div class="icon-circle"></div>
                        <div class="icon-fix"></div>
                    </div>
                    <# } #>
                </div>
                <h2 class="title">{{{ data.title }}}</h2>
                <div class="text">{{{ data.text }}}</div>
            </div>
        </script>

        <script type="text/template" id="tmpl-wcf-settings-smooth-scroller">
            <div class="popup-status-wrapper">
                <h2 class="title">Smooth Scroller</h2>
                <div class="smooth-scroller-settings">
                    <div class="input-items">
                        <label>Smooth</label>
                        <input type="number" value="{{data.smooth_value}}" />
                    </div>
                    <div class="input-items">
                        <label>Enable On Mobile</label>
                        <input type="checkbox" data-checked="{{data.on_mobile}}"/>
                    </div>
                </div>
                <div class="wcf-popup-actions">
                    <button type="button" class="wcf-button-confirm popup-button">OK</button>
                </div>
            </div>
        </script>
		<?php
	}

}

WCF_Admin_Init::instance();
