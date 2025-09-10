<?php

namespace WCF_ADDONS\CodeSnippet;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers class for CodeSnippet.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
class Helpers {

	/**
	 * Get the status toggle HTML for a snippet.
	 *
	 * @param object $item The current snippet object.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_status_toggle( $item ) {
		$id        = $item->ID;
		$is_active = get_post_meta( $id, 'is_active', true );

		$toggle_html  = '<label class="toggle-switch">';
		$toggle_html .= sprintf(
			'<input type="checkbox" class="snippet-status-toggle" data-id="%d" %s>',
			esc_attr( $id ),
			( 'yes' === $is_active ) ? 'checked' : ''
		);
		$toggle_html .= '<span class="slider"></span>';
		$toggle_html .= '</label>';

		return $toggle_html;
	}

	/**
	 * Add flash message using the Notices class.
	 *
	 * @param string $message The message text.
	 * @param string $type    The message type.
	 *
	 * @since 1.0.0
	 */
	public static function add_flash_message( $message, $type = 'success' ) {
		Notices::add_flash_message( $message, $type );
	}

	/**
	 * Get list table class.
	 *
	 * @param string $type Type of list table to get.
	 *
	 * @since 2.3.10
	 * @return object
	 */
	public static function aae_get_list_table( $type ) {
		switch ( $type ) {
			case 'wcf-code-snippet':
			default:
				$list_table = new \WCF_ADDONS\CodeSnippet\listTables\CodeSnippetListTable();
				break;
		}

		return $list_table;
	}
}
