<?php

namespace WCF_ADDONS\CodeSnippet\listTables;

defined( 'ABSPATH' ) || exit();

// Load WP_List_Table if not loaded.
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
abstract class AbstractListTable extends \WP_List_Table {
	/**
	 * Current page URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $base_url;
	/**
	 * Get a request var, or return the default if not set.
	 *
	 * @param string $param Request var name.
	 * @param mixed  $fallback Default value.
	 *
	 * @since 2.0.0
	 * @return mixed Un-sanitized request var.
	 */
	protected function get_request_var( $param = '', $fallback = false ) {
		return isset( $_REQUEST[ $param ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $param ] ) ) : $fallback; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Process bulk action.
	 *
	 * @param string $doaction Action name.
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_actions( $doaction ) {
		if ( ! empty( $_GET['_wp_http_referer'] ) || ! empty( $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect(
				remove_query_arg(
					array(
						'_wp_http_referer',
						'_wpnonce',
					),
					isset( $_SERVER['REQUEST_URI'] ) ?? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
				)
			);
			exit;
		}
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @param Object|array $item The current item.
	 * @param string       $column_name The name of the column.
	 *
	 * @since 1.0.0
	 * @return string The column value.
	 */
	public function column_default( $item, $column_name ) {

		if ( is_object( $item ) && method_exists( $item, "get_$column_name" ) ) {
			$getter = "get_$column_name";

			return empty( $item->$getter( 'view' ) ) ? '&mdash;' : esc_html( $item->$getter( 'view' ) );
		} elseif ( is_array( $item ) && isset( $item[ $column_name ] ) ) {
			return empty( $item[ $column_name ] ) ? '&mdash;' : esc_html( $item[ $column_name ] );
		}

		return '&mdash;';
	}

	/**
	 * Return the status filter for this request, if any.
	 *
	 * @param string $fallback Default status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_request_status( $fallback = null ) {
		wp_verify_nonce( '_wpnonce' );
		$status = ( ! empty( $_GET['code_type'] ) ) ? sanitize_text_field( wp_unslash( $_GET['code_type'] ) ) : '';

		return empty( $status ) ? $fallback : $status;
	}
}
