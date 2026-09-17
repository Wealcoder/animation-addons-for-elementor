<?php
namespace Wealcoder\AnimationAddons\Widgets\Loop_Builder;

use Wealcoder\AnimationAddons\Nonce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Loop Builder Integration for Animation Addons.
 *
 *  Initializes the loop builder functionality.
 */
class Aaeaddon_Loop_Builder_Integration {

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
		// if elementor pro is active retun
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return;
		}
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
		require_once AAEADDON_PATH . 'widgets/loop-builder/class-template-manager.php';
		require_once AAEADDON_PATH . 'widgets/loop-builder/class-query-manager.php';
		require_once AAEADDON_PATH . 'widgets/loop-builder/class-ajax-handler.php';

		// Document types.
		require_once AAEADDON_PATH . 'widgets/loop-builder/documents/class-loop-item.php';

		// Controls.
		require_once AAEADDON_PATH . 'widgets/loop-builder/controls/class-template-query.php';
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
			\Wealcoder\AnimationAddons\Widgets\Loop_Builder\Documents\Loop_Item::class
		);

		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );

		// The editor bundle posts this to admin-ajax.php as a plain `action`,
		// but it used to be registered through Elementor's ajax manager (which
		// only answers `elementor_ajax` requests) -- so it could never reach
		// the handler. Registered as a real wp_ajax action now, under the
		// prefixed name; the old unprefixed name stays for one release for a
		// stale editor bundle -- remove the alias in 4.3.
		// (`clb_refresh_loop_items` had no caller and was removed in 4.2.)
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'clb_get_template_preview', 'aaeaddon_clb_get_template_preview', array( $this, 'ajax_get_template_preview' ) );

		// Initialize managers.
		\Wealcoder\AnimationAddons\Widgets\Loop_Builder\Template_Manager::instance();
		\Wealcoder\AnimationAddons\Widgets\Loop_Builder\Query_Manager::instance();
		\Wealcoder\AnimationAddons\Widgets\Loop_Builder\Ajax_Handler::instance();
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
		$controls_manager->register( new \Wealcoder\AnimationAddons\Widgets\Loop_Builder\Controls\Template_Query() );
	}

	/**
	 * AJAX handler for template preview.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_get_template_preview() {
		if ( ! isset( $_POST['nonce'] ) || ! Nonce::verify( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), Nonce::LOOP_BUILDER ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;

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
			'aaeaddon-loop-builder-frontend',
			AAEADDON_URL . 'assets/js/loop-builder/frontend.js',
			array( 'jquery' ),
			AAEADDON_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'aaeaddon-loop-builder-frontend',
			'wcf_addons_frontend',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => Nonce::create( Nonce::LOOP_BUILDER ),
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
			AAEADDON_URL . 'assets/js/loop-builder/editor.js',
			array( 'elementor-common', 'elementor-editor' ),
			AAEADDON_VERSION,
			true
		);

		wp_enqueue_script(
			'aae-loop-builder-active-document',
			AAEADDON_URL . 'assets/js/loop-builder/active-document.js',
			array( 'elementor-common', 'elementor-editor', 'jquery' ),
			AAEADDON_VERSION,
			true
		);

		wp_localize_script(
			'aae-loop-builder-editor',
			'aaeLoopBuilderEditor',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => Nonce::create( Nonce::LOOP_BUILDER ),
			)
		);

		wp_enqueue_style(
			'aae-loop-builder-editor',
			AAEADDON_URL . 'assets/css/editor-loop.css',
			array( 'elementor-editor' ),
			AAEADDON_VERSION
		);
	}
}

// Initialize the integration.
Aaeaddon_Loop_Builder_Integration::instance();
