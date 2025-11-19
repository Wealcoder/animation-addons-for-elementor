<?php
namespace WCF_ADDONS\Widgets\Loop_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Loop Builder Integration for Animation Addons.
 *
 *  Initializes the loop builder functionality.
 */
class AAE_Loop_Builder_Integration {

	/**
	 * Instance.
	 *
	 * @var object $_instance Class instance.
	 */
	private static $_instance = null;

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
	 * Constructor.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	/**
	 * Initialize the integration.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function init() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$this->load_files();

		add_filter( 'wcf_builder_template_types', array( $this, 'add_template_types' ) );
		add_action( 'elementor/init', array( $this, 'init_elementor_components' ) );
		add_action( 'init', array( $this, 'register_custom_post_type_support' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Load required files.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	private function load_files() {
		// Core classes.
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/class-template-manager.php';
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/class-query-manager.php';
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/class-ajax-handler.php';

		// Document types.
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/documents/class-loop-item.php';

		// Controls.
		require_once WCF_ADDONS_PATH . 'widgets/loop-builder/controls/class-template-query.php';
	}

	/**
	 * Add a loop-builder template type.
	 *
	 * @param array $template_types Template types.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function add_template_types( $template_types ) {
		$template_types['loop-builder'] = array(
			'label'     => esc_html__( 'Loop Builder', 'animation-addons-for-elementor' ),
			'optionkey' => 'loopbuilder',
		);
		return $template_types;
	}

	/**
	 * Initialize Elementor components.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function init_elementor_components() {
		// Register document types.
		\Elementor\Plugin::$instance->documents->register_document_type(
			'loop-item',
			\WCF_ADDONS\Widgets\Loop_Builder\Documents\Loop_Item::class
		);

		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
		add_action( 'elementor/ajax/register_actions', array( $this, 'register_ajax_actions' ) );

		// Initialize managers.
		\WCF_ADDONS\Widgets\Loop_Builder\Template_Manager::instance();
		\WCF_ADDONS\Widgets\Loop_Builder\Query_Manager::instance();
		\WCF_ADDONS\Widgets\Loop_Builder\Ajax_Handler::instance();
	}

	/**
	 * Register custom post-type support.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function register_custom_post_type_support() {
		if ( post_type_exists( 'wcf-addons-template' ) ) {
			add_post_type_support( 'wcf-addons-template', 'elementor' );
		}
	}

	/**
	 * Register controls.
	 *
	 * @param \Elementor\Controls_Manager $controls_manager Controls manager.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function register_controls( $controls_manager ) {
		$controls_manager->register( new \WCF_ADDONS\Widgets\Loop_Builder\Controls\Template_Query() );
	}

	/**
	 * Register AJAX actions.
	 *
	 * @param object $ajax_manager AJAX manager.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function register_ajax_actions( $ajax_manager ) {
		$ajax_manager->register_ajax_action( 'clb_get_template_preview', array( $this, 'ajax_get_template_preview' ) );
		$ajax_manager->register_ajax_action( 'clb_refresh_loop_items', array( $this, 'ajax_refresh_loop_items' ) );
	}

	/**
	 * AJAX handler to refresh loop items in preview.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_refresh_loop_items() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aae_loop_builder_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_id = isset( $_POST['template_id'] ) ?? absint( wp_unslash( $_POST['template_id'] ) );
		$settings    = isset( $_POST['settings'] ) ?? $this->sanitize_widget_settings( wp_unslash( $_POST['settings'] ) ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $template_id ) {
			wp_send_json_error( 'Invalid template ID' );
		}

		$query_manager = \WCF_ADDONS\Widgets\Loop_Builder\Query_Manager::instance();
		$query         = $query_manager->get_query( $settings );

		$html = '';
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$classes = get_post_class( 'e-loop-item aae-loop-item', get_the_ID() );
				$query->the_post();
				$html .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-elementor-type="loop-item">';
				$html .= \WCF_ADDONS\Widgets\Loop_Builder\Template_Manager::render_template( $template_id, get_the_ID() );
				$html .= '</div>';
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Sanitize widget settings for AJAX.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return array Sanitized settings.
	 */
	private function sanitize_widget_settings( $settings ) {
		$sanitized = array();

		$string_fields = array( 'source', 'orderby', 'order' );
		foreach ( $string_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $settings[ $field ] );
			}
		}

		$int_fields = array( 'template_id', 'posts_per_page' );
		foreach ( $int_fields as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$sanitized[ $field ] = intval( $settings[ $field ] );
			}
		}

		return $sanitized;
	}

	/**
	 * AJAX handler for template preview.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_template_preview() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aae_loop_builder_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_id = isset( $_POST['template_id'] ) ?? absint( wp_unslash( $_POST['template_id'] ) );

		if ( ! $template_id ) {
			wp_send_json_error( 'Invalid template ID' );
		}

		$template_content = get_post_meta( $template_id, '_elementor_data', true );

		if ( ! $template_content ) {
			wp_send_json_error( 'Template not found' );
		}

		// Decode if it's JSON string.
		if ( is_string( $template_content ) ) {
			$template_content = json_decode( $template_content, true );
		}

		wp_send_json_success(
			array(
				'template_id'   => $template_id,
				'template_data' => $template_content,
			)
		);
	}

	/**
	 * Register and enqueue frontend scripts.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function register_frontend_scripts() {
		// Register frontend script.
		wp_register_script(
			'custom-loop-builder-frontend',
			WCF_ADDONS_URL . 'assets/js/loop-builder/frontend.js',
			array( 'jquery' ),
			WCF_ADDONS_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'custom-loop-builder-frontend',
			'wcf_addons_frontend',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aae_loop_builder_nonce' ),
			)
		);
	}

	/**
	 * Enqueue editor scripts.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_script(
			'aae-loop-builder-editor',
			WCF_ADDONS_URL . 'assets/js/loop-builder/editor.js',
			array( 'elementor-common', 'elementor-editor' ),
			WCF_ADDONS_VERSION,
			true
		);

		wp_enqueue_script(
			'aae-loop-builder-active-document',
			WCF_ADDONS_URL . 'assets/js/loop-builder/active-document.js',
			array( 'elementor-common', 'elementor-editor', 'jquery' ),
			WCF_ADDONS_VERSION,
			true
		);

		wp_localize_script(
			'aae-loop-builder-editor',
			'aaeLoopBuilderEditor',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aae_loop_builder_nonce' ),
			)
		);

		wp_enqueue_style(
			'aae-loop-builder-editor',
			WCF_ADDONS_URL . 'assets/css/editor-loop.css',
			array( 'elementor-editor' ),
			WCF_ADDONS_VERSION
		);
	}
}

// Initialize the integration.
AAE_Loop_Builder_Integration::instance();
