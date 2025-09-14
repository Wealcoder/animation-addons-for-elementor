<?php

namespace WCF_ADDONS\CodeSnippet;

defined( 'ABSPATH' ) || exit;

/**
 * Notices class for CodeSnippet.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
class Notices {

	/**
	 * Notices constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function display_notices() {
		// Check if we're on the CodeSnippet admin page.
		if ( ! isset( $_GET['page'] ) || 'wcf-code-snippet' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Display success/error messages from flash data.
		$flash_messages = get_transient( 'wcf_code_snippet_flash_messages' );
		if ( $flash_messages ) {
			foreach ( $flash_messages as $message ) {
				$class = 'success' === $message['type'] ? 'notice-success' : 'notice-error';
				echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message['text'] ) . '</p></div>';
			}
			delete_transient( 'wcf_code_snippet_flash_messages' );
		}
	}

	/**
	 * Add a flash message.
	 *
	 * @param string $message The message text.
	 * @param string $type    The message type (success, error, warning, info).
	 *
	 * @since 1.0.0
	 */
	public static function add_flash_message( $message, $type = 'success' ) {
		$flash_messages = get_transient( 'wcf_code_snippet_flash_messages' );
		if ( ! $flash_messages ) {
			$flash_messages = array();
		}
		$flash_messages[] = array(
			'text' => $message,
			'type' => $type,
		);
		set_transient( 'wcf_code_snippet_flash_messages', $flash_messages, 30 );
	}
}
