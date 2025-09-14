<?php

namespace WCF_ADDONS\CodeSnippet\listTables;

defined( 'ABSPATH' ) || exit;

// Load WP_List_Table if not loaded.
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Abstract List table class.
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
	 * Process bulk action.
	 *
	 * @param string $doaction Action name.
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_actions( $doaction ) {
		// Clean up URL parameters after processing.
		if ( ! empty( $_GET['_wp_http_referer'] ) || ! empty( $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect(
				remove_query_arg(
					array(
						'_wp_http_referer',
						'_wpnonce',
					),
					$this->get_current_url()
				)
			);
			exit;
		}
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
		$status = isset( $_GET['code_type'] ) ? sanitize_text_field( wp_unslash( $_GET['code_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return empty( $status ) ? $fallback : $status;
	}

	/**
	 * Get current URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_current_url() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			return esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		return '';
	}

	/**
	 * Get views links HTML.
	 *
	 * @param array $status_links Array of status link data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_status_links( $status_links ) {
		$links = array();

		foreach ( $status_links as $key => $link ) {
			$class         = $link['current'] ? ' class="current"' : '';
			$links[ $key ] = sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( $link['url'] ),
				$class,
				$link['label']
			);
		}

		return $links;
	}

	/**
	 * Display admin notices/messages.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		// This method can be overridden by child classes to display notices.
		if ( isset( $_GET['message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message  = sanitize_text_field( wp_unslash( $_GET['message'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$messages = $this->get_admin_messages();

			if ( isset( $messages[ $message ] ) ) {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $messages[ $message ] )
				);
			}
		}
	}

	/**
	 * Get admin messages.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_admin_messages() {
		return array(
			'deleted'     => __( 'Items deleted successfully.', 'animation-addons-for-elementor' ),
			'activated'   => __( 'Items activated successfully.', 'animation-addons-for-elementor' ),
			'deactivated' => __( 'Items deactivated successfully.', 'animation-addons-for-elementor' ),
		);
	}

	/**
	 * Get the CSS classes for the WP_List_Table table tag.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', $this->_args['plural'] );
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No items found.', 'animation-addons-for-elementor' );
	}

	/**
	 * Handles the default column output.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The current column name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '&mdash;';
	}

	/**
	 * Get row actions.
	 *
	 * @param array $actions Array of actions.
	 * @param bool  $always_visible Whether actions should always be visible.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return '';
		}

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		if ( 'excerpt' === $mode ) {
			$always_visible = true;
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		$i = 0;
		foreach ( $actions as $action => $link ) {
			++$i;
			$sep  = ( $i < $action_count ) ? ' | ' : '';
			$out .= "<span class='$action'>$link$sep</span>";
		}

		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
			__( 'Show more details', 'animation-addons-for-elementor' ) .
			'</span></button>';

		return $out;
	}

	/**
	 * Generate and display row action links.
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return $column_name === $primary ? $this->row_actions( $this->get_row_actions( $item ) ) : '';
	}

	/**
	 * Get row actions for an item.
	 * This method should be overridden by child classes.
	 *
	 * @param object $item The item.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_row_actions( $item ) {
		return array();
	}

	/**
	 * Get items per page from user meta or use default.
	 *
	 * @param string $option  Option name.
	 * @param int    $default_value Default value.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	protected function get_items_per_page( $option, $default_value = 20 ) {
		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $default_value;
		}

		return $per_page;
	}

	/**
	 * Add screen options for items per page.
	 *
	 * @param string $option    Option name.
	 * @param string $label     Label for the option.
	 * @param int    $default_value   Default value.
	 *
	 * @since 1.0.0
	 */
	public function add_screen_option( $option, $label, $default_value = 20 ) {
		$args = array(
			'label'   => $label,
			'default' => $default_value,
			'option'  => $option,
		);
		add_screen_option( 'per_page', $args );
	}
}
