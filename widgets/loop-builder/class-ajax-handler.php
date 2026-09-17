<?php
namespace Wealcoder\AnimationAddons\Widgets\Loop_Builder;

use Wealcoder\AnimationAddons\Nonce;

use Wealcoder\AnimationAddons\Ajax_Alias;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AJAX Handler Class
 *
 * Handles AJAX requests for autocomplete and pagination
 */
class Ajax_Handler {

	/**
	 * Instance.
	 *
	 * @var object $_instance Class instance.
	 */
	private static $_instance = null;

	/**
	 * Instance.
	 *
	 * @return object Class instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function __construct() {
		// Pagination actions, posted by the front-end bundle. The unprefixed
		// `clb_*` names stay answered while a page cache or combined JS bundle
		// can still be posting them; remove the aliases in 4.4.
		// (The `clb_get_posts/terms/authors/templates/taxonomies` autocomplete
		// actions had no caller in either plugin and were removed in 4.2.)
		Ajax_Alias::register( 'clb_load_more', 'aaeaddon_clb_load_more', array( $this, 'ajax_load_more' ), true );
		Ajax_Alias::register( 'clb_load_page', 'aaeaddon_clb_load_page', array( $this, 'ajax_load_page' ), true );
	}

	/**
	 * AJAX handler for a load more pagination.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_load_more() {
		try {
			// Checked inline so the check is in the same scope as the request
			// reads below (the sniff cannot follow a helper).
			if ( ! check_ajax_referer( Nonce::action( Nonce::LOOP_BUILDER, 'nonce' ), 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'animation-addons-for-elementor' ) ), 403 );
			}

			$safe_settings = isset( $_POST['settings'] ) ? map_deep( (array) wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : array();
			$settings      = $this->sanitize_settings( $safe_settings );
			
			$page = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;

			if ( empty( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => 'No template specified' ) );
			}
			// A public endpoint must not render any document a visitor names.
			if ( ! Template_Manager::is_public_loop_template( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid template.', 'animation-addons-for-elementor' ) ), 403 );
			}

			// Security: Enforce a hard maximum limit on posts per page to prevent DoS via heavy queries.
			if ( isset( $settings['posts_per_page'] ) && (int) $settings['posts_per_page'] > 100 ) {
				$settings['posts_per_page'] = 100;
			}

			$settings['paged'] = $page;

			$query_manager = Query_Manager::instance();
			$query         = $query_manager->get_query( $settings );

			$html     = '';
			$has_more = false;

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$classes = get_post_class( 'e-loop-item aae-loop-item', get_the_ID() );
					$html   .= '<article class="' . esc_attr( implode( ' ', $classes ) ) . '" data-elementor-type="loop-item">';
					$html   .= Template_Manager::render_template( $settings['template_id'], get_the_ID() );
					$html   .= '</article>';
				}
				wp_reset_postdata();

				$has_more = $page < $query->max_num_pages;
			}

			wp_send_json_success(
				array(
					'html'      => $html,
					'has_more'  => $has_more,
					'next_page' => $page + 1,
					'max_pages' => $query->max_num_pages,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Sanitize widget settings.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return array Sanitized settings.
	 */
	private function sanitize_settings( $settings ) {
		$sanitized = array();

		$string_fields = array( 'source', 'orderby', 'order', 'meta_key', 'meta_value', 'meta_compare' );
		foreach ( $string_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $settings[ $field ] );
			}
		}

		$int_fields = array( 'template_id', 'posts_per_page', 'paged' );
		foreach ( $int_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$sanitized[ $field ] = intval( $settings[ $field ] );
			}
		}

		$array_fields = array( 'include_posts', 'exclude_posts', 'include_terms', 'exclude_terms', 'include_authors', 'exclude_authors' );
		foreach ( $array_fields as $field ) {
			if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
				$sanitized[ $field ] = array_map( 'intval', $settings[ $field ] );
			}
		}

		return $sanitized;
	}

	/**
	 * Get post-types for autocomplete.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$options    = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * AJAX handler for page loading.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_load_page() {
		try {
			// Checked inline so the check is in the same scope as the request
			// reads below (the sniff cannot follow a helper).
			if ( ! check_ajax_referer( Nonce::action( Nonce::LOOP_BUILDER, 'nonce' ), 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'animation-addons-for-elementor' ) ), 403 );
			}

			$safe_settings = isset( $_POST['settings'] ) ? map_deep( (array) wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : array();
			$settings      = $this->sanitize_settings( $safe_settings );
			
			$page = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : ( $settings['paged'] ?? 1 );

			if ( empty( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => 'No template specified' ) );
			}
			// A public endpoint must not render any document a visitor names.
			if ( ! Template_Manager::is_public_loop_template( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid template.', 'animation-addons-for-elementor' ) ), 403 );
			}

			// Security: Enforce a hard maximum limit on posts per page to prevent DoS via heavy queries.
			if ( isset( $settings['posts_per_page'] ) && (int) $settings['posts_per_page'] > 100 ) {
				$settings['posts_per_page'] = 100;
			}

			$settings['paged'] = $page;

			$query_manager = Query_Manager::instance();
			$query         = $query_manager->get_query( $settings );

			$html = '';

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$classes = get_post_class( 'e-loop-item aae-loop-item', get_the_ID() );
					$html   .= '<article class="' . esc_attr( implode( ' ', $classes ) ) . '" data-elementor-type="loop-item">';
					$html   .= Template_Manager::render_template( $settings['template_id'], get_the_ID() );
					$html   .= '</article>';
				}
				wp_reset_postdata();
			}

			$pagination = '';
			if ( $query->max_num_pages > 1 ) {
				$pagination_type = isset( $settings['pagination_type'] ) ? $settings['pagination_type'] : 'numbers';
				$page_limit      = isset( $settings['pagination_page_limit'] ) ? intval( $settings['pagination_page_limit'] ) : 5;

				$base_url = $this->get_pagination_base_url();

				$args = array(
					'base'      => $base_url . '%_%',
					'format'    => '%#%',
					'total'     => min( $page_limit, $query->max_num_pages ),
					'current'   => $page,
					'type'      => 'list',
					'mid_size'  => 2,
					'end_size'  => 1,
					'prev_next' => false,
				);

				if ( 'numbers_and_prev_next' === $pagination_type ) {
					$prev_icon = isset( $settings['navigation_prev_icon'] ) ? $settings['navigation_prev_icon'] : array(
						'value'   => 'eicon-chevron-left',
						'library' => 'eicons',
					);
					$next_icon = isset( $settings['navigation_next_icon'] ) ? $settings['navigation_next_icon'] : array(
						'value'   => 'eicon-chevron-right',
						'library' => 'eicons',
					);

					$args['prev_text'] = '<i class="' . esc_attr( $prev_icon['value'] ) . '" aria-hidden="true"></i><span>' . __( 'Previous', 'animation-addons-for-elementor' ) . '</span>';
					$args['next_text'] = '<span>' . __( 'Next', 'animation-addons-for-elementor' ) . '</span><i class="' . esc_attr( $next_icon['value'] ) . '" aria-hidden="true"></i>';
					$args['prev_next'] = true;
				} elseif ( 'prev_next' === $pagination_type ) {
					$prev_icon = isset( $settings['navigation_prev_icon'] ) ? $settings['navigation_prev_icon'] : array(
						'value'   => 'eicon-chevron-left',
						'library' => 'eicons',
					);
					$next_icon = isset( $settings['navigation_next_icon'] ) ? $settings['navigation_next_icon'] : array(
						'value'   => 'eicon-chevron-right',
						'library' => 'eicons',
					);

					$args['prev_text'] = '<i class="' . esc_attr( $prev_icon['value'] ) . '" aria-hidden="true"></i><span>' . __( 'Previous', 'animation-addons-for-elementor' ) . '</span>';
					$args['next_text'] = '<span>' . __( 'Next', 'animation-addons-for-elementor' ) . '</span><i class="' . esc_attr( $next_icon['value'] ) . '" aria-hidden="true"></i>';
					$args['prev_next'] = true;
				}

				$pagination = paginate_links( $args );

				if ( $pagination ) {
					$pagination = '<nav class="custom-loop-pagination-wrapper" aria-label="' . esc_attr__( 'Posts pagination', 'animation-addons-for-elementor' ) . '">' . $pagination . '</nav>';
				}
			}

			wp_send_json_success(
				array(
					'html'         => $html,
					'pagination'   => $pagination,
					'current_page' => $page,
					'max_pages'    => $query->max_num_pages,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Get taxonomies for autocomplete.
	 *
	 * @param string $post_type Post type.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_taxonomies( $post_type = '' ) {
		if ( empty( $post_type ) ) {
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		} else {
			$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		}

		$options = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy->public || $taxonomy->publicly_queryable ) {
				$options[ $taxonomy->name ] = $taxonomy->label;
			}
		}

		return $options;
	}

	/**
	 * Get pagination base URL for current context.
	 *
	 * @return string Base URL for pagination.
	 */
	private function get_pagination_base_url() {
		global $wp_rewrite;

		$current_url = $this->get_current_page_url();

		$current_url = remove_query_arg( array( 'paged', 'page' ), $current_url );

		if ( $wp_rewrite->using_permalinks() ) {
			$base_url = $current_url;

			if ( ! preg_match( '/\/$/', $base_url ) ) {
				$base_url .= '/';
			}

			return $base_url;
		} else {
			return $current_url . ( strpos( $current_url, '?' ) !== false ? '&' : '?' ) . 'paged=';
		}
	}

	/**
	 * Get current page URL.
	 *
	 * @return string Current page URL.
	 */
	private function get_current_page_url() {
		// `isset( $x ) ?? f( $x )` never reached f() -- isset() is never null --
		// so this returned "1" and every pagination link was built on it.
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' === $host ) {
			return home_url( $uri );
		}

		return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri );
	}
}
