<?php
namespace Wealcoder\AnimationAddons\Widgets\Loop_Builder;

use Wealcoder\AnimationAddons\Nonce;

use Wealcoder\AnimationAddons\Ajax_Alias;

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
		// Editor-only; the old unprefixed name is kept for one release for a
		// stale editor bundle -- remove the alias in 4.3.
		// (`clb_duplicate_template` / `clb_delete_template` had no caller in
		// either plugin and were removed in 4.2.)
		Ajax_Alias::register( 'create_loop_template', 'aaeaddon_clb_create_template', array( $this, 'ajax_create_template' ) );
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

		return;

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
		if ( ! isset( $_POST['nonce'] ) || ! Nonce::verify( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), Nonce::LOOP_BUILDER ) ) {
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
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- deliberate post switch; $original_post is restored on every exit path below.
			$post = get_post( $post_id );

			// Restore before bailing. Returning here used to leave the global
			// $post as null for the rest of the request, which is the failure
			// documented under "switch_to_post() nulls the global post" -- every
			// later reader warns on a null it did not cause.
			if ( ! $post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the value saved above.
				$post = $original_post;
				return '';
			}
			setup_postdata( $post );
		}

		// Get Elementor content safely.
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );

		// Restore the global post.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the value saved above.
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
