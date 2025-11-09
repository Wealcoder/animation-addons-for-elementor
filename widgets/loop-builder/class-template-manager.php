<?php
namespace WCF_ADDONS\Widgets\Loop_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template Manager Class.
 *
 * Manages loop item templates and their creation/editing.
 */
class Template_Manager {

	/**
	 * Instance.
	 *
	 * @var object $_instance Class instance.
	 */
	private static $_instance = null;

	/**
	 * Template post-type.
	 */
	const TEMPLATE_POST_TYPE = 'wcf-addons-template';


	/**
	 * Loop item type.
	 */
	const LOOP_ITEM_TYPE = 'loop-builder';

	/**
	 * Instance.
	 *
	 * @since 2.4.16
	 * @return object Class instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'elementor/template-library/create_new_dialog_fields', array( $this, 'add_template_fields' ) );
		add_filter( 'elementor/finder/categories', array( $this, 'add_finder_items' ) );
		add_action( 'wp_ajax_create_loop_template', array( $this, 'ajax_create_template' ) );
		add_action( 'wp_ajax_nopriv_create_loop_template', array( $this, 'ajax_create_template' ) );
		add_action( 'wp_ajax_clb_duplicate_template', array( $this, 'ajax_duplicate_template' ) );
		add_action( 'wp_ajax_clb_delete_template', array( $this, 'ajax_delete_template' ) );
	}

	/**
	 * Add template creation fields.
	 *
	 * @param \Elementor\Core\Base\Document $form Form instance.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function add_template_fields( $form ) {
		if ( empty( $form ) ) {
			return;
		}

		$form->add_control(
			'_elementor_source',
			array(
				'type'       => \Elementor\Controls_Manager::SELECT,
				'label'      => __( 'Choose source type', 'animation-addons-for-elementor' ),
				'options'    => $this->get_source_options(),
				'section'    => 'main',
				'required'   => true,
				'conditions' => array(
					'template-type' => self::LOOP_ITEM_TYPE,
				),
			)
		);
	}

	/**
	 * Get source options for templates.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	private function get_source_options() {
		$options = array(
			'post' => __( 'Posts', 'animation-addons-for-elementor' ),
			'page' => __( 'Pages', 'animation-addons-for-elementor' ),
		);

		// Add custom post types.
		$post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);
		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * Add finder items.
	 *
	 * @param array $categories Categories.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function add_finder_items( $categories ) {
		$categories['create']['items']['loop-template'] = array(
			'title'    => __( 'Add New Loop Template', 'animation-addons-for-elementor' ),
			'icon'     => 'plus-circle-o',
			'url'      => admin_url( 'edit.php?post_type=elementor_library&tabs_group=theme&elementor_library_type=' . self::LOOP_ITEM_TYPE ),
			'keywords' => array( 'template', 'loop', 'dynamic', 'listing', 'archive', 'repeater', 'grid' ),
		);

		return $categories;
	}

	/**
	 * Create new loop template via AJAX.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_create_template() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aae_loop_builder_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
		$source_type   = isset( $_POST['source_type'] ) ? sanitize_text_field( wp_unslash( $_POST['source_type'] ) ) : '';

		if ( empty( $template_name ) ) {
			wp_send_json_error( array( 'message' => 'Template name is required' ) );
		}

		$template_id = wp_insert_post(
			array(
				'post_title'  => $template_name,
				'post_type'   => self::TEMPLATE_POST_TYPE,
				'post_status' => 'publish',
				'meta_input'  => array(
					'_elementor_data'                   => wp_json_encode( $this->get_default_template_structure() ),
					'wcf-addons-template-meta_location' => 'global',
					'wcf-addons-template-meta_type'     => 'loop-builder',
					'_elementor_source'                 => $source_type,
					'_elementor_edit_mode'              => 'builder',
					'_wp_page_template'                 => 'elementor_canvas',
				),
			)
		);

		if ( is_wp_error( $template_id ) ) {
			wp_send_json_error( array( 'message' => 'Failed to create template: ' . $template_id->get_error_message() ) );
		}

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create template post' ) );
		}

		wp_send_json_success(
			array(
				'template_id'   => $template_id,
				'template_name' => $template_name,
				'message'       => 'Template created successfully',
				'edit_url'      => add_query_arg(
					array(
						'post'   => $template_id,
						'action' => 'elementor',
					),
					admin_url( '/post.php' )
				),
			)
		);
	}

	/**
	 * Duplicate template via AJAX.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_duplicate_template() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aae_loop_builder_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$template_id   = isset( $_POST['template_id'] ) ? intval( wp_unslash( $_POST['template_id'] ) ) : '';
		$original_post = get_post( $template_id );

		if ( ! $original_post ) {
			wp_send_json_error( 'Template not found' );
		}

		$new_template_id = wp_insert_post(
			array(
				'post_title'   => $original_post->post_title . ' (Copy)',
				'post_type'    => $original_post->post_type,
				'post_status'  => 'publish',
				'post_content' => $original_post->post_content,
			)
		);

		if ( is_wp_error( $new_template_id ) ) {
			wp_send_json_error( 'Failed to duplicate template' );
		}

		$meta_data = get_post_meta( $template_id );
		foreach ( $meta_data as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $new_template_id, $key, maybe_unserialize( $value ) );
			}
		}

		wp_send_json_success(
			array(
				'template_id' => $new_template_id,
				'title'       => get_the_title( $new_template_id ),
			)
		);
	}

	/**
	 * Delete template via AJAX.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_delete_template() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aae_loop_builder_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? intval( wp_unslash( $_POST['template_id'] ) ) : '';

		if ( wp_delete_post( $template_id, true ) ) {
			wp_send_json_success( 'Template deleted successfully' );
		} else {
			wp_send_json_error( 'Failed to delete template' );
		}
	}

	/**
	 * Render template content.
	 *
	 * @param int $template_id Template ID.
	 * @param int $post_id Post ID.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public static function render_template( $template_id, $post_id = null ) {
		if ( empty( $template_id ) ) {
			return '';
		}

		global $post;

		$original_post = $post;

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return '';
			}
			setup_postdata( $post );
		}

		// Get Elementor content safely.
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );

		// Restore the global post.
		$post = $original_post;
		wp_reset_postdata();

		return $content;
	}

	/**
	 * Get default template structure for new templates.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	private function get_default_template_structure() {
		return array(

			array(
				'id'       => uniqid(),
				'elType'   => 'container',
				'elements' => array(
					array(
						'id'         => uniqid(),
						'elType'     => 'widget',
						'widgetType' => 'wcf--theme-post-image',
					),
					array(
						'id'         => uniqid(),
						'elType'     => 'widget',
						'widgetType' => 'wcf--blog--post--title',
						'settings'   => array(
							'header_size' => 'h4',
						),
					),
					array(
						'id'         => uniqid(),
						'elType'     => 'widget',
						'widgetType' => 'aae--advanced-button',
					),
				),
			),
		);
	}
}

Template_Manager::instance();
