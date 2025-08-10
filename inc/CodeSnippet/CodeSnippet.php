<?php
namespace WCF_ADDONS\CodeSnippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

/**
 * CodeSnippet Class
 *
 * @package WCF_ADDONS\CodeSnippet
 */
class CodeSnippet {
	/**
	 * PostType name.
	 *
	 * @since 2.3.10
	 */
	const CPTTYPE = 'wcf-code-snippet';


	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	public static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return [_Admin_Init]
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
		add_action( 'init', array( $this, 'register_code_snippet_post_type' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 225 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_post_add_wcf_code_snippet', array( $this, 'handle_add_wcf_code_snippet' ) );
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

		flush_rewrite_rules();
	}

	/**
	 * Add Code Snippet Post type Submenu
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function admin_menu() {
		$link_custom_post = self::CPTTYPE;
		add_submenu_page(
			'wcf_addons_page',
			esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			esc_html__( 'Code Snippet', 'animation-addons-for-elementor' ),
			'manage_options',
			$link_custom_post,
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
			$snippet_details = aae_get_code_snippet_settings();
			include __DIR__ . '/views/edit-code-snippet.php';
		} elseif ( $code_snippet_id ) {
			$snippet_details = aae_get_code_snippet_settings( $code_snippet_id );
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
			wp_enqueue_style( 'aae-code-snippet', WCF_ADDONS_URL . 'assets/css/code-snippet.min.css', null, time(), 'all' );
			wp_enqueue_script(
				'codemirror-editor',
				WCF_ADDONS_URL . 'assets/js/code-snippet.min.js',
				array(),
				'1.0.0',
				true
			);

			// code mirror.
			wp_enqueue_style(
				'codemirror-core',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css',
				array(),
				'5.65.16'
			);
			wp_enqueue_style(
				'codemirror-theme-material',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material.min.css',
				array( 'codemirror-core' ),
				'5.65.16'
			);
			wp_enqueue_style(
				'codemirror-theme-default',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/default.min.css',
				array( 'codemirror-core' ),
				'5.65.16'
			);
			wp_enqueue_style(
				'codemirror-foldgutter',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.css',
				array( 'codemirror-core' ),
				'5.65.16'
			);
			wp_enqueue_script(
				'codemirror-core',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js',
				array(),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-htmlmixed',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-css',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-javascript',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-php',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-xml',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-mode-clike',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-closebrackets',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-closetag',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closetag.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-foldcode',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldcode.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-foldgutter',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-brace-fold',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/brace-fold.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
			wp_enqueue_script(
				'codemirror-addon-xml-fold',
				'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/xml-fold.min.js',
				array( 'codemirror-core' ),
				'5.65.16',
				true
			);
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

		$settings = aae_get_code_snippet_settings();
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

		} else {

		}
		wp_safe_redirect( $redirect_to );
		exit;
	}
}

CodeSnippet::instance();
