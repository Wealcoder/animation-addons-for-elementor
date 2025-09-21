<?php
namespace WCF_ADDONS\CodeSnippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

require_once __DIR__ . '/CodeSnippetAjax.php';

/**
 * CodeSnippet Class
 *
 * @package WCF_ADDONS\CodeSnippet
 */
class CodeSnippet {
	use CodeSnippetSettingsTrait;

	/**
	 * PostType name.
	 *
	 * @since 2.3.10
	 */
	const CPTTYPE = 'wcf-code-snippet';


	/**
	 * [$_instance]
	 *
	 * @since 2.3.10
	 * @var null
	 */
	public static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @since 2.3.10
	 * @return CodeSnippet|null [_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * CodeSnippet constructor.
	 *
	 * @since 2.3.10
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'remove_query_vars' ) );
		add_action( 'init', array( $this, 'register_code_snippet_post_type' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 225 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_post_add_wcf_code_snippet', array( $this, 'handle_add_wcf_code_snippet' ) );
		add_action( 'wp_ajax_add_custom_page', array( $this, 'add_custom_page' ) );
		add_action( 'wp_ajax_toggle_snippet_status', array( $this, 'handle_toggle_snippet_status' ) );

		// Initialize AJAX handler.
		new CodeSnippetAjax();
		new Notices();
	}

	/**
	 * Remove Query Var - FIXED VERSION.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function remove_query_vars() {
		if ( isset( $_GET['page'] ) && 'wcf-code-snippet' === $_GET['page'] && isset( $_GET['_wp_http_referer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// FIXED: Better redirect handling to avoid WooCommerce issues.
			$redirect_url = admin_url( 'admin.php?page=wcf-code-snippet' );

			// Only use referer if it's safe and contains our page.
			$referer = wp_get_referer();
			if ( $referer && strpos( $referer, 'wcf-code-snippet' ) !== false ) {
				$redirect_url = remove_query_arg(
					array( 'action', 'action2', 'ids', 'id', '_wpnonce', '_wp_http_referer' ),
					$referer
				);
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Register Code Snippet Post-Type.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function register_code_snippet_post_type() {
		$labels = array(
			'name'                  => esc_html_x( 'Code Snippet', 'Post Type General Name', 'animation-addons-for-elementor' ),
			'singular_name'         => esc_html_x( 'Code Snippet', 'Post Type Singular Name', 'animation-addons-for-elementor' ),
			'menu_name'             => esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			'name_admin_bar'        => esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			'archives'              => esc_html__( 'Code Snippet Archives', 'animation-addons-for-elementor' ),
			'attributes'            => esc_html__( 'Code Snippet Attributes', 'animation-addons-for-elementor' ),
			'parent_item_colon'     => esc_html__( 'Parent Item:', 'animation-addons-for-elementor' ),
			'all_items'             => esc_html__( 'Code Snippets', 'animation-addons-for-elementor' ),
			'add_new_item'          => esc_html__( 'Add New Snippet', 'animation-addons-for-elementor' ),
			'add_new'               => esc_html__( 'Add New', 'animation-addons-for-elementor' ),
			'new_item'              => esc_html__( 'New Snippet', 'animation-addons-for-elementor' ),
			'edit_item'             => esc_html__( 'Edit Snippet', 'animation-addons-for-elementor' ),
			'update_item'           => esc_html__( 'Update Snippet', 'animation-addons-for-elementor' ),
			'view_item'             => esc_html__( 'View Snippet', 'animation-addons-for-elementor' ),
			'view_items'            => esc_html__( 'View Snippet', 'animation-addons-for-elementor' ),
			'search_items'          => esc_html__( 'Search Snippet', 'animation-addons-for-elementor' ),
			'not_found'             => esc_html__( 'Not found', 'animation-addons-for-elementor' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'animation-addons-for-elementor' ),
			'featured_image'        => esc_html__( 'Featured Image', 'animation-addons-for-elementor' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'animation-addons-for-elementor' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'animation-addons-for-elementor' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'animation-addons-for-elementor' ),
			'insert_into_item'      => esc_html__( 'Insert into snippet', 'animation-addons-for-elementor' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this Snippet', 'animation-addons-for-elementor' ),
			'items_list'            => esc_html__( 'Snippets list', 'animation-addons-for-elementor' ),
			'items_list_navigation' => esc_html__( 'Snippets list navigation', 'animation-addons-for-elementor' ),
			'filter_items_list'     => esc_html__( 'Filter from snippet list', 'animation-addons-for-elementor' ),
		);

		$args = array(
			'label'               => esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			'description'         => esc_html__( 'AAE Code Snippet', 'animation-addons-for-elementor' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'wcf-code-snippet',
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

		// FIXED: Only flush rewrite rules if needed to avoid WooCommerce conflicts.
		if ( ! get_option( 'wcf_code_snippet_rewrite_rules_flushed' ) ) {
			flush_rewrite_rules();
			update_option( 'wcf_code_snippet_rewrite_rules_flushed', true );
		}
	}

	/**
	 * Add Code Snippet Post type Submenu - IMPROVED VERSION.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page(
			'wcf_addons_page',
			esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			'manage_options',
			self::CPTTYPE,
			array( $this, 'code_snippet_page_admin_page' )
		);
	}

	/**
	 * Code Snippet Admin Page.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function code_snippet_page_admin_page() {
		$add_new_tab     = isset( $_GET['new'] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code_snippet_id = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $add_new_tab ) {
			$snippet_details = $this->aae_get_code_snippet_settings();
			include __DIR__ . '/views/edit-code-snippet.php';
		} elseif ( $code_snippet_id ) {
			$snippet_details = $this->aae_get_code_snippet_settings( $code_snippet_id );
			include __DIR__ . '/views/edit-code-snippet.php';
		} else {
			include __DIR__ . '/views/code-snippet-list.php';
		}
	}

	/**
	 * Enqueue Scripts.
	 *
	 * @param string $hook Current page hook.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'animation-addon_page_wcf-code-snippet' === $hook ) {
			wp_enqueue_style( 'aae-code-snippet', WCF_ADDONS_URL . 'assets/css/code-snippet.min.css', null, WCF_ADDONS_VERSION, 'all' );
			wp_enqueue_style( 'aae-code-snippet-ajax', WCF_ADDONS_URL . 'assets/css/code-snippet-ajax.css', null, WCF_ADDONS_VERSION, 'all' );
			wp_enqueue_style( 'select2', WCF_ADDONS_URL . 'assets/css/select2.min.css', null, WCF_ADDONS_VERSION, 'all' );
			wp_enqueue_style( 'codemirror-core', WCF_ADDONS_URL . 'assets/css/cs-css/codemirror.min.css', null, WCF_ADDONS_VERSION, 'all' );
			wp_enqueue_style( 'foldgutter', WCF_ADDONS_URL . 'assets/css/cs-css/foldgutter.min.css', null, WCF_ADDONS_VERSION, 'all' );
			wp_enqueue_style( 'material', WCF_ADDONS_URL . 'assets/css/cs-css/material.min.css', null, WCF_ADDONS_VERSION, 'all' );

			// code mirror.
			wp_enqueue_script( 'codemirror-core', WCF_ADDONS_URL . 'assets/js/cs-js/custom-code.min.js', array(), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-htmlmixed', WCF_ADDONS_URL . 'assets/js/cs-js/htmlmixed.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-js-css', WCF_ADDONS_URL . 'assets/js/cs-js/css.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-javascript', WCF_ADDONS_URL . 'assets/js/cs-js/javascript.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-php', WCF_ADDONS_URL . 'assets/js/cs-js/php.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-xml', WCF_ADDONS_URL . 'assets/js/cs-js/xml.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-mode-clike', WCF_ADDONS_URL . 'assets/js/cs-js/clike.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-closebrackets', WCF_ADDONS_URL . 'assets/js/cs-js/closebrackets.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-closetag', WCF_ADDONS_URL . 'assets/js/cs-js/closetag.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-foldcode', WCF_ADDONS_URL . 'assets/js/cs-js/foldcode.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-foldgutter', WCF_ADDONS_URL . 'assets/js/cs-js/foldgutter.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-brace-fold', WCF_ADDONS_URL . 'assets/js/cs-js/brace-fold.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );
			wp_enqueue_script( 'codemirror-addon-xml-fold', WCF_ADDONS_URL . 'assets/js/cs-js/xml-fold.min.js', array( 'codemirror-core' ), WCF_ADDONS_VERSION, true );

			// Custom Code Editor.
			wp_enqueue_script(
				'codemirror-editor',
				WCF_ADDONS_URL . 'assets/js/code-snippet.min.js',
				array( 'jquery', 'select2', 'codemirror-core' ),
				'1.0.0',
				true
			);

			// AJAX functionality for list page.
			wp_enqueue_script(
				'code-snippet-ajax',
				WCF_ADDONS_URL . 'assets/js/code-snippet-ajax.js',
				array( 'jquery' ),
				WCF_ADDONS_VERSION,
				true
			);
			$localize_data = array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'wcf_custom_code_security' ),
				'adminURL'      => admin_url(),
				'snippet_page'  => admin_url( 'admin.php?page=wcf-code-snippet' ),
				'serverDetails' => array(
					'currentVersion' => PHP_VERSION,
					'majorVersion'   => PHP_MAJOR_VERSION,
					'minorVersion'   => PHP_MINOR_VERSION,
				),
				'ajaxActions'   => array(
					'search' => 'wcf_search_snippets',
					'delete' => 'wcf_delete_snippet',
					'bulk'   => 'wcf_bulk_action_snippets',
					'toggle' => 'wcf_toggle_snippet_status',
				),
				'messages'      => array(
					'confirmDelete'     => __( 'Are you sure you want to delete this snippet?', 'animation-addons-for-elementor' ),
					'confirmBulkDelete' => __( 'Are you sure you want to delete the selected snippets?', 'animation-addons-for-elementor' ),
					'loading'           => __( 'Loading...', 'animation-addons-for-elementor' ),
					'error'             => __( 'An error occurred. Please try again.', 'animation-addons-for-elementor' ),
				),
			);
			wp_localize_script( 'codemirror-editor', 'WCFCustomCodeVars', $localize_data );
			wp_enqueue_script( 'select2', WCF_ADDONS_URL . '/assets/js/select2.min.js', array( 'jquery' ), WCF_ADDONS_VERSION, true );
		}
	}


	/**
	 * Add code snippet data.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function handle_add_wcf_code_snippet() {
		check_admin_referer( 'wcf_code_snippet' );
		$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : '';
		$referer    = wp_get_referer();

		// Post title & content.
		$snippet_title = isset( $_POST['snippet_title'] ) ? sanitize_text_field( wp_unslash( $_POST['snippet_title'] ) ) : '';

		$args = array(
			'ID'          => $snippet_id,
			'post_title'  => $snippet_title,
			'post_type'   => 'wcf-code-snippet',
			'post_status' => 'publish',
		);

		$snippet_id = wp_insert_post( $args );
		if ( is_wp_error( $snippet_id ) ) {
			wp_safe_redirect( $referer );
			exit();
		}

		$settings = $this->aae_get_code_snippet_settings();
		foreach ( $settings as $key => $default_value ) {
			if ( isset( $_POST[ $key ] ) ) {
				if ( 'code_content' === $key ) {
                    $meta_value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
				} else {
					$meta_value = is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : map_deep( wp_unslash( $_POST[ $key ] ), 'sanitize_text_field' );
				}
				if ( 'is_active' === $key && empty( $meta_value ) ) {
					update_post_meta( $snippet_id, $key, 'no' );
					continue;
				}

				if ( isset( $_POST['code_type'] ) && 'php' === $_POST['code_type'] && 'load_location' === $key ) {
					update_post_meta( $snippet_id, $key, '' );
					continue;
				}
				update_post_meta( $snippet_id, $key, $meta_value );

			} else {
				update_post_meta( $snippet_id, $key, $meta_value );
			}
		}

		/**
		 * Action hook to add code snippet data.
		 *
		 * @param int $snippet_id Post ID.
		 *
		 * @since 2.3.10
		 */
		do_action( 'after_update_code_snippet_post_data', $snippet_id );

		$redirect_to = admin_url( 'admin.php?page=wcf-code-snippet&edit=' . $snippet_id );
		if ( isset( $_POST['snippet_id'] ) && ! empty( $_POST['snippet_id'] ) ) {
			Helpers::add_flash_message(
				__( 'Code Snippet Updated Successfully!', 'animation-addons-for-elementor' ),
				'success'
			);
		} else {
			Helpers::add_flash_message(
				__( 'Code Snippet Added Successfully!', 'animation-addons-for-elementor' ),
				'success'
			);
		}
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Ajax handler to return the posts based on the search query.
	 * When searching for the post/pages, only titles are searched for.
	 *
	 * @since  1.0.0
	 */
	public function add_custom_page() {

		if ( isset( $_POST ) ) {

			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wcf_custom_code_security' ) ) {
				$errormessage = array(
					'message' => esc_html__( 'Nonce Varification Failed!', 'animation-addons-for-elementor' ),
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

			unset( $post_types[ self::CPTTYPE ] ); // Exclude wcf post type templates.

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
						$title .= ( 0 !== $query->post->post_parent ) ? ' (' . get_the_title( $query->post->post_parent ) . ')' : '';
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

			// return the result in JSON.
			wp_send_json( $result );
		} else {
			$errormessage = array(
				'message' => esc_html__( 'Some thing is wrong!', 'animation-addons-for-elementor' ),
			);
			wp_send_json_error( $errormessage );
		}
	}

	/**
	 * Return search results only by post-title.
	 * This is only run from hfe_get_posts_by_query()
	 *
	 * @param  (string)    $search   Search SQL for the WHERE clause.
	 * @param  (\WP_Query) $wp_query The current WP_Query object.
	 *
	 * @since 2.3.10
	 * @return (string) The Modified Search SQL for WHERE clause.
	 */
	public function search_only_titles( $search, $wp_query ) {
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
	 * Retrieve the count of code snippets based on specified criteria.
	 *
	 * @param array $args Optional. Associative array of arguments to filter the snippets. Default empty array.
	 *
	 * @since 2.3.10
	 * @return int
	 */
	public static function get_snippet_count( $args = array() ) {
		$default_args = array(
			'post_type'      => self::CPTTYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);

		$args = wp_parse_args( $args, $default_args );

		// Handle code_type filtering.
		if ( ! empty( $args['code_type'] ) && 'all' !== $args['code_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'code_type',
					'value'   => $args['code_type'],
					'compare' => '=',
				),
			);
		}

		// Remove code_type from args as it's handled by meta_query.
		unset( $args['code_type'] );

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Handle AJAX request to toggle snippet status.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function handle_toggle_snippet_status() {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wcf_custom_code_security' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'animation-addons-for-elementor' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'animation-addons-for-elementor' ) ) );
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? intval( $_POST['snippet_id'] ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		// Validate snippet exists and is of correct post-type.
		$snippet = get_post( $snippet_id );
		if ( ! $snippet || self::CPTTYPE !== $snippet->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid snippet.', 'animation-addons-for-elementor' ) ) );
		}

		// Update the status.
		$updated = update_post_meta( $snippet_id, 'is_active', $status );

		if ( $updated ) {
			$status_text = ( 'yes' === $status ) ? __( 'Activated', 'animation-addons-for-elementor' ) : __( 'Deactivated', 'animation-addons-for-elementor' );
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s: snippet status text. */
						__( 'Snippet %s successfully.', 'animation-addons-for-elementor' ),
						$status_text
					),
					'status'  => $status,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update snippet status.', 'animation-addons-for-elementor' ) ) );
		}
	}
}

CodeSnippet::instance();
