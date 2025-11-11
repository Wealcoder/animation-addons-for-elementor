<?php
namespace WCF_ADDONS\Admin\Notices;

defined( 'ABSPATH' ) || exit();

/**
 * Flash Message Handler Class.
 *
 * @since   2.4.16
 * @version 2.4.16
 * @package WCF_ADDONS\Admin\Notices
 */
class ShowNotices {
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
	 * The messages.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Construct and initialize the service trait.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	
		$this->plugin_prefix = 'aae_notice_';
		add_action( 'admin_init', array( $this, 'load_messages' ), 1 );
		add_filter( 'wp_redirect', array( $this, 'save_messages' ), 1 );
		add_action( 'admin_notices', array( $this, 'display_messages' ) );
	}

	/**
	 * Load the messages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_messages() {
		$flash = filter_input( INPUT_GET, '_flash', FILTER_VALIDATE_BOOLEAN );
		if ( true === $flash ) {
			$messages = get_option( $this->plugin_prefix . '_flash_messages', array() );
			if ( ! empty( $messages ) && is_array( $messages ) ) {
				foreach ( $messages as $message ) {
					$this->message( $message['type'], $message['message'] );
				}
			}
			update_option( $this->plugin_prefix . '_flash_messages', array() );
		}
	}

	/**
	 * Save the messages.
	 *
	 * @param string $location The location to redirect to.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function save_messages( $location ) {
		if ( ! empty( $this->messages ) ) {
			update_option( $this->plugin_prefix . '_flash_messages', $this->messages );
			$location = add_query_arg( '_flash', 'yes', $location );
		}

		return $location;
	}

	/**
	 * Display the messages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_messages() {
		if ( empty( $this->messages ) ) {
			return;
		}
		foreach ( $this->messages as $message_id => $message ) {
			printf( '<div class="notice notice-%1$s is-dismissible">%2$s</div>', esc_attr( $message['type'] ), wp_kses_post( wpautop( $message['message'] ) ) );
			unset( $this->messages[ $message_id ] );
		}
	}

	/**
	 * Add a message to the list of messages.
	 *
	 * @param string $type Message type. Default 'success'.
	 * @param string $message The message to add.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function message( $type, $message ) {
		if ( empty( $message ) && ! in_array( $type, array( 'success', 'info', 'warning', 'error' ), true ) ) {
			return;
		}

		// Generate a unique ID for the message.
		$id                    = substr( md5( $message . $type ), 0, 8 );
		$this->messages[ $id ] = array(
			'message' => $message,
			'type'    => $type,
		);
	}

	/**
	 * Add error message to the list of messages.
	 *
	 * @param string $message The message to add.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function error( $message ) {
		$this->message( 'error', $message );
	}

	/**
	 * Add warning message to the list of messages.
	 *
	 * @param string $message The message to add.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function warning( $message ) {
		$this->message( 'warning', $message );
	}

	/**
	 * Add info message to the list of messages.
	 *
	 * @param string $message The message to add.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function info( $message ) {
		$this->message( 'info', $message );
	}

	/**
	 * Add success message to the list of messages.
	 *
	 * @param string $message The message to add.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function success( $message ) {
		$this->message( 'success', $message );
	}

	/**
	 * Get the messages.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_messages() {
		return $this->messages;
	}

	/**
	 * Clear the messages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_messages() {
		$this->messages = array();
		update_option( $this->plugin_prefix . '_flash_messages', array() );
	}
}

// Initialize the class.
ShowNotices::instance();
