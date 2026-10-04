<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\CodeSnippet;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

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
	 * Transient key for the current user's pending messages.
	 *
	 * Keyed by user id, not shared. A flash message describes what the person
	 * reading it just did, so a site-wide key means whichever administrator
	 * loads the screen first consumes it -- the one who acted may never see
	 * their own result, and someone else sees a message about an action that
	 * was not theirs. Returns '' when there is no user to key on.
	 *
	 * @return string
	 */
	private static function key() {
		$user_id = get_current_user_id();

		return $user_id ? 'wcf_code_snippet_flash_' . $user_id : '';
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

		$key = self::key();
		if ( '' === $key ) {
			return;
		}

		// Display success/error messages from flash data.
		$flash_messages = get_transient( $key );
		if ( $flash_messages && is_array( $flash_messages ) ) {
			foreach ( $flash_messages as $message ) {
				$class = 'success' === $message['type'] ? 'notice-success' : 'notice-error';
				echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message['text'] ) . '</p></div>';
			}
			delete_transient( $key );
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
		$key = self::key();
		if ( '' === $key ) {
			return;
		}

		$flash_messages = get_transient( $key );
		if ( ! is_array( $flash_messages ) ) {
			$flash_messages = array();
		}
		$flash_messages[] = array(
			'text' => $message,
			'type' => $type,
		);
		set_transient( $key, $flash_messages, 30 );
	}
}
