<?php
namespace WCF_ADDONS\Widgets\Loop_Builder;

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
		// Autocomplete actions.
		add_action( 'wp_ajax_clb_get_posts', array( $this, 'ajax_get_posts' ) );
		add_action( 'wp_ajax_clb_get_terms', array( $this, 'ajax_get_terms' ) );
		add_action( 'wp_ajax_clb_get_authors', array( $this, 'ajax_get_authors' ) );
		add_action( 'wp_ajax_clb_get_templates', array( $this, 'ajax_get_templates' ) );

		// Pagination actions.
		add_action( 'wp_ajax_clb_load_more', array( $this, 'ajax_load_more' ) );
		add_action( 'wp_ajax_nopriv_clb_load_more', array( $this, 'ajax_load_more' ) );
		add_action( 'wp_ajax_clb_load_page', array( $this, 'ajax_load_page' ) );
		add_action( 'wp_ajax_nopriv_clb_load_page', array( $this, 'ajax_load_page' ) );

		// Additional AJAX actions.
		add_action( 'wp_ajax_clb_get_taxonomies', array( $this, 'ajax_get_taxonomies' ) );
	}

	/**
	 * AJAX handler for post-autocomplete.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_posts() {
		$this->verify_nonce();

		$search    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_posts_for_autocomplete( $search, $post_type );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for term autocomplete.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_terms() {
		$this->verify_nonce();

		$search   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : 'category'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_terms_for_autocomplete( $search, $taxonomy );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for author autocomplete.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_authors() {
		$this->verify_nonce();

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_authors_for_autocomplete( $search );

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for template autocomplete.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_templates() {
		$this->verify_nonce();

		$search      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$source_type = isset( $_GET['source_type'] ) ? sanitize_text_field( wp_unslash( $_GET['source_type'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		$args = array(
			'post_type'      => 'wcf-addons-template',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'wcf-addons-template-meta_type',
					'value'   => 'loop-builder',
					'compare' => '=',
				),
			),
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( ! empty( $source_type ) ) {
			$args['meta_query'][] = array(
				'key'     => '_elementor_source',
				'value'   => $source_type,
				'compare' => '=',
			);
		}

		$templates = get_posts( $args );
		$results   = array();

		foreach ( $templates as $template ) {
			$source       = get_post_meta( $template->ID, '_elementor_source', true );
			$source_label = '';

			if ( $source ) {
				$post_type_obj = get_post_type_object( $source );
				$source_label  = $post_type_obj ? ' (' . $post_type_obj->label . ')' : ' (' . ucfirst( $source ) . ')';
			}

			$results[] = array(
				'id'   => $template->ID,
				'text' => $template->post_title . $source_label,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for a load more pagination.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_load_more() {
		try {
			$this->verify_nonce();

			$settings = isset( $_POST['settings'] ) ? $this->sanitize_settings( wp_unslash( $_POST['settings'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$page     = intval( $_POST['page'] ?? 1 );

			if ( empty( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => 'No template specified' ) );
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
	 * Verify nonce for AJAX requests.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	private function verify_nonce() {
		if ( isset( $_REQUEST['nonce'] ) || isset( $_GET['nonce'] ) || isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) ?? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) ?? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) ?? '';
			if ( ! wp_verify_nonce( $nonce, 'aae_loop_builder_nonce' ) ) {
				wp_send_json_error( 'Security check failed' );
			}
		}
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
			$this->verify_nonce();

			$settings = isset( $_POST['settings'] ) ? $this->sanitize_settings( wp_unslash( $_POST['settings'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : ( $settings['paged'] ?? 1 );

			if ( empty( $settings['template_id'] ) ) {
				wp_send_json_error( array( 'message' => 'No template specified' ) );
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
	 * AJAX handler for getting taxonomies.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_taxonomies() {
		$this->verify_nonce();

		$post_type  = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$taxonomies = $this->get_taxonomies( $post_type );

		wp_send_json_success( $taxonomies );
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
		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		$host = isset( $_SERVER['HTTP_HOST'] ) ?? wp_unslash( $_SERVER['HTTP_HOST'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ?? wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		return $protocol . $host . $uri;
	}
}
