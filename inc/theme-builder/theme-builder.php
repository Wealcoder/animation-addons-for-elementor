<?php

namespace WCF_ADDONS;

use Elementor\Modules\Library\Documents\Library_Document;
use Elementor\Plugin as ElementorPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class WCF_Theme_Builder {

	const CPTTYPE = 'wcf-addons-template';
	const CPT_META = 'wcf-addons-template-meta';


	/**
	 * [$_instance]
	 * @var null
	 */
	public static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

		//Add Menu
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 225 );

		// Print template tabs.
		add_filter( 'views_edit-' . self::CPTTYPE, [ $this, 'print_tabs' ] );

		// query filter
		add_filter( 'parse_query', [ $this, 'query_filter' ] );

		// Template type column.
		add_action( 'manage_' . self::CPTTYPE . '_posts_columns', [ $this, 'manage_columns' ] );
		add_action( 'manage_' . self::CPTTYPE . '_posts_custom_column', [ $this, 'columns_content' ], 10, 2 );

		// Print template edit popup.
		add_action( 'admin_footer', [ $this, 'print_popup' ] );

		// Template store ajax action
		add_action( 'wp_ajax_wcf_save_template', [ $this, 'save_template_request' ] );

		// Get template data Ajax action
		add_action( 'wp_ajax_wcf_get_template', [ $this, 'get_post_By_id' ] );

		add_action( 'wp_ajax_wcf_get_posts_by_query', array( $this, 'get_posts_by_query' ) );

		// Load Scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Change Template
		add_filter( 'template_include', [ $this, 'template_loader' ] , 30 );

		// Archive Page
		add_action( 'wcf_archive_builder_content', [ $this, 'archive_page_builder_content' ] );

		//single
		add_action( 'wcf_single_builder_content', [ $this, 'single_post_builder_content' ] );

		// Body classes
		add_filter( 'body_class', [ $this, 'body_classes' ] );

		//header footer
		add_action( 'get_header', [ $this, 'override_header' ] );
		add_action( 'get_footer', [ $this, 'override_footer' ] );
		add_action( 'wcf_header_builder_content', [ $this, 'header_builder_content' ] );
		add_action( 'wcf_footer_builder_content', [ $this, 'footer_builder_content' ] );
	}

	/**
	 * @param $post_id
	 *
	 * @return Library_Document
	 */
	public static function get_document( $post_id ) {
		$document = null;

		try {
			$document = ElementorPlugin::$instance->documents->get( $post_id );
		} catch ( \Exception $e ) {
			// Do nothing.
			unset( $e );
		}

		if ( ! empty( $document ) && ! $document instanceof Library_Document ) {
			$document = null;
		}

		return $document;
	}

	//header footer//

	/**
	 * Function for overriding the header in the elmentor way.
	 *
	 *
	 */
	public function override_header( $name ) {

		if ( ! $this->has_template( 'header' ) ) {
			return;
		}

		require WCF_ADDONS_PATH . '/templates/header.php';

		$templates = array();
		$name      = (string) $name;
		if ( '' !== $name ) {
			$templates[] = "header-{$name}.php";
		}

		$templates[] = 'header.php';

		// Avoid running wp_head hooks again
		remove_all_actions( 'wp_head' );
		ob_start();
		// It cause a `require_once` so, in the get_header it self it will not be required again.
		locate_template( $templates, true );
		ob_get_clean();
	}

	/**
	 * Function for overriding the footer in the elmentor way.
	 *
	 */
	public function override_footer( $name ) {

		//fixed div ending issues
		if ( ! $this->has_template( 'header' ) && $this->has_template( 'footer' ) ) {

			$current_template = basename( get_page_template_slug() );
			if ( $current_template == 'elementor_canvas' ) {
				return;
			}

			$current_theme = get_template();

			switch ( $current_theme ) {
				case 'astra':
					echo '</div></div>';
					break;

				default:
					break;
			}
		}


		if ( ! $this->has_template( 'footer' ) ) {
			return;
		}

		require WCF_ADDONS_PATH . '/templates/footer.php';

		$templates = array();
		$name      = (string) $name;
		if ( '' !== $name ) {
			$templates[] = "footer-{$name}.php";
		}

		$templates[] = 'footer.php';

		// Avoid running wp_head hooks again
		remove_all_actions( 'wp_footer' );
		ob_start();
		// It cause a `require_once` so, in the get_header it self it will not be required again.
		locate_template( $templates, true );
		ob_get_clean();
	}

	// Set Builder content header footer
	public function header_builder_content() {

		$archive_template_id = $this->get_template_id( 'header' );
		if ( $archive_template_id != '0' ) {
			// PHPCS - should not be escaped.
			echo self::render_build_content( $archive_template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function footer_builder_content() {
		$archive_template_id = $this->get_template_id( 'footer' );
		if ( $archive_template_id != '0' ) {
			// PHPCS - should not be escaped.
			echo self::render_build_content( $archive_template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}	
	
	public function body_classes( $classes ) {

		$class_prefix = 'elementor-page-';

		if ( is_singular() && $this->has_template( 'single' ) ) {
			$classes[] = $class_prefix . self::has_template( 'single' );
		} elseif ( ( is_archive() || is_home() || is_search() ) && $this->has_template( 'archive' ) ) {
			$classes[] = $class_prefix . self::has_template( 'archive' );
		}

		return $classes;

	}

	/**
	 * Load template.
	 *
	 * @param string $template template to load.
	 *
	 * @return string
	 */
	public function template_loader( $template ) {

		if ( is_embed() ) {
			return $template;
		}
		
		if(isset($_REQUEST['aaeid']) && !isset($_REQUEST['preview_id'])){
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			$template = WCF_ADDONS_PATH . '/templates/' . $default_file;
		}

		return $template;

	}

	/**
	 * Get the default filename for a template except if a block template with
	 * the same name exists.
	 *
	 * @return string
	 * @since  5.5.0 If a block template with the same name exists, return an
	 * empty string.
	 * @since  6.3.0 It checks custom product taxonomies
	 * @since  3.0.0
	 */
	private function get_template_loader_default_file() {
		
		if ( is_singular() && $this->has_template( 'single' ) ) {
			$default_file = 'single.php';
		} elseif ( ( is_archive() || is_home() || is_search() || is_404() || (function_exists('is_shop') && is_shop()) ) && $this->has_template( 'archive' ) ) {
			$default_file = 'archive.php';
		} else {
			$default_file = '';
		}
       
		return $default_file;
	}

	/**
	 * [has_template]
	 *
	 * @param  [string]  $field_key
	 *
	 * @return boolean | int
	 */
	public function has_template( $tmpType = '' ) {
		$template_ID = self::get_current_post_by_condition( $tmpType );
      
		if ( $template_ID ) {
			return $template_ID;
		}

		return false;
	}

	/**
	 * [get_template_id]
	 *
	 * @param  [string]  $field_key
	 * @param  [string]  $meta_key
	 *
	 * @return boolean | int
	 */
	public function get_template_id( $tmpType = '' ) {
		$template_ID = self::get_current_post_by_condition( $tmpType );

		if ( $template_ID ) {
			return $template_ID;
		}

		return false;
	}

	public function get_current_post_by_condition( $tmpType = '' ) {
		$query_args         = [
			'post_type'      => self::CPTTYPE,
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'date',
			'meta_query'     => array(
				array(
					'key'   => self::CPT_META . '_type',
					'value' => $tmpType,
				)
			)
		];
		$query              = new \WP_Query( $query_args );
		$count              = $query->post_count;
		$templates          = [];
		$templates_specific = [ 'specifics' => [] ];

		foreach ( $query->posts as $key => $post_id ) {

			$location   = get_post_meta( absint( $post_id ), self::CPT_META . '_location', true );
			$splocation = get_post_meta( absint( $post_id ), self::CPT_META . '_splocation', true );

			if ( ! empty( $location ) ) {
				if ( 'specifics' === $location ) {
					array_push( $templates_specific['specifics'],
						[ 'id' => $post_id, 'posts' => json_decode( $splocation ) ]
					);
				} else {
					$templates[ $location ] = $post_id;
				}
			}

			if ( $key === $count - 1 && ! empty( $templates_specific['specifics'] ) ) {
				$templates = array_merge( $templates, $templates_specific );
			}
		}

		wp_reset_postdata();
	
		if ( empty( $templates ) ) {
			return false;
		}

		//check for specific page and post
		if ( ! is_home() && ! is_archive() && array_key_exists( 'specifics', $templates ) ) {
			foreach ( $templates['specifics'] as $specific ) {
				$key = array_search( get_the_ID(), $specific['posts'] );
				if ( false !== $key ) {
					return $specific['id'];
				}
			}
		}

		//check 404 page
		if ( is_404() && array_key_exists( '404', $templates ) ) {
			return $templates['404'];
		}

		//check search page
		if ( is_search() && array_key_exists( 'search', $templates ) ) {
			return $templates['search'];
		}

		//check front page
		if ( is_front_page() && array_key_exists( 'front', $templates ) ) {
			return $templates['front'];
		}

		//check for blog/posts page
		if ( is_home() && array_key_exists( 'blog', $templates ) ) {
			return $templates['blog'];
		}
		
		if(function_exists('is_shop') && is_shop()){
			//check for WooCommerce shop archive				
			if ( function_exists( 'is_shop' ) && is_shop() && array_key_exists( 'product-archive', $templates ) ) {
				return $templates['product-archive'];
			}
		}
		//check for archive
		if ( is_archive() ) {
		  
			//check for all date archive
			if ( is_date() && array_key_exists( 'date', $templates ) ) {
				return $templates['date'];
			}

			//check for all author archive
			if ( is_author() && array_key_exists( 'author', $templates ) ) {
				return $templates['author'];
			}

			

			//check for custom post type archive
			$custom_archive = get_post_type() . '-archive';
			
			if(is_tax()){
			
				$get_queried_object = get_queried_object();		
				$taxonomy = $get_queried_object->taxonomy; // Get the taxonomy slug.
				$post_types = get_taxonomy($taxonomy)->object_type; // Get all post types for this taxonomy.
				
				if(is_array($post_types)){					
					foreach($post_types as $ptype){
						$custom_archive = $ptype . '-archive';
						if ( array_key_exists( $custom_archive, $templates ) ) {
							return $templates[ $custom_archive ];
						}
					}					
				}				
				
			}
			
			if ( array_key_exists( $custom_archive, $templates ) ) {
				return $templates[ $custom_archive ];
			}

			//all archives
			if ( array_key_exists( 'archives', $templates ) ) {
				return $templates['archives'];
			}
		}

		

		//check for singular
		if ( is_singular() ) {

			//if template type single ignore post type page
			if ( ( 'page' === get_post_type() || self::CPTTYPE === get_post_type() ) && 'single' === $tmpType ) {
				return false;
			}

			//check for custom post type singular
			$custom_single = get_post_type() . '-singular';
			if ( array_key_exists( $custom_single, $templates ) ) {
				return $templates[ $custom_single ];
			}

			//all singular
			if ( array_key_exists( 'singulars', $templates ) ) {
				return $templates['singulars'];
			}
		}

	
		//check for global
		if ( array_key_exists( 'global', $templates ) ) {
			return $templates['global'];
		}
	}

	/**
	 * Get current page type
	 *
	 * @return string Page Type.
	 * @since  1.0.0
	 *
	 */
	public function get_current_page_type() {
		$page_type = '';

		if ( is_404() ) {
			$page_type = 'is_404';
		} elseif ( is_search() ) {
			$page_type = 'is_search';
		} elseif ( is_archive() ) {
			$page_type = 'is_archive';

			if ( is_category() || is_tag() || is_tax() ) {
				$page_type = 'is_tax';
			} elseif ( is_date() ) {
				$page_type = 'is_date';
			} elseif ( is_author() ) {
				$page_type = 'is_author';
			} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
				$page_type = 'is_woo_shop_page';
			}
		} elseif ( is_home() ) {
			$page_type = 'is_home';
		} elseif ( is_front_page() ) {
			$page_type  = 'is_front_page';
			$current_id = get_the_id();
		} elseif ( is_singular() ) {
			$page_type  = 'is_singular';
			$current_id = get_the_id();
		} else {
			$current_id = get_the_id();
		}
		
		return $page_type;
	}

	/**
	 * [render_build_content]
	 *
	 * @param  [int]  $id
	 *
	 * @return string
	 */
	public static function render_build_content( $id ) {
		$output   = '';
		$document = ElementorPlugin::instance()->documents->get( $id );

		if ( $document && $document->is_built_with_elementor() ) {
			$output = ElementorPlugin::instance()->frontend->get_builder_content_for_display( $id, true );
		} else {
			$content = get_the_content( null, false, $id );

			if ( has_blocks( $content ) ) {
				$blocks = parse_blocks( $content );
				foreach ( $blocks as $block ) {
					$output .= do_shortcode( render_block( $block ) );
				}
			} else {
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );

				return $content;
			}
		}

		return $output;

	}

	// Set Builder Content For archive page
	public function archive_page_builder_content() {
		$archive_template_id = $this->get_template_id( 'archive' );
		if ( $archive_template_id != '0' ) {
			// PHPCS - should not be escaped.
			echo self::render_build_content( $archive_template_id );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	// Set Builder Content For single post
	public function single_post_builder_content() {
		$archive_template_id = $this->get_template_id( 'single' );
		if ( $archive_template_id != '0' ) {
			// PHPCS - should not be escaped.
			echo self::render_build_content( $archive_template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	///template///


	/**
	 * Print Admin Tab
	 *
	 * @param [array] $views
	 *
	 * @return array
	 */
	public function print_tabs( $views ) {
		$active_class = 'nav-tab-active';
		$current_type = '';
		if ( isset( $_GET['template_type'] ) ) {
			$active_class = '';
			$current_type = sanitize_key( $_GET['template_type'] );
		}
		?>
        <div id="wcf-template-tabs-wrapper" class="nav-tab-wrapper">
            <div class="wcf-menu-area">
                <a class="nav-tab <?php echo esc_attr( $active_class ); ?>"
                   href="edit.php?post_type=<?php echo esc_attr( self::CPTTYPE ); ?>">
					<?php echo esc_html__( 'All', 'animation-addons-for-elementor' ); ?>
                </a>
				<?php
				foreach ( self::get_template_type() as $tabkey => $tab ) {
					$active_class = ( $current_type == $tabkey ? 'nav-tab-active' : '' );
					$url          = 'edit.php?post_type=' . self::CPTTYPE . '&template_type=' . $tabkey;

					printf( '<a class="nav-tab %s" href="%s">%s</a>',
						esc_attr( $active_class ),
						esc_url( $url ),
						esc_html( $tab['label'] )
					);
				}
				?>
            </div>
        </div>
		<?php
		return $views;
	}

	/**
	 * Manage Template filter by template type
	 *
	 * @param \WP_Query $query	 
	 * @return void
	 */
	public function query_filter( \WP_Query $query ) {
		if ( ! is_admin() || ! empty( $query->query_vars['meta_key'] ) || self::CPTTYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( isset( $_GET['template_type'] ) && $_GET['template_type'] != '' && $_GET['template_type'] != 'all' ) {
			$type = isset( $_GET['template_type'] ) ? sanitize_key( $_GET['template_type'] ) : '';

			$query->query_vars['meta_key']     = self::CPT_META . '_type';
			$query->query_vars['meta_value']   = $type;
			$query->query_vars['meta_compare'] = '=';
		}
	}

	/**
	 * Manage Post Table columns
	 *
	 * @param [array] $columns
	 *
	 * @return array
	 */
	public function manage_columns( $columns ) {

		$column_date = $columns['date'];
		unset( $columns['date'] );

		$columns['type']   = esc_html__( 'Type', 'animation-addons-for-elementor' );
		$columns['status'] = esc_html__( 'Display', 'animation-addons-for-elementor' );
		$columns['date']   = esc_html( $column_date );

		return $columns;
	}

	/**
	 * Manage Custom column content
	 *
	 * @param [string] $column_name
	 * @param [int] $post_id
	 *
	 * @return void
	 */
	public function columns_content( $column_name, $post_id ) {
		$tmpType = get_post_meta( $post_id, self::CPT_META . '_type', true );

		if ( ! array_key_exists( $tmpType, self::get_template_type() ) ) {
			return;
		}

		if ( $column_name === 'type' ) {
			// PHPCS - should not be escaped.
			echo isset( self::get_template_type()[ $tmpType ] ) ? '<div class="column-tmptype">' . self::get_template_type()[ $tmpType ]['label'] . '</div>' : '-'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $column_name === 'status' ) {
			$tmpDisplay = get_post_meta( $post_id, self::CPT_META . '_location', true );
			?>
            <div class="post-status">
            <strong>Display: </strong>
			<?php echo esc_html( $tmpDisplay ); ?>
            </div><?php
		}

	}

	/**
	 * Get Template Type
	 *
	 * @return array
	 */
	public static function get_template_type() {

		$template_type = [
			'header'  => [
				'label'     => esc_html__( 'Header', 'animation-addons-for-elementor' ),
				'optionkey' => 'header'
			],
			'footer'  => [
				'label'     => esc_html__( 'Footer', 'animation-addons-for-elementor' ),
				'optionkey' => 'footer'
			],
			'archive' => [
				'label'     => esc_html__( 'Archive/404/Search', 'animation-addons-for-elementor' ),
				'optionkey' => 'archivepage'
			],			
			'single'  => [
				'label'     => esc_html__( 'Single', 'animation-addons-for-elementor' ),
				'optionkey' => 'singlepage'
			],
		];

		return apply_filters( 'wcf_builder_template_types', $template_type );

	}

	/**
	 * Get header footer location selection options.
	 *
	 * @return array
	 */
	public static function get_hf_location_selections() {
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types( $args, 'objects' );

		$special_pages = array(
			'404'    => esc_html__( '404 Page', 'animation-addons-for-elementor' ),
			'search' => esc_html__( 'Search Page', 'animation-addons-for-elementor' ),
			'blog'   => esc_html__( 'Blog / Posts Page', 'animation-addons-for-elementor' ),
			'front'  => esc_html__( 'Front Page', 'animation-addons-for-elementor' ),
			'date'   => esc_html__( 'Date Archive', 'animation-addons-for-elementor' ),
			'author' => esc_html__( 'Author Archive', 'animation-addons-for-elementor' ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$special_pages['woo-shop'] = esc_html__( 'WooCommerce Shop Page', 'animation-addons-for-elementor' );
		}

		$selection_options = array(
			'basic' => array(
				'label' => esc_html__( 'Basic', 'animation-addons-for-elementor' ),
				'value' => array(
					''          => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'global'    => esc_html__( 'Entire Website', 'animation-addons-for-elementor' ),
					'singulars' => esc_html__( 'All Singulars', 'animation-addons-for-elementor' ),
					'archives'  => esc_html__( 'All Archives', 'animation-addons-for-elementor' ),
				),
			),

			'special-pages' => array(
				'label' => esc_html__( 'Special Pages', 'animation-addons-for-elementor' ),
				'value' => $special_pages,
			),
		);


		foreach ( $post_types as $post_type ) {
			if ( 'page' === $post_type->name ) {
				$selection_options[ $post_type->name ] = [
					'label' => esc_html( $post_type->label ),
					'value' => array(
						'all' . $post_type->name => esc_html( 'All' . $post_type->label ),
					),
				];
			} else {
				$selection_options[ $post_type->name ] = [
					'label' => esc_html( $post_type->label ),
					'value' => array(
						$post_type->name . '-archive'   => esc_html( $post_type->label . ' Archive' ),
						$post_type->name . '-singulars' => esc_html( $post_type->label . ' Singulars' ),
					),
				];
			}
		}


		$selection_options['specific-target'] = array(
			'label' => esc_html__( 'Specific Target', 'animation-addons-for-elementor' ),
			'value' => array(
				'specifics' => esc_html__( 'Specific Pages / Posts.', 'animation-addons-for-elementor' ),
			),
		);

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'wcf_display_hf_list', $selection_options );
	}

	/**
	 * Get archive location selection options.
	 *
	 * @return array
	 */
	public static function get_archive_location_selections() {
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types( $args, 'objects' );

		//unset unnecessary post type
		unset( $post_types['page'] );
		unset( $post_types['post'] );
		//unset( $post_types['product'] );
		unset( $post_types[ self::CPTTYPE ] );

		$special_pages = array(
			'404'    => esc_html__( '404 Page', 'animation-addons-for-elementor' ),
			'search' => esc_html__( 'Search Page', 'animation-addons-for-elementor' ),
			'blog'   => esc_html__( 'Blog / Posts Page', 'animation-addons-for-elementor' ),
			'date'   => esc_html__( 'Date Archive', 'animation-addons-for-elementor' ),
			'author' => esc_html__( 'Author Archive', 'animation-addons-for-elementor' ),
		);

		$selection_options = array(
			'basic' => array(
				'label' => esc_html__( 'Basic', 'animation-addons-for-elementor' ),
				'value' => array(
					''         => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'archives' => esc_html__( 'All Archives', 'animation-addons-for-elementor' ),
				),
			),

			'special-pages' => array(
				'label' => esc_html__( 'Special Pages', 'animation-addons-for-elementor' ),
				'value' => $special_pages,
			),
		);


		foreach ( $post_types as $post_type ) {
			$selection_options[ $post_type->name ] = [
				'label' => esc_html( $post_type->label ),
				'value' => array(
					$post_type->name . '-archive' => esc_html( $post_type->label . ' Archive' ),
				),
			];
		}

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'wcf_display_archive_list', $selection_options );
	}

	/**
	 * Get single location selection options.
	 *
	 * @return array
	 */
	public static function get_single_location_selections() {
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types( $args, 'objects' );

		//unset unnecessary post type
		unset( $post_types['page'] );
		//unset( $post_types['product'] );
		unset( $post_types[ self::CPTTYPE ] );

		$selection_options = array(
			'basic' => array(
				'label' => esc_html__( 'Basic', 'animation-addons-for-elementor' ),
				'value' => array(
					'singulars' => esc_html__( 'All Singular', 'animation-addons-for-elementor' ),
				),
			),
		);


		foreach ( $post_types as $post_type ) {
			$selection_options[ $post_type->name ] = [
				'label' => esc_html( $post_type->label ),
				'value' => array(
					$post_type->name . '-singular' => esc_html( $post_type->label . ' Singular' ),
				),
			];
		}

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'wcf_display_archive_list', $selection_options );
	}

	/**
	 * Register Builder Custom post
	 *
	 * @return void
	 */
	public function register_custom_post_type() {

		$labels = array(
			'name'                  => esc_html_x( 'AAE Builder', 'Post Type General Name', 'animation-addons-for-elementor' ),
			'singular_name'         => esc_html_x( 'AAE Builder', 'Post Type Singular Name', 'animation-addons-for-elementor' ),
			'menu_name'             => esc_html__( 'AAE Builder', 'animation-addons-for-elementor' ),
			'name_admin_bar'        => esc_html__( 'AAE Builder', 'animation-addons-for-elementor' ),
			'archives'              => esc_html__( 'Template Archives', 'animation-addons-for-elementor' ),
			'attributes'            => esc_html__( 'Template Attributes', 'animation-addons-for-elementor' ),
			'parent_item_colon'     => esc_html__( 'Parent Item:', 'animation-addons-for-elementor' ),
			'all_items'             => esc_html__( 'Templates', 'animation-addons-for-elementor' ),
			'add_new_item'          => esc_html__( 'Add New Template', 'animation-addons-for-elementor' ),
			'add_new'               => esc_html__( 'Add New', 'animation-addons-for-elementor' ),
			'new_item'              => esc_html__( 'New Template', 'animation-addons-for-elementor' ),
			'edit_item'             => esc_html__( 'Edit Template', 'animation-addons-for-elementor' ),
			'update_item'           => esc_html__( 'Update Template', 'animation-addons-for-elementor' ),
			'view_item'             => esc_html__( 'View Template', 'animation-addons-for-elementor' ),
			'view_items'            => esc_html__( 'View Templates', 'animation-addons-for-elementor' ),
			'search_items'          => esc_html__( 'Search Templates', 'animation-addons-for-elementor' ),
			'not_found'             => esc_html__( 'Not found', 'animation-addons-for-elementor' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'animation-addons-for-elementor' ),
			'featured_image'        => esc_html__( 'Featured Image', 'animation-addons-for-elementor' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'animation-addons-for-elementor' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'animation-addons-for-elementor' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'animation-addons-for-elementor' ),
			'insert_into_item'      => esc_html__( 'Insert into Template', 'animation-addons-for-elementor' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this Template', 'animation-addons-for-elementor' ),
			'items_list'            => esc_html__( 'Templates list', 'animation-addons-for-elementor' ),
			'items_list_navigation' => esc_html__( 'Templates list navigation', 'animation-addons-for-elementor' ),
			'filter_items_list'     => esc_html__( 'Filter from list', 'animation-addons-for-elementor' ),
		);

		$args = array(
			'label'               => esc_html__( 'Theme Builder', 'animation-addons-for-elementor' ),
			'description'         => esc_html__( 'AAE Theme Builder', 'animation-addons-for-elementor' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'elementor', 'thumbnail' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'wcf-template',
				'pages'      => false,
				'with_front' => true,
				'feeds'      => false,
			),
			'query_var'           => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
			'rest_base'           => self::CPTTYPE,
		);

		register_post_type( self::CPTTYPE, $args );

		flush_rewrite_rules();
	}

	/**
	 * Print Template edit popup
	 *
	 * @return void
	 */
	public function print_popup() {
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == self::CPTTYPE ) {
			?>
            <script type="text/template" id="tmpl-wcf-addons-ctppopup">
                <div class="wcf-addons-template-edit-popup-area">
                    <div class="wcf-addons-body-overlay"></div>
                    <div class="wcf-addons-template-edit-popup">

                        <div class="wcf-addons-template-edit-header">
                            <h3 class="wcf-addons-template-edit-setting-title">
                                {{{data.heading.head}}}
                            </h3>
                            <span class="wcf-addons-template-edit-cross">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                     class="bi bi-x-lg" viewBox="0 0 16 16"><path
                                            d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/></svg>
                            </span>
                        </div>

                        <div class="wcf-addons-template-edit-body">

                            <div class="wcf-addons-template-edit-field">
                                <label class="wcf-addons-template-edit-label">{{{ data.heading.fields.name.title
                                    }}}</label>
                                <input class="wcf-addons-template-edit-input" id="wcf-addons-template-title" type="text"
                                       name="wcf-addons-template-title"
                                       placeholder="{{ data.heading.fields.name.placeholder }}">
                            </div>

                            <div class="wcf-addons-template-edit-field">
                                <label class="wcf-addons-template-edit-label">{{{data.heading.fields.type}}}</label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-template-type"
                                        id="wcf-addons-template-type">
                                    <#
                                    _.each( data.templatetype, function( item, key ) {

                                    #>
                                    <option value="{{ key }}">{{{ item.label }}}</option>
                                    <#

                                    } );
                                    #>
                                </select>
                            </div>

                            <div class="wcf-addons-template-edit-field hf-location hidden">
                                <label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-hf-display-type"
                                        id="wcf-addons-hf-display-type">
                                    <#
                                    _.each( data.hflocation, function( items, keys ) {
                                    #>
                                    <optgroup label="{{{ items.label }}}">
                                        <#
                                        _.each( items.value, function( item, key ) {
                                        #>
                                        <option value="{{ key }}">{{{ item }}}</option>
                                        <#
                                        } );
                                        #>
                                    </optgroup>
                                    <#
                                    } );
                                    #>
                                </select>
                            </div>

                            <div class="wcf-addons-template-edit-field hf-s-location hidden">
                                <label class="wcf-addons-template-edit-label"></label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-hf-s-display-type[]"
                                        id="wcf-addons-hf-s-display-type" multiple="multiple">
                                </select>
                            </div>

                            <div class="wcf-addons-template-edit-field archive-location hidden">
                                <label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-archive-display-type"
                                        id="wcf-addons-archive-display-type">
                                    <#
                                    _.each( data.archivelocation, function( items, keys ) {
                                    #>
                                    <optgroup label="{{{ items.label }}}">
                                        <#
                                        _.each( items.value, function( item, key ) {
                                        #>
                                        <option value="{{ key }}">{{{ item }}}</option>
                                        <#
                                        } );
                                        #>
                                    </optgroup>
                                    <#
                                    } );
                                    #>
                                </select>
                            </div>

                            <div class="wcf-addons-template-edit-field single-location hidden">
                                <label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-single-display-type"
                                        id="wcf-addons-single-display-type">
                                    <#
                                    _.each( data.singlelocation, function( items, keys ) {
                                    #>
                                    <optgroup label="{{{ items.label }}}">
                                        <#
                                        _.each( items.value, function( item, key ) {
                                        #>
                                        <option value="{{ key }}">{{{ item }}}</option>
                                        <#
                                        } );
                                        #>
                                    </optgroup>
                                    <#
                                    } );
                                    #>
                                </select>
                            </div>

                        </div>

                        <div class="wcf-addons-template-edit-footer">

                            <div class="wcf-addons-template-button-group">
                                <div class="wcf-addons-template-button-item wcf-addons-editor-elementor {{ data.haselementor === 'yes' ? 'button-show' : '' }}">
                                    <button class="wcf-addons-tmp-elementor button">{{{
                                        data.heading.buttons.elementor.label
                                        }}}
                                    </button>
                                </div>
                                <div class="wcf-addons-template-button-item">
                                    <button class="wcf-addons-tmp-save button button-primary">{{{
                                        data.heading.buttons.save.label }}}
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </script>
			<?php
		}
	}

	/**
	 * Save Template
	 *
	 * @return void
	 */
	public function save_template_request() {
		if ( isset( $_POST ) ) {

			if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) ) {
				$errormessage = array(
					'message' => esc_html__( 'You are unauthorize to adding template!', 'animation-addons-for-elementor' )
				);
				wp_send_json_error( $errormessage );
			}

			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wcf_tmp_nonce' ) ) {
				$errormessage = array(
					'message' => esc_html__( 'Nonce Varification Faild !', 'animation-addons-for-elementor' )
				);
				wp_send_json_error( $errormessage );
			}

			$title            = ! empty( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$tmpid            = ! empty( $_POST['tmpId'] ) ? sanitize_text_field( wp_unslash( $_POST['tmpId'] ) ) : '';
			$tmpType          = ! empty( $_POST['tmpType'] ) ? sanitize_text_field( wp_unslash( $_POST['tmpType'] ) ) : 'single';
			$tmplocation      = ! empty( $_POST['tmpDisplay'] ) ? sanitize_text_field( wp_unslash( $_POST['tmpDisplay'] ) ) : '';
			$specificsDisplay = ! empty( $_POST['specificsDisplay'] ) ? sanitize_text_field( wp_unslash( $_POST['specificsDisplay'] ) ) : '';

			$data = [
				'title'         => $title,
				'id'            => $tmpid,
				'tmptype'       => $tmpType,
				'tmplocation'   => $tmplocation,
				'tmpSpLocation' => $specificsDisplay,
			];


			if ( $tmpid ) {
				$this->update( $data );
			} else {
				$this->insert( $data );
			}

		} else {
			$errormessage = array(
				'message' => esc_html__( 'Post request dose not found', 'animation-addons-for-elementor' )
			);
			wp_send_json_error( $errormessage );
		}

	}

	/**
	 * Get Template data by id
	 *
	 * @return void
	 */
	public function get_post_By_id() {
		if ( isset( $_POST ) ) {

			if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) ) {
				$errormessage = array(
					'message' => esc_html__( 'You are unauthorize to adding template!', 'animation-addons-for-elementor' )
				);
				wp_send_json_error( $errormessage );
			}

			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wcf_tmp_nonce' ) ) {
				$errormessage = array(
					'message' => esc_html__( 'Nonce Varification Faild !', 'animation-addons-for-elementor' )
				);
				wp_send_json_error( $errormessage );
			}

			$tmpid            = ! empty( $_POST['tmpId'] ) ? sanitize_text_field( wp_unslash( $_POST['tmpId'] ) ) : '';
			$postdata         = get_post( $tmpid );
			$tmpType          = ! empty( get_post_meta( $tmpid, self::CPT_META . '_type', true ) ) ? get_post_meta( $tmpid, self::CPT_META . '_type', true ) : 'single';
			$tmpLocation      = ! empty( get_post_meta( $tmpid, self::CPT_META . '_location', true ) ) ? get_post_meta( $tmpid, self::CPT_META . '_location', true ) : '';
			$specificsDisplay = ! empty( get_post_meta( $tmpid, self::CPT_META . '_splocation', true ) ) ? get_post_meta( $tmpid, self::CPT_META . '_splocation', true ) : '';
			$spLocations      = [];

			if ( ! empty( $specificsDisplay ) ) {
				foreach ( json_decode( $specificsDisplay ) as $item ) {
					$sppost               = get_post( intval( $item ) );
					$spLocations[ $item ] = $sppost->post_title;
				}
			}

			$data = [
				'tmpTitle'      => $postdata->post_title,
				'tmpType'       => $tmpType,
				'tmpLocation'   => $tmpLocation,
				'tmpSpLocation' => $spLocations,
			];
			wp_send_json_success( $data );

		} else {
			$errormessage = array(
				'message' => esc_html__( 'Some thing is worng !', 'animation-addons-for-elementor' )
			);
			wp_send_json_error( $errormessage );
		}

	}

	/**
	 * Ajax handeler to return the posts based on the search query.
	 * When searching for the post/pages only titles are searched for.
	 *
	 * @since  1.0.0
	 */
	function get_posts_by_query() {

		if ( isset( $_POST ) ) {


			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wcf_tmp_nonce' ) ) {
				$errormessage = array(
					'message' => esc_html__( 'Nonce Varification Faild !', 'animation-addons-for-elementor' )
				);
				wp_send_json_error( $errormessage );
			}

			$search_string = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
			$data          = array();
			$result        = array();

			$args = array(
				'public'   => true,
				'_builtin' => false,
			);

			$output     = 'names'; // names or objects, note names is the default.
			$operator   = 'and'; // also supports 'or'.
			$post_types = get_post_types( $args, $output, $operator );

			unset( $post_types[ self::CPTTYPE ] ); //Exclude wcf post type templates.

			$post_types['Posts'] = 'post';
			$post_types['Pages'] = 'page';

			foreach ( $post_types as $key => $post_type ) {
				$data = array();

				add_filter( 'posts_search', array( $this, 'search_only_titles' ), 10, 2 );

				$query = new \WP_Query(
					array(
						's'              => $search_string,
						'post_type'      => $post_type,
						'posts_per_page' => - 1,
					)
				);

				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$title  = get_the_title();
						$title  .= ( 0 != $query->post->post_parent ) ? ' (' . get_the_title( $query->post->post_parent ) . ')' : '';
						$id     = get_the_id();
						$data[] = array(
							'id'   => $id,
							'text' => $title,
						);
					}
				}

				if ( is_array( $data ) && ! empty( $data ) ) {
					$result[] = array(
						'text'     => $key,
						'children' => $data,
					);
				}
			}

			$data = array();

			wp_reset_postdata();

			// return the result in json.
			wp_send_json( $result );
		} else {
			$errormessage = array(
				'message' => esc_html__( 'Some thing is worng !', 'animation-addons-for-elementor' )
			);
			wp_send_json_error( $errormessage );
		}
	}

	/**
	 * Return search results only by post title.
	 * This is only run from hfe_get_posts_by_query()
	 *
	 * @param  (string)   $search   Search SQL for WHERE clause.
	 * @param  (WP_Query) $wp_query The current WP_Query object.
	 *
	 * @return (string) The Modified Search SQL for WHERE clause.
	 */
	function search_only_titles( $search, $wp_query ) {
		if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
			global $wpdb;

			$q = $wp_query->query_vars;
			$n = ! empty( $q['exact'] ) ? '' : '%';

			$search = array();

			foreach ( (array) $q['search_terms'] as $term ) {
				$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like( $term ) . $n );
			}

			if ( ! is_user_logged_in() ) {
				$search[] = "$wpdb->posts.post_password = ''";
			}

			$search = ' AND ' . implode( ' AND ', $search );
		}

		return $search;
	}

	/**
	 * Template Insert
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function insert( $data ) {

		$args        = [
			'post_type'   => self::CPTTYPE,
			'post_status' => $data['tmptype'] == 'popup' ? 'draft' : 'publish',
			'post_title'  => $data['title'],
		];
		$new_post_id = wp_insert_post( $args );

		if ( $new_post_id ) {
			$return = array(
				'message' => esc_html__( 'Template has been inserted', 'animation-addons-for-elementor' ),
				'id'      => $new_post_id,
			);

			// Meta data
			update_post_meta( $new_post_id, self::CPT_META . '_type', $data['tmptype'] );
			update_post_meta( $new_post_id, self::CPT_META . '_location', $data['tmplocation'] );
			update_post_meta( $new_post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $new_post_id, '_wp_page_template', 'elementor_canvas' );

			//specific page and post template header footer
			if ( 'header' === $data['tmptype'] || 'footer' === $data['tmptype'] ) {
				update_post_meta( $new_post_id, self::CPT_META . '_splocation', $data['tmpSpLocation'] );
			}

			wp_send_json_success( $return );

		} else {
			$errormessage = array(
				'message' => esc_html__( 'Some thing is worng !', 'animation-addons-for-elementor' )
			);
			wp_send_json_error( $errormessage );
		}

	}

	/**
	 * Template Update
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function update( $data ) {

		$update_post_args = array(
			'ID'         => $data['id'],
			'post_title' => $data['title'],
		);
		wp_update_post( $update_post_args );

		// Update Meta data
		update_post_meta( $data['id'], self::CPT_META . '_type', $data['tmptype'] );
		update_post_meta( $data['id'], self::CPT_META . '_location', $data['tmplocation'] );

		//specific page and post template header footer
		if ( 'header' === $data['tmptype'] || 'footer' === $data['tmptype'] ) {
			update_post_meta( $data['id'], self::CPT_META . '_splocation', $data['tmpSpLocation'] );
		} else {
			delete_post_meta( $data['id'], self::CPT_META . '_splocation' );
		}

		$return = array(
			'message' => esc_html__( 'Template has been updated', 'animation-addons-for-elementor' ),
			'id'      => $data['id']
		);
		wp_send_json_success( $return );

	}

	/**
	 * Manage Scripts
	 *
	 * @param [string] $hook
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == self::CPTTYPE ) {

			// CSS
			wp_enqueue_style( 'select2', WCF_ADDONS_URL . '/assets/css/select2.min.css' );
			wp_enqueue_style( 'wcf-theme-builder', WCF_ADDONS_URL . '/assets/css/theme-builder.min.css' );

			// JS
			wp_enqueue_script( 'select2', WCF_ADDONS_URL . '/assets/js/select2.min.js', array( 'jquery' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'wcf-theme-builder', WCF_ADDONS_URL . '/assets/js/theme-builder.js', array(
				'jquery',
				'wp-util'
			), WCF_ADDONS_VERSION, true );

			$localize_data = [
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wcf_tmp_nonce' ),
				'adminURL'        => admin_url(),
				'hflocation'      => self::get_hf_location_selections(),
				'archivelocation' => self::get_archive_location_selections(),
				'singlelocation'  => self::get_single_location_selections(),
				'templatetype'    => self::get_template_type(),
				'labels'          => [
					'fields'  => [
						'name'    => [
							'title'       => esc_html__( 'Name', 'animation-addons-for-elementor' ),
							'placeholder' => esc_html__( 'Enter a template name', 'animation-addons-for-elementor' )
						],
						'type'    => esc_html__( 'Type', 'animation-addons-for-elementor' ),
						'display' => esc_html__( 'Display', 'animation-addons-for-elementor' ),
					],
					'head'    => esc_html__( 'Template Settings', 'animation-addons-for-elementor' ),
					'buttons' => [
						'elementor' => [
							'label' => esc_html__( 'Edit With Elementor', 'animation-addons-for-elementor' ),
							'link'  => '#'
						],
						'save'      => [
							'label'  => esc_html__( 'Save Settings', 'animation-addons-for-elementor' ),
							'saving' => esc_html__( 'Saving...', 'animation-addons-for-elementor' ),
							'saved'  => esc_html__( 'All Data Saved', 'animation-addons-for-elementor' ),
							'link'   => '#'
						]
					],
				]
			];
			wp_localize_script( 'wcf-theme-builder', 'WCF_Theme_Builder', $localize_data );

		}

	}

	/**
	 * [init] Assets Initializes
	 * @return [void]
	 */
	public function init() {
		//Register Custom Post Type
		$this->register_custom_post_type();
	}

	/**
	 * [admin_menu] Add Post type Submenu
	 *
	 * @return void
	 */
	public function admin_menu() {
		$link_custom_post = 'edit.php?post_type=' . self::CPTTYPE;
		add_submenu_page(
			'wcf_addons_page',
			esc_html__( 'Theme Builder', 'animation-addons-for-elementor' ),
			esc_html__( 'Theme Builder', 'animation-addons-for-elementor' ),
			'manage_options',
			$link_custom_post,
			null
		);
	}

}

WCF_Theme_Builder::instance();
