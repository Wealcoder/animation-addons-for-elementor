<?php

namespace WCF_ADDONS\Admin\Notices;

defined( 'ABSPATH' ) || exit();
/**
 * Notices class.
 *
 * @since 2.4.16
 */
class Notices {
	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

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
	 * The plugin prefix.
	 *
	 * @var array|string|string[]
	 */
	protected $plugin_prefix;

	/**
	 * The notices.
	 *
	 * @since 2.4.16
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Notices constructor.
	 *
	 * @since 2.4.16
	 */
	public function __construct() {
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'add_admin_notices' ) );
		

		$this->plugin_prefix = 'aae_notice_';
		add_action( 'wp_ajax_' . $this->plugin_prefix . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_style( 'aae-notice', WCF_ADDONS_URL . 'assets/css/css/notice.css', array(), WCF_ADDONS_VERSION );
		wp_register_style( 'aae-notice-halloween', WCF_ADDONS_URL . 'assets/css/css/halloween-2025.css', array(), WCF_ADDONS_VERSION );
		wp_register_script( 'aae-notice', WCF_ADDONS_URL . 'assets/js/js/notice.js', array( 'jquery' ), WCF_ADDONS_VERSION, true );
	}

	/**
	 * Add Admin notices.
	 *
	 * @since 2.4.16
	 */
	public function add_admin_notices() {
		$installed_time = absint( get_option( 'aae_installed' ) );
		$current_time   = absint( wp_date( 'U' ) );
		$plugin_file = WP_PLUGIN_DIR . '/animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php';
		if ( !file_exists( $plugin_file ) ) {
			wp_enqueue_style( 'aae-notice-halloween' );
			// $this->add(
			// 	array(
			// 		'message'     => __DIR__ . '/views/halloween-2025.php',
			// 		'notice_id'   => 'aae_halloween',
			// 		'style'       => 'border-left-color: #FC6848; border-radius: 6px; overflow: hidden;',
			// 		'dismissible' => false,
			// 	)
			// );
		}
		
	}

	/**
	 * Dismisses the notice.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_dismiss_notice() {
		if ( ! check_ajax_referer( $this->plugin_prefix . '_dismiss_notice', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
			exit;
		}
		$notice_id   = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';
		$snooze      = isset( $_POST['snooze'] ) ? filter_var( wp_unslash( $_POST['snooze'] ), FILTER_VALIDATE_BOOLEAN ) : false;
		$snooze_time = isset( $_POST['snooze_time'] ) ? absint( wp_unslash( $_POST['snooze_time'] ) ) : 7 * DAY_IN_SECONDS;
		$notice      = array_key_exists( $notice_id, $this->notices ) ? $this->notices[ $notice_id ] : null;
		if ( ! is_null( $notice ) ) {
			if ( $snooze ) {
				$this->snooze( $notice_id, $snooze_time );
			} else {
				$this->dismiss( $notice_id );
			}
			wp_cache_flush();
			wp_send_json_success();
			exit;
		}
		wp_send_json_error();
		exit;
	}

	/**
	 * Display the admin notices.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function admin_notices() {
		if ( empty( $this->notices ) ) {
			return;
		}

		// Enqueue the notices script.
		wp_enqueue_script( 'aae-notice' );

		foreach ( $this->notices as $notice ) {
			if ( $this->should_display( $notice ) ) {
				$classes = array_unique( array_filter( wp_parse_list( $notice['class'] ) ) );
				$style   = ! empty( $notice['style'] ) ? $notice['style'] : '';
				$message = $notice['message'];
				if ( str_ends_with( $message, '.php' ) ) {
					wp_enqueue_style( 'aae-notice' );
					$path = wp_normalize_path( $message );
					if ( file_exists( $path ) ) {
						ob_start();
						include $path;
						$message = ob_get_clean();
					}
				}

				// if dismissible then add is-dismissible class.
				if ( $notice['dismissible'] ) {
					$classes[] = 'is-dismissible';
				}

				// if empty message then skip.
				if ( empty( $message ) ) {
					continue;
				}

				// if message does not contain html tags then wrap it with a paragraph.
				if ( ! preg_match( '/<[^>]+>/', $message ) ) {
					$message = wpautop( $message );
				}

				printf(
					'<div class="notice aae-notice notice-%1$s %2$s" data-notice_id="%3$s" data-nonce="%4$s" data-action="%5$s" style="%6$s">%7$s%8$s</div>',
					esc_attr( $notice['type'] ),
					esc_attr( implode( ' ', $classes ) ),
					esc_attr( $notice['notice_id'] ),
					esc_attr( wp_create_nonce( $this->plugin_prefix . '_dismiss_notice' ) ),
					esc_attr( $this->plugin_prefix . '_dismiss_notice' ),
					esc_attr( $style ),
					wp_kses_post( wptexturize( $message ) ),
					$notice['dismissible'] ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice', 'animation-addons-for-elementor' ) . '</span></button>' : ''
				);
			}
		}
	}

	/**
	 * Add a notice.
	 *
	 * @param string|array $args The notice arguments or message.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function add( $args ) {
		if ( is_string( $args ) ) {
			$args = array( 'message' => $args );
		}
		$args = wp_parse_args(
			$args,
			array(
				'message'     => '',
				'type'        => 'info',
				'dismissible' => true,
				'capability'  => 'manage_options',
				'notice_id'   => '',
				'class'       => '',
				'style'       => '',
			)
		);

		// if message is empty then skip.
		if ( empty( $args['message'] ) ) {
			return;
		}

		// if we do not have a notice id then generate one.
		if ( empty( $args['notice_id'] ) ) {
			$args['notice_id'] = $this->plugin_prefix . '_' . md5( $args['message'] . $args['type'] );
		}

		// if dismissible and already dismissed, don't add the notice.
		if ( true === filter_var( $args['dismissible'], FILTER_VALIDATE_BOOLEAN ) && $this->is_dismissed( $args['notice_id'] ) ) {
			return;
		}

		$this->notices[ $args['notice_id'] ] = $args;
	}

	/**
	 * Get the notices.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_notices() {
		return $this->notices;
	}

	/**
	 * Is the notice dismissed?
	 *
	 * @param string $id The notice id.
	 *
	 * @since 2.4.16
	 * @return bool
	 */
	public function is_dismissed( $id ) {
		if ( 'yes' === get_option( $id ) || 'yes' === get_option( '_transient_' . $id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Should the notice be displayed?
	 *
	 * @param array $notice The notice options.
	 *
	 * @since 2.4.16
	 * @return bool
	 */
	public function should_display( $notice ) {
		if ( ( $notice['notice_id'] && $this->is_dismissed( $notice['notice_id'] ) ) || ( $notice['capability'] && ! current_user_can( $notice['capability'] ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Dismiss a notice.
	 *
	 * @param string $id The notice id.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function dismiss( $id ) {
		update_option( $id, 'yes' );
		delete_transient( $id );
	}

	/**
	 * Snooze a notice.
	 *
	 * @param string $id The notice id.
	 * @param int    $time The time to snooze the notice for.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function snooze( $id, $time = null ) {
		set_transient( $id, 'yes', absint( $time ) );
	}
}

// Initialize the class.
Notices::instance();
