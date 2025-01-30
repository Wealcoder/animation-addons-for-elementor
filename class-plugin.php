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
	use \WCF_ADDONS\WCF_Extension_Widgets_Trait;
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
		$scripts = [
			'wcf-addons-core' => [
				'handler' => 'wcf--addons',
				'src'     => 'wcf-addons.min.js',
				'dep'     => [ 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
		];

		foreach ( $scripts as $key => $script ) {
			wp_register_script( $script['handler'], plugins_url( '/assets/js/' . $script['src'], __FILE__ ), $script['dep'], $script['version'], $script['arg'] );
		}

		$data = apply_filters( 'wcf-addons/js/data', [
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'_wpnonce'       => wp_create_nonce( 'wcf-addons-frontend' ),
			'post_id'        => get_the_ID(),
			'i18n'           => [
				'okay'    => esc_html__( 'Okay', 'animation-addons-for-elementor' ),
				'cancel'  => esc_html__( 'Cancel', 'animation-addons-for-elementor' ),
				'submit'  => esc_html__( 'Submit', 'animation-addons-for-elementor' ),
				'success' => esc_html__( 'Success', 'animation-addons-for-elementor' ),
				'warning' => esc_html__( 'Warning', 'animation-addons-for-elementor' ),
			],
			'smoothScroller' => json_decode( get_option( 'wcf_smooth_scroller' ) )
		] );

		wp_localize_script( 'wcf--addons', 'WCF_ADDONS_JS', $data );

		wp_enqueue_script( 'wcf--addons' );

		//widget scripts
		foreach ( self::get_widget_scripts() as $key => $script ) {
			wp_register_script( $script['handler'], plugins_url( '/assets/js/' . $script['src'], __FILE__ ), $script['dep'], $script['version'], $script['arg'] );
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
		$styles = [
			'wcf-addons-core' => [
				'handler' => 'wcf--addons',
				'src'     => 'wcf-addons.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
		];

		foreach ( $styles as $key => $style ) {
			wp_register_style( $style['handler'], plugins_url( '/assets/css/' . $style['src'], __FILE__ ), $style['dep'], $style['version'], $style['media'] );
		}

		wp_enqueue_style( 'wcf--addons' );

		//widget style
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
		wp_enqueue_script( 'wcf-editor', plugins_url( '/assets/js/editor.min.js', __FILE__ ), [
			'elementor-editor',
		], WCF_ADDONS_VERSION, true );

		$data = apply_filters( 'wcf-addons-editor/js/data', [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'_wpnonce' => wp_create_nonce( 'wcf-addons-editor' ),
		] );

		wp_localize_script( 'wcf-editor', 'WCF_Addons_Editor', $data );
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
		wp_enqueue_style( 'wcf--editor', plugins_url( '/assets/css/editor.min.css', __FILE__ ), [], WCF_ADDONS_VERSION, 'all' );
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
		return [
			'typed'            => [
				'handler' => 'typed',
				'src'     => 'typed.min.js',
				'dep'     => [],
				'version' => false,
				'arg'     => true,
			],			
			'ProgressBar'      => [
				'handler' => 'progressbar',
				'src'     => 'progressbar.min.js',
				'dep'     => [],
				'version' => false,
				'arg'     => true,
			],
			'slider'           => [
				'handler' => 'wcf--slider',
				'src'     => 'widgets/slider.min.js',
				'dep'     => [],
				'version' => false,
				'arg'     => true,
			],
			'typewriter'       => [
				'handler' => 'wcf--typewriter',
				'src'     => 'widgets/typewriter.min.js',
				'dep'     => [ 'typed', 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
			'text-hover-image' => [
				'handler' => 'wcf--text-hover-image',
				'src'     => 'widgets/text-hover-image.min.js',
				'dep'     => [ 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
			'counter'          => [
				'handler' => 'wcf--counter',
				'src'     => 'widgets/counter.min.js',
				'dep'     => [ 'jquery-numerator' ],
				'version' => false,
				'arg'     => true,
			],
			'socials-shares'          => [
				'handler' => 'wcf--socials-share',
				'src'     => 'widgets/social-share.min.js',
				'dep'     => [ ],
				'version' => false,
				'arg'     => true,
			],
			'progressbar'      => [
				'handler' => 'wcf--progressbar',
				'src'     => 'widgets/progressbar.min.js',
				'dep'     => [ 'progressbar' ],
				'version' => false,
				'arg'     => true,
			],
			'before-after'     => [
				'handler' => 'draggable',
				'src'     => 'Draggable.min.js',
				'dep'     => [ 'gsap' ],
				'version' => false,
				'arg'     => true,
			],
			'image-compare'    => [
				'handler' => 'wcf--image-compare',
				'src'     => 'widgets/image-compare.min.js',
				'dep'     => [ 'draggable' ],
				'version' => false,
				'arg'     => true,
			],
			'tabs'             => [
				'handler' => 'wcf--tabs',
				'src'     => 'widgets/tabs.min.js',
				'dep'     => [ 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
			'nav-menu'         => [
				'handler' => 'wcf--nav-menu',
				'src'     => 'widgets/nav-menu.min.js',
				'dep'     => [ 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
			'animated-heading' => [
				'handler' => 'wcf--animated-heading',
				'src'     => 'widgets/animated-heading.min.js',
				'dep'     => [ 'jquery', 'gsap' ],
				'version' => false,
				'arg'     => true,
			],
			'video-posts-tab'             => [
				'handler' => 'aae-video-posts-tab',
				'src'     => 'widgets/video-posts-tab.min.js',
				'dep'     => [ 'jquery' ],
				'version' => false,
				'arg'     => true,
			],
		];
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
		return [
			'icon-box'         => [
				'handler' => 'wcf--icon-box',
				'src'     => 'widgets/icon-box.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'testimonial'      => [
				'handler' => 'wcf--testimonial',
				'src'     => 'widgets/testimonial.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'testimonial2'     => [
				'handler' => 'wcf--testimonial2',
				'src'     => 'widgets/testimonial2.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'testimonial3'     => [
				'handler' => 'wcf--testimonial3',
				'src'     => 'widgets/testimonial3.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'posts'            => [
				'handler' => 'wcf--posts',
				'src'     => 'widgets/posts.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'button'           => [
				'handler' => 'wcf--button',
				'src'     => 'widgets/button.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'progressbar'      => [
				'handler' => 'wcf--progressbar',
				'src'     => 'widgets/progressbar.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'counter'          => [
				'handler' => 'wcf--counter',
				'src'     => 'widgets/counter.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'image-compare'    => [
				'handler' => 'wcf--image-compare',
				'src'     => 'widgets/image-compare.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'brand-slider'     => [
				'handler' => 'wcf--brand-slider',
				'src'     => 'widgets/brand-slider.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'text-hover-image' => [
				'handler' => 'wcf--text-hover-image',
				'src'     => 'widgets/text-hover-image.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'one-page-nav'     => [
				'handler' => 'wcf--one-page-nav',
				'src'     => 'widgets/one-page-nav.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'social-icons'     => [
				'handler' => 'wcf--social-icons',
				'src'     => 'widgets/social-icons.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'image-gallery'    => [
				'handler' => 'wcf--image-gallery',
				'src'     => 'widgets/image-gallery.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'team'             => [
				'handler' => 'wcf--team',
				'src'     => 'widgets/team.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'image-box'        => [
				'handler' => 'wcf--image-box',
				'src'     => 'widgets/image-box.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'timeline'         => [
				'handler' => 'wcf--timeline',
				'src'     => 'widgets/timeline.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'event-slider'     => [
				'handler' => 'wcf--event-slider',
				'src'     => 'widgets/event-slider.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'services-tab'     => [
				'handler' => 'wcf--services-tab',
				'src'     => 'widgets/services-tab.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'countdown'        => [
				'handler' => 'wcf--countdown',
				'src'     => 'widgets/countdown.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'meta-info'        => [
				'handler' => 'wcf--meta-info',
				'src'     => 'widgets/meta-info.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
			'video-posts-tab'        => [
				'handler' => 'aae-video-posts-tab',
				'src'     => 'widgets/video-posts-tab.min.css',
				'dep'     => [],
				'version' => false,
				'media'   => 'all',
			],
		];
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
			
			if($data['is_pro']){
				continue;
			}
			
			if(file_exists(__DIR__ . '/widgets/' . $slug . '/' . $slug . '.php') || file_exists(__DIR__ . '/widgets/' . $slug . '.php'))
			{
				if ( ! $data['is_pro'] && ! $data['is_extension'] )
				{
					if ( is_dir( __DIR__ . '/widgets/' . $slug ) ) {					
						require_once( __DIR__ . '/widgets/' . $slug . '/' . $slug . '.php' );
					} else {
						require_once( __DIR__ . '/widgets/' . $slug . '.php' );
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
			
			if (! $data['is_pro'] && ! $data['is_extension'] ) {

				include_once WCF_ADDONS_PATH . 'inc/class-wcf-' . $slug . '.php';
			}
		}
	}


	/**
	 * Widget Category
	 *
	 * @param $elements_manager
	 */
	public function widget_categories( $elements_manager ) {
		$categories = [];

		$categories['weal-coder-addon'] = [
			'title' => esc_html__( 'AAE', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		];

		$categories['wcf-hf-addon'] = [
			'title' => __( 'AAE Header & Footer', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		];

		$categories['wcf-archive-addon'] = [
			'title' => esc_html__( 'AAE Archive', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		];

		$categories['wcf-search-addon'] = [
			'title' => esc_html__( 'AAE Search', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		];

		$categories['wcf-single-addon'] = [
			'title' => esc_html__( 'AAE Single', 'animation-addons-for-elementor' ),
			'icon'  => 'fa fa-plug',
		];

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

		if ( is_admin() ) {
			
			if (  'complete' !== get_option( 'wcf_addons_setup_wizard' ) ) {
				require_once WCF_ADDONS_PATH . 'inc/admin/setup-wizard.php';
			}

			require_once WCF_ADDONS_PATH . 'inc/admin/dashboard.php';
		}

		require_once( WCF_ADDONS_PATH . 'inc/theme-builder/theme-builder.php' );

		require_once WCF_ADDONS_PATH . 'inc/helper.php';
		require_once WCF_ADDONS_PATH . 'inc/hook.php';
		require_once WCF_ADDONS_PATH . 'inc/extension-update.php';		
		require_once WCF_ADDONS_PATH . 'inc/class-blacklist.php';
		require_once WCF_ADDONS_PATH . 'inc/ajax-handler.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-post-query.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-button.php';
		include_once WCF_ADDONS_PATH . 'inc/trait-wcf-slider.php';

		//extensions
		$this->register_extensions();
	}
	
	public function elementor_editor_url( $url ){
		$args = [
			'numberposts' => 1,
			'post_type'   => 'post',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		]; 
		$latest_posts = get_posts($args);      
		if (!is_wp_error( $latest_posts ) && !empty($latest_posts) && isset($latest_posts[0])) {  
			return add_query_arg( 'aaeid', $latest_posts[0]->ID ,  $url ); 
		}
		return add_query_arg( 'aaeid', 1 , $url ); 
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
		add_action( 'elementor/elements/categories_registered', [ $this, 'widget_categories' ] );

		// Register widget scripts
		add_action( 'wp_enqueue_scripts', [ $this, 'widget_scripts' ] );

		// Register widget style
		add_action( 'wp_enqueue_scripts', [ $this, 'widget_styles' ] );

		// Register widgets
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );	

		// Register editor scripts
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'editor_scripts' ] );

		// Register editor style
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
		add_filter( 'elementor/document/urls/preview' , [ $this, 'elementor_editor_url' ] , 4 );
		add_filter( 'elementor/document/urls/wp_preview' , [ $this, 'elementor_editor_url' ] , 4 );
		$this->include_files();
	}
	
	
}

// Instantiate Plugin Class
Plugin::instance();
