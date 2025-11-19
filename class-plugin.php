<?php

namespace WCF_ADDONS;

use Elementor\Plugin as ElementorPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Plugin
 *
 * Main Plugin class
 *
 * @since 1.2.0
 */
class Plugin {

	public $categories;
	/**
	 * Plugin version.
	 *
	 * Holds the current plugin version.
	 *
	 * @access public
	 * @static
	 *
	 * @var string Plugin version.
	 */
	use \WCF_ADDONS\WCF_Extension_Widgets_Trait;

	const LIBRARY_OPTION_KEY = 'wcf_templates_library';

	/**
	 * API templates URL.
	 *
	 * Holds the URL of the templates API.
	 *
	 * @access public
	 * @static
	 *
	 * @var string API URL.
	 */

	public $api_url = 'https://block.animation-addons.com/wp-json/api/v2/list';

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
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Widget_scripts
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function widget_scripts() {
		$scripts = array(
			'wcf-addons-core' => array(
				'handler' => 'wcf--addons',
				'src'     => 'wcf-addons.min.js',
				'dep'     => array(),
				'version' => false,
				'arg'     => false,
			),
		);

		foreach ( $scripts as $key => $script ) {
			wp_register_script( $script['handler'], plugins_url( '/assets/js/' . $script['src'], __FILE__ ), $script['dep'], $script['version'], $script['arg'] );
		}

		$data = apply_filters(
			'wcf-addons/js/data',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'_wpnonce'       => wp_create_nonce( 'wcf-addons-frontend' ),
				'post_id'        => get_the_ID(),
				'i18n'           => array(
					'okay'    => esc_html__( 'Okay', 'animation-addons-for-elementor' ),
					'cancel'  => esc_html__( 'Cancel', 'animation-addons-for-elementor' ),
					'submit'  => esc_html__( 'Submit', 'animation-addons-for-elementor' ),
					'success' => esc_html__( 'Success', 'animation-addons-for-elementor' ),
					'warning' => esc_html__( 'Warning', 'animation-addons-for-elementor' ),
				),
				'smoothScroller' => json_decode( get_option( 'wcf_smooth_scroller' ) ),
				'mode'           => \Elementor\Plugin::$instance->editor->is_edit_mode(),
			)
		);

		wp_localize_script( 'wcf--addons', 'WCF_ADDONS_JS', $data );

		wp_enqueue_script( 'wcf--addons' );
		// widget scripts
		foreach ( self::get_widget_scripts() as $key => $script ) {
			wp_register_script( $script['handler'], plugins_url( '/assets/js/' . $script['src'], __FILE__ ), $script['dep'], $script['version'], $script['arg'] );
		}

		if ( defined( 'WCF_ADDONS_PRO_VERSION' ) && version_compare( WCF_ADDONS_PRO_VERSION, '2.4.14', '<=' ) ) {
			wp_enqueue_script( 'aae--switcher-toggle' );
		}
	}

	/**
	 * Function widget_styles
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function widget_styles() {
		$styles = array(
			'wcf-addons-core' => array(
				'handler' => 'wcf--addons',
				'src'     => 'wcf-addons.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
		);

		foreach ( $styles as $key => $style ) {
			wp_register_style( $style['handler'], plugins_url( '/assets/css/' . $style['src'], __FILE__ ), $style['dep'], $style['version'], $style['media'] );
		}

		wp_enqueue_style( 'wcf--addons' );

		// widget style
		foreach ( self::get_widget_style() as $key => $style ) {
			wp_register_style( $style['handler'], plugins_url( '/assets/css/' . $style['src'], __FILE__ ), $style['dep'], $style['version'], $style['media'] );
		}
	}

	/**
	 * Editor scripts
	 *
	 * Enqueue plugin javascripts integrations for Elementor editor.
	 *
	 * @since 1.2.1
	 * @access public
	 */
	public function editor_scripts() {
		wp_enqueue_script(
			'aae-nested-sl',
			WCF_ADDONS_URL . '/assets/build/modules/nested-slider/editor/index.js',
			array(
				'nested-elements',
				'elementor-editor',
				'elementor-common',
				'wp-element',
				'jquery',
			),
			time(),
			true
		);
		wp_enqueue_script(
			'wcf-editor',
			plugins_url( '/assets/js/editor.min.js', __FILE__ ),
			array(
				'elementor-editor',
			),
			WCF_ADDONS_VERSION,
			true
		);

		$data = apply_filters(
			'wcf-addons-editor/js/data',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'_wpnonce' => wp_create_nonce( 'wcf-addons-editor' ),
			)
		);

		wp_localize_script( 'wcf-editor', 'WCF_Addons_Editor', $data );

		// templates Library
		if ( class_exists( '\WCF_ADDONS\Library_Source' ) ) {
			wp_enqueue_script(
				'wcf-template-library',
				plugins_url( '/assets/js/wcf-template-library.js', __FILE__ ),
				array(
					'jquery',
					'wp-util',
				),
				WCF_ADDONS_VERSION,
				true
			);

			wp_localize_script(
				'wcf-template-library',
				'WCF_TEMPLATE_LIBRARY',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'template_types' => self::get_template_types(),
					'nonce'          => wp_create_nonce( 'wcf-template-library' ),
					'dashboard_link' => admin_url( 'admin.php?page=wcf_addons_settings' ),
					'config'         => apply_filters( 'wcf_addons_editor_config', array() ),
					'pro_installed'  => array_key_exists( 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php', get_plugins() ),
					'pro_active'     => class_exists( '\AAE_ADDONS_Plugin_Pro' ) && array_key_exists( 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php', get_plugins() ),
				)
			);

			wp_enqueue_style(
				'wcf-template-library',
				plugins_url( '/assets/css/wcf-template-library.css', __FILE__ ),
				array(),
				WCF_ADDONS_VERSION
			);
		}
	}

	/**
	 * Editor style
	 *
	 * Enqueue plugin css integrations for Elementor editor.
	 *
	 * @since 1.2.1
	 * @access public
	 */
	public function editor_styles() {
		wp_enqueue_style( 'wcf--editor', plugins_url( '/assets/css/editor.min.css', __FILE__ ), array(), time(), 'all' );
	}

	/**
	 * Function widget_scripts
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function get_widget_scripts() {
		return apply_filters(
			'aae/lite/widgets/scripts',
			array(
				'typed'                => array(
					'handler' => 'typed',
					'src'     => 'typed.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'ProgressBar'          => array(
					'handler' => 'progressbar',
					'src'     => 'progressbar.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'slider'               => array(
					'handler' => 'wcf--slider',
					'src'     => 'widgets/slider.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'typewriter'           => array(
					'handler' => 'wcf--typewriter',
					'src'     => 'widgets/typewriter.min.js',
					'dep'     => array( 'typed', 'jquery' ),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'text-hover-image'     => array(
					'handler' => 'wcf--text-hover-image',
					'src'     => 'widgets/text-hover-image.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'counter'              => array(
					'handler' => 'wcf--counter',
					'src'     => 'widgets/counter.min.js',
					'dep'     => array('jquery-numerator'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'nested-slider'        => array(
					'handler' => 'aae--nested-slider',
					'src'     => 'widgets/aae-slider-frontend.min.js',
					'dep'     => array('jquery-numerator'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'socials-shares'       => array(
					'handler' => 'wcf--socials-share',
					'src'     => 'widgets/social-share.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'progressbar'          => array(
					'handler' => 'wcf--progressbar',
					'src'     => 'widgets/progressbar.min.js',
					'dep'     => array('progressbar'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),

				'tabs'                 => array(
					'handler' => 'wcf--tabs',
					'src'     => 'widgets/tabs.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'nav-menu'             => array(
					'handler' => 'wcf--nav-menu',
					'src'     => 'widgets/nav-menu.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'animated-heading'     => array(
					'handler' => 'wcf--animated-heading',
					'src'     => 'widgets/animated-heading.min.js',
					'dep'     => array('jquery', 'gsap'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'video-posts-tab'      => array(
					'handler' => 'aae-video-posts-tab',
					'src'     => 'widgets/video-posts-tab.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'search'               => array(
					'handler' => 'aae--search',
					'src'     => 'widgets/search.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'contact-form-7'       => array(
					'handler' => 'aae--contact-form',
					'src'     => 'widgets/contact-form.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'image-hotspot'        => array(
					'handler' => 'aae-image-hotspot',
					'src'     => 'widgets/image-hotspot.min.js',
					'dep'     => array('jquery'),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'wcf-posts'            => array(
					'handler' => 'wcf--posts',
					'src'     => 'widgets/post-pro.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'button-pro'           => array(
					'handler' => 'aae--button-pro',
					'src'     => 'widgets/button-pro.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'category-slider'      => array(
					'handler' => 'wcf--category-slider',
					'src'     => 'widgets/category-slider.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'feature-posts'        => array(
					'handler' => 'wcf--posts',
					'src'     => 'widgets/post.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'wcf--a-accordion'     => array(
					'handler' => 'wcf--a-accordion',
					'src'     => 'widgets/advance-accordion.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'filterable-slider'    => array(
					'handler' => 'wcf--filterable-slider',
					'src'     => 'widgets/filterable-slider.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'notification'         => array(
					'handler' => 'aae-notification',
					'src'     => 'widgets/notification.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'post-rating'          => array(
					'handler' => 'aae-post-rating',
					'src'     => 'widgets/post-rating.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'post-reactions-js'    => array(
					'handler' => 'wcf--post-reactions',
					'src'     => 'widgets/post-reactions.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'team-slider'          => array(
					'handler' => 'wcf--team-slider',
					'src'     => 'widgets/team-slider.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'image-compare'        => array(
					'handler' => 'wcf--image-compare',
					'src'     => 'widgets/image-compare.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'mailchimp-script'     => array(
					'handler' => 'wcf--mailchimp',
					'src'     => 'widgets/mailchimp.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'click-drop'           => array(
					'handler' => 'wcf--click-drop',
					'src'     => 'widgets/click-drop.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'wcf--countdown'       => array(
					'handler' => 'wcf-countdown-script',
					'src'     => 'widgets/countdown.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'aae--switcher-toggle' => array(
					'handler' => 'aae--switcher-toggle',
					'src'     => 'widgets/toggle-switch.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
				'wcf-image-accordion'  => array(
					'handler' => 'wcf--image-accordion',
					'src'     => 'widgets/image-accordion.min.js',
					'dep'     => array(),
					'version' => WCF_ADDONS_VERSION,
					'arg'     => true,
				),
			)
		);
	}

	/**
	 * Function widget_style
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function get_widget_style() {
		return array(
			'icon-box'           => array(
				'handler' => 'wcf--icon-box',
				'src'     => 'widgets/icon-box.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'testimonial'        => array(
				'handler' => 'wcf--testimonial',
				'src'     => 'widgets/testimonial.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'testimonial2'       => array(
				'handler' => 'wcf--testimonial2',
				'src'     => 'widgets/testimonial2.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'testimonial3'       => array(
				'handler' => 'wcf--testimonial3',
				'src'     => 'widgets/testimonial3.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'posts'              => array(
				'handler' => 'wcf--posts',
				'src'     => 'widgets/posts.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'button'             => array(
				'handler' => 'wcf--button',
				'src'     => 'widgets/button.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'progressbar'        => array(
				'handler' => 'wcf--progressbar',
				'src'     => 'widgets/progressbar.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'counter'            => array(
				'handler' => 'wcf--counter',
				'src'     => 'widgets/counter.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'brand-slider'       => array(
				'handler' => 'wcf--brand-slider',
				'src'     => 'widgets/brand-slider.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'text-hover-image'   => array(
				'handler' => 'wcf--text-hover-image',
				'src'     => 'widgets/text-hover-image.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'one-page-nav'       => array(
				'handler' => 'wcf--one-page-nav',
				'src'     => 'widgets/one-page-nav.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'social-icons'       => array(
				'handler' => 'wcf--social-icons',
				'src'     => 'widgets/social-icons.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'image-gallery'      => array(
				'handler' => 'wcf--image-gallery',
				'src'     => 'widgets/image-gallery.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'team'               => array(
				'handler' => 'wcf--team',
				'src'     => 'widgets/team.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'image-box'          => array(
				'handler' => 'wcf--image-box',
				'src'     => 'widgets/image-box.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'timeline'           => array(
				'handler' => 'wcf--timeline',
				'src'     => 'widgets/timeline.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'event-slider'       => array(
				'handler' => 'wcf--event-slider',
				'src'     => 'widgets/event-slider.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'services-tab'       => array(
				'handler' => 'wcf--services-tab',
				'src'     => 'widgets/services-tab.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'countdown'          => array(
				'handler' => 'wcf--countdown',
				'src'     => 'widgets/countdown.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'meta-info'          => array(
				'handler' => 'wcf--meta-info',
				'src'     => 'widgets/meta-info.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'video-posts-tab'    => array(
				'handler' => 'aae-video-posts-tab',
				'src'     => 'widgets/video-posts-tab.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),

			'search'             => array(
				'handler' => 'aae--search',
				'src'     => 'widgets/search.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'image-hotspot'      => array(
				'handler' => 'aae-image-hotspot',
				'src'     => 'widgets/image-hotspot.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'clickdrop'          => array(
				'handler' => 'aae-clickdrop',
				'src'     => 'widgets/clickdrop.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'author-box'         => array(
				'handler' => 'wcf--author-box',
				'src'     => 'widgets/author-box.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'posts-pro'          => array(
				'handler' => 'wcf--post-pro',
				'src'     => 'widgets/posts-pro.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'button-pro'         => array(
				'handler' => 'aae--button-pro',
				'src'     => 'widgets/button-pro.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'category-showcase'  => array(
				'handler' => 'wcf--category-showcase',
				'src'     => 'widgets/category-showcase.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'feature-posts'      => array(
				'handler' => 'wcf--post-pro',
				'src'     => 'widgets/posts.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'grid-hover-posts'   => array(
				'handler' => 'grid-hover-posts',
				'src'     => 'widgets/grid-hover-posts.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'wcf--a-accordion'   => array(
				'handler' => 'wcf--a-accordion',
				'src'     => 'widgets/advance-accordion.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'aae-a-testimonial'  => array(
				'handler' => 'aae-a-testimonial',
				'src'     => 'widgets/advanced-testimonial.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'filterable-slider'  => array(
				'handler' => 'wcf--filterable-slider',
				'src'     => 'widgets/filterable-slider.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'filterable-gallery' => array(
				'handler' => 'wcf--filterable-gallery',
				'src'     => 'widgets/filterable-gallery.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'image-accordion'    => array(
				'handler' => 'wcf--image-accordion',
				'src'     => 'widgets/image-accordion.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'notification'       => array(
				'handler' => 'aae-notification',
				'src'     => 'widgets/notification.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'post-rating'        => array(
				'handler' => 'aae-post-rating',
				'src'     => 'widgets/post-rating.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'post-reactions-css' => array(
				'handler' => 'wcf--post-reactions',
				'src'     => 'widgets/post-reaction.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'post-timeline'      => array(
				'handler' => 'aae-post-timeline',
				'src'     => 'widgets/post-timeline.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'table-of-content'   => array(
				'handler' => 'wcf--table-of-content',
				'src'     => 'widgets/table-of-content.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'team-slider'        => array(
				'handler' => 'wcf--team-slider',
				'src'     => 'widgets/team-slider.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'toggle-switch'      => array(
				'handler' => 'wcf--toggle-switch',
				'src'     => 'widgets/toggle-switch.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'image-compare'      => array(
				'handler' => 'wcf--image-compare',
				'src'     => 'widgets/image-compare.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'post-comment'       => array(
				'handler' => 'wcf--post-comment',
				'src'     => 'widgets/post-comment.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'mailchimp'          => array(
				'handler' => 'wcf--mailchimp',
				'src'     => 'widgets/mailchimp.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'nav-menu'           => array(
				'handler' => 'wcf--nav-menu',
				'src'     => 'widgets/nav-menu.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
			'loop-grid'          => array(
				'handler' => 'wcf--loop-grid',
				'src'     => 'widgets/loop-grid.min.css',
				'dep'     => array(),
				'version' => false,
				'media'   => 'all',
			),
		);
	}

	/**
	 * Register Widgets
	 *
	 * Register new Elementor widgets.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_widgets() {

		foreach ( self::get_widgets() as $slug => $data ) {

			// If upcoming don't register.
			if ( $data['is_upcoming'] ) {
				continue;
			}

			if ( $data['is_pro'] ) {
				continue;
			}

			if ( file_exists( __DIR__ . '/widgets/' . $slug . '/' . $slug . '.php' ) || file_exists( __DIR__ . '/widgets/' . $slug . '.php' ) ) {

				if ( ! $data['is_pro'] && ! $data['is_extension'] ) {

					if ( is_dir( __DIR__ . '/widgets/' . $slug ) ) {
						require_once __DIR__ . '/widgets/' . $slug . '/' . $slug . '.php';
					} else {
						require_once __DIR__ . '/widgets/' . $slug . '.php';
					}

					$class = explode( '-', $slug );
					$class = array_map( 'ucfirst', $class );
					$class = implode( '_', $class );
					$class = 'WCF_ADDONS\\Widgets\\' . $class;
					ElementorPlugin::instance()->widgets_manager->register( new $class() );

				}
			}
		}
	}

	/**
	 * Register Widgets
	 *
	 * Register new Elementor Extensions.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_extensions() {

		foreach ( self::get_extensions() as $slug => $data ) {

			// If upcoming don't register.
			if ( $data['is_upcoming'] ) {
				continue;
			}

			if ( ! $data['is_pro'] && ! $data['is_extension'] ) {
				if ( file_exists( WCF_ADDONS_PATH . 'inc/class-wcf-' . $slug . '.php' ) ) {

					include_once WCF_ADDONS_PATH . 'inc/class-wcf-' . $slug . '.php';
				}
			}
		}
	}

	/**
	 * Widget Category
	 *
	 * @param $elements_manager
	 */
	public function widget_categories( $elements_manager ) {
		$categories = array();

		$categories['weal-coder-addon'] = array(
			'title' => esc_html__( 'AAE', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		);

		$categories['wcf-hf-addon'] = array(
			'title' => __( 'AAE Header & Footer', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		);

		$categories['wcf-archive-addon'] = array(
			'title' => esc_html__( 'AAE Archive', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		);

		$categories['wcf-search-addon'] = array(
			'title' => esc_html__( 'AAE Search', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		);

		$categories['wcf-single-addon'] = array(
			'title' => esc_html__( 'AAE Single', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		);

		$old_categories = $elements_manager->get_categories();
		$categories     = array_merge( $categories, $old_categories );

		$set_categories = function ( $categories ) {
			$this->categories = $categories;
		};

		$set_categories->call( $elements_manager, $categories );
	}

	/**
	 * Include Plugin files
	 *
	 * @access private
	 */
	private function include_files() {

		require_once WCF_ADDONS_PATH . 'config.php';
		require_once WCF_ADDONS_PATH . 'inc/helper.php';
		if ( is_admin() ) {
			if ( get_option( 'wcf_addons_setup_wizard' ) !== 'complete' ) {
				require_once WCF_ADDONS_PATH . 'inc/admin/setup-wizard.php';
			}
			require_once WCF_ADDONS_PATH . 'inc/admin/dashboard.php';
			if ( wcf_addons_get_settings( 'wcf_save_extensions', 'code-snippet' ) ) {
				// Include CodeSnippet Admin functionality.
				include_once WCF_ADDONS_PATH . 'inc/CodeSnippet/CodeSnippet.php';
			}

			include_once WCF_ADDONS_PATH . 'inc/admin/Notices/Notices.php';
			include_once WCF_ADDONS_PATH . 'inc/admin/Notices/ShowNotices.php';
		}

		// Include CodeSnippet frontend functionality.
		if ( wcf_addons_get_settings( 'wcf_save_extensions', 'code-snippet' ) ) {
			include_once WCF_ADDONS_PATH . 'inc/CodeSnippet/CodeSnippetFrontend.php';
			include_once WCF_ADDONS_PATH . 'inc/CodeSnippet/CodeSnippetCompatibility.php';
		}

		require_once WCF_ADDONS_PATH . 'inc/theme-builder/theme-builder.php';

		require_once WCF_ADDONS_PATH . 'inc/hook.php';
		require_once WCF_ADDONS_PATH . 'inc/class-blacklist.php';
		require_once WCF_ADDONS_PATH . 'inc/ajax-handler.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-post-query.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-button.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-slider.php';
		include_once WCF_ADDONS_PATH . 'inc/post-rating-handler.php';
		include_once WCF_ADDONS_PATH . 'inc/category-fields.php';
		include_once WCF_ADDONS_PATH . 'inc/admin/image-cache.php';
		include_once WCF_ADDONS_PATH . 'inc/admin/page-import.php';
		include_once WCF_ADDONS_PATH . 'widgets/mailchimp/mailchimp-api.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-nested-slider.php';

		// Load Loop Builder Integration.
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/init.php';

		// extensions.
		$this->register_extensions();
	}

	public function elementor_editor_url( $url ) {
		$args         = array(
			'numberposts' => 1,
			'post_type'   => 'post',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		);
		$latest_posts = get_posts( $args );
		if ( ! is_wp_error( $latest_posts ) && ! empty( $latest_posts ) && isset( $latest_posts[0] ) ) {
			return add_query_arg( 'aaeid', $latest_posts[0]->ID, $url );
		}
		return add_query_arg( 'aaeid', 1, $url );
	}

	public function print_templates() {
		$all_plugins    = get_plugins();
		$plugin_slug    = 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php';
		$active_plugins = get_option( 'active_plugins' );
		$dahsboard_link = admin_url( 'admin.php?page=wcf_addons_settings' );
		?>
<script type="text/template" id="tmpl-wcf-templates-header">
	<div class="dialog-header dialog-lightbox-header">
				<div class="elementor-templates-modal__header wcf-template-library--header">
					<div class="elementor-templates-modal__header__logo-area"></div>
					<div class="elementor-templates-modal__header__menu-area" data-disabled="false">
						<div id="elementor-template-library-header-menu">
							<#
							let i = 0;
							_.each( data.template_types, function( item, key ) {
							#>
							<div class="elementor-component-tab elementor-template-library-menu-item {{ 0==i ? 'elementor-active' : ''}}" data-tab="{{ key }}">
								{{{ item.label }}}
							</div>
							<#
							i ++ ;
							} );
							#>
						</div>
					</div>
					<div class="elementor-templates-modal__header__items-area">
						<div class="elementor-templates-modal__header__close elementor-templates-modal__header__close--normal elementor-templates-modal__header__item">
							<i class="eicon-close" aria-hidden="true" title="Close"></i>
							<span class="elementor-screen-only"><?php echo esc_html__( 'Close', 'animation-addons-for-elementor' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</script>
<script type="text/template" id="tmpl-wcf-templates">
	<div class="dialog-message dialog-lightbox-message">
				<div class="dialog-content dialog-lightbox-content">
					<div class="elementor-template-library-templates">
						<!--toolbar-->
						<div id="elementor-template-library-toolbar">
							<div style="display: flex; align-items: center; gap: 10px">
															<div id="elementor-template-library-filter-toolbar-remote" class="elementor-template-library-filter-toolbar">
								<div id="elementor-template-library-filter">
									<select id="wcf-template-library-filter-subtype" class="elementor-template-library-filter-select"  tabindex="-1">
										<option value=""><?php echo esc_html__( 'Category', 'animation-addons-for-elementor' ); ?></option>
										<#
										_.each( data.categories, function( item, key ) {
										#>
										<option value="{{item.id}}">{{{item.name}}}</option>
										<#
										} );
										#>
									</select>
								</div>
								</div>
								<div id="elementor-template-library-color-toolbar-remote" class="elementor-template-library-color-toolbar">
								<div id="elementor-template-library-color">
									<select id="wcf-template-library-color-subtype" class="elementor-template-library-color-select"  tabindex="-1">
																			<option value="">All</option>
										<option value="lite">Light</option>
										<option value="dark">Dark</option>
									</select>
								</div>
								</div>
														
														</div>
							<div id="elementor-template-library-filter-text-wrapper">
								<label for="wcf-template-library-filter-text" class="elementor-screen-only"><?php echo esc_html__( 'Search Templates:', 'animation-addons-for-elementor' ); ?></label>
								<input id="wcf-template-library-filter-text" placeholder="Search">
								<i class="eicon-search"></i>
							</div>
						</div>

						<!--templates -->
						<div class="wcf-library-templates">
							<#
							_.each( data.templates, function( item, key ) {
							#>
							<div class="wcf-library-template" data-id="{{item.id}}" data-url="{{item.url}}">
								<div class="thumbnail">
									<img src="{{{ item.thumbnail }}}" alt="{{ item.title }}">
								</div>
								<# if(item?.valid && item.valid){ #>
									<button class="library--action insert">
										<i class="eicon-file-download"></i>
										Insert
									</button>
								<#
								} else {
								#>
								<?php if ( ! class_exists( 'AAE_ADDONS_Plugin_Pro' ) && ! array_key_exists( $plugin_slug, $all_plugins ) ) { ?>
									<a href="https://animation-addons.com" class="library--action pro" target="_blank">
										<i class="eicon-external-link-square"></i>
										<?php echo esc_html__( 'Go Premium', 'animation-addons-for-elementor' ); ?>
									</a>
									<?php } elseif ( class_exists( 'AAE_ADDONS_Plugin_Pro' ) && in_array( $plugin_slug, $active_plugins ) && get_option( 'aae_sc_error_status_current_support' ) !== 'active' ) { ?>
										<a href="<?php echo esc_url( $dahsboard_link ); ?>" class="library--action pro" target="_blank">
											<i class="eicon-external-link-square"></i>
												<?php echo esc_html__( 'Activate License', 'animation-addons-for-elementor' ); ?>
											</a>                
									<?php } elseif ( array_key_exists( $plugin_slug, $all_plugins ) ) { ?>
										<button class="library--action pro aaeplugin-activate">
											<i class="eicon-external-link-square"></i>
											<?php echo esc_html__( 'Activate', 'animation-addons-for-elementor' ); ?>
									</button>
									<?php } ?>
								<# } #>
								<p class="title">{{{ item.title }}}</p>
							</div>
							<#
							} );
							#>
						</div>
						<div class="aaeaadon-loadmore-footer">.</div>
					</div>
				</div>
				<div class="dialog-loading dialog-lightbox-loading wcf-template-library--loading" hidden>
					<div id="elementor-template-library-loading">
						<div class="elementor-loader-wrapper">
							<div class="elementor-loader">
								<div class="elementor-loader-boxes">
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
								</div>
							</div>
							<div class="elementor-loading-title"><?php echo esc_html__( 'Loading', 'animation-addons-for-elementor' ); ?></div>
						</div>
					</div>
				</div>
			</div>
		</script>
		<script type="text/template" id="tmpl-wcf-templates-single">
			<div class="dialog-header dialog-lightbox-header">
				<div class="elementor-templates-modal__header">
					<div id="wcf-template-library-header-preview-back">
							<i class="eicon-" aria-hidden="true"></i>
							<span><?php echo esc_html__( 'Back to Library', 'animation-addons-for-elementor' ); ?></span>
						</div>
					<div class="elementor-templates-modal__header__menu-area"></div>
					<div class="elementor-templates-modal__header__items-area">
						<div class="elementor-templates-modal__header__close elementor-templates-modal__header__close--normal elementor-templates-modal__header__item">

							<i class="eicon-close" aria-hidden="true"></i>
							<span class="elementor-screen-only"><?php echo esc_html__( 'Close', 'animation-addons-for-elementor' ); ?></span>
						</div>
						<div id="elementor-template-library-header-tools">
							<div id="elementor-template-library-header-preview">
								<div id="elementor-template-library-header-preview-insert-wrapper" class="elementor-templates-modal__header__item">
									<# if(WCF_TEMPLATE_LIBRARY?.config?.wcf_valid && WCF_TEMPLATE_LIBRARY?.config?.wcf_valid === true){ #> 
										<button class="library--action insert">
											<i class="eicon-file-download"></i>
											<?php echo esc_html__( 'Insert', 'animation-addons-for-elementor' ); ?>
										</button>
									<# } #>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="dialog-message dialog-lightbox-message">
				<div class="dialog-content dialog-lightbox-content">
					<div id="elementor-template-library-preview">
						<iframe src="{{data.template_link}}"></iframe>
					</div>
				</div>
				<div class="dialog-loading dialog-lightbox-loading wcf-template-library--loading" hidden>
					<div id="elementor-template-library-loading">
						<div class="elementor-loader-wrapper">
							<div class="elementor-loader">
								<div class="elementor-loader-boxes">
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
									<div class="elementor-loader-box"></div>
								</div>
							</div>
							<div class="elementor-loading-title"><?php echo esc_html__( 'Loading', 'animation-addons-for-elementor' ); ?></div>
						</div>
					</div>
				</div>
			</div>
		</script>
		<?php
	}
	public function preview_styles() {

		wp_enqueue_style(
			'wcf-template-library-preview',
			plugins_url( '/assets/css/preview.css', __FILE__ ),
			array(),
			WCF_ADDONS_VERSION
		);

	}
	public static function get_template_types() {
		
		$template_type = array(
			'block' => array(
				'label' => esc_html__( 'Block', 'animation-addons-for-elementor' ),
			),
			'page'  => array(
				'label' => esc_html__( 'Page', 'animation-addons-for-elementor' ),
			),
		);

		return $template_type;
	}

	/**
	 * Get templates data.
	 *
	 * This function the templates data.
	 *
	 * @param bool $force_update Optional. Whether to force the data retrieval or * not. Default is false.
	 *
	 * @return array|false Templates data, or false.
	 * @since 1.0
	 * @access private
	 * @static
	 */
	private static function get_templates_data( $force_update = false ) {

		$cache_key      = 'aae_templates_data_' . 3.1;
		$templates_data = get_transient( $cache_key );

		if ( $force_update || false === $templates_data ) {

			$timeout = ( $force_update ) ? 30 : 45;

			$response = wp_remote_get(
				esc_url_raw( self::$instance->api_url ),
				array(
					'timeout'   => $timeout,
					'sslverify' => false,
					'body'      => array(
						// Which API version is used.
						'api_version' => 1.1,
						// Which language to return.
						'site_lang'   => get_bloginfo( 'language' ),
					),
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				set_transient( $cache_key, array(), 1 * HOUR_IN_SECONDS );
				return false;
			}

			$templates_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $templates_data ) || ! is_array( $templates_data ) ) {
				set_transient( $cache_key, array(), 1 * HOUR_IN_SECONDS );

				return false;
			}

			if ( isset( $templates_data['library'] ) ) {
				update_option( self::LIBRARY_OPTION_KEY, $templates_data['library'], 'no' );
				unset( $templates_data['library'] );
			}
			set_transient( $cache_key, $templates_data, 1 * HOUR_IN_SECONDS );
		}

		return $templates_data;
	}

	public static function admin_scripts( $hook ) {
		if ( $hook === 'plugins.php' ) {
			wp_enqueue_script(
				'aae-admin-scripts',
				WCF_ADDONS_URL . 'assets/js/wcf-admin.js',
				array(),
				WCF_ADDONS_VERSION,
				true
			);

			wp_enqueue_style(
				'aae-plugins-styles',
				WCF_ADDONS_URL . 'assets/css/plugins.css',
				array(),
				WCF_ADDONS_VERSION,
				'all'
			);
		}
	}

	public function wp_head() {

			$data = apply_filters(
				'wcf-addons/js/data',
				array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'_wpnonce'       => wp_create_nonce( 'wcf-addons-frontend' ),
					'post_id'        => get_the_ID(),
					'i18n'           => array(
						'okay'    => esc_html__( 'Okay', 'animation-addons-for-elementor' ),
						'cancel'  => esc_html__( 'Cancel', 'animation-addons-for-elementor' ),
						'submit'  => esc_html__( 'Submit', 'animation-addons-for-elementor' ),
						'success' => esc_html__( 'Success', 'animation-addons-for-elementor' ),
						'warning' => esc_html__( 'Warning', 'animation-addons-for-elementor' ),
					),
					'smoothScroller' => json_decode( get_option( 'wcf_smooth_scroller' ) ),
					'mode'           => \Elementor\Plugin::$instance->editor->is_edit_mode(),
				)
			);
		echo '<script id="wcf-addons-data">const WCF_ADDONS_JS = ' . wp_json_encode( $data ) . ';</script>';
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

		add_action( 'elementor/elements/categories_registered', array( $this, 'widget_categories' ) );

		// Register widget scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'widget_scripts' ), 29 );
		// admin footer

		// Register widget style
		add_action( 'wp_enqueue_scripts', array( $this, 'widget_styles' ) );

		// Register widgets
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register editor scripts
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'editor_scripts' ) );

		// Register editor style
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
		add_filter( 'elementor/document/urls/preview', array( $this, 'elementor_editor_url' ), 4 );
		add_filter( 'elementor/document/urls/wp_preview', array( $this, 'elementor_editor_url' ), 4 );
		// add_action('wp_head', array($this, 'wp_head'), 4);

		$this->include_files();

		if ( class_exists( '\WCF_ADDONS\Library_Source' ) ) {

			add_action( 'elementor/editor/footer', array( $this, 'print_templates' ) );
			// enqueue modal's preview css.
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'preview_styles' ) );
		}
	}
}

// Instantiate Plugin Class
Plugin::instance();
