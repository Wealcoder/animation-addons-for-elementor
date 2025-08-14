<?php

namespace WCF_ADDONS\CodeSnippet\listTables;

use WCF_ADDONS\CodeSnippet\CodeSnippet;
use WCF_ADDONS\CodeSnippet\listTables\AbstractListTable;

defined( 'ABSPATH' ) || exit;

/**
 * CodeSnippetListTable ListTable class.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
class CodeSnippetListTable extends AbstractListTable {
	/**
	 * Get a snippet table.
	 *
	 * @param array $args Optional.
	 *
	 * @see WP_List_Table::__construct()
	 * @since  1.0.0
	 */
	public function __construct( $args = array() ) {
		$args           = wp_parse_args(
			$args,
			array(
				'singular' => 'snippet',
				'plural'   => 'snippets',
				'ajax'     => true,
			)
		);
		$this->screen   = get_current_screen();
		$this->base_url = admin_url( 'admin.php?page=wcf-code-snippet' );
		parent::__construct( $args );
	}

	/**
	 * Retrieve all the data for the table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$hidden                = $this->get_hidden_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page         = 20;
		$order_by         = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'post_title'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order            = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'ASC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search           = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code_type_filter = isset( $_GET['code_type'] ) ? sanitize_text_field( wp_unslash( $_GET['code_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Build query arguments.
		$args = array(
			'post_type'      => 'wcf-code-snippet',
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		// Handle search.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Handle code type filtering.
		if ( ! empty( $code_type_filter ) && 'all' !== $code_type_filter ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'code_type',
					'value'   => $code_type_filter,
					'compare' => '=',
				),
			);
		}
		switch ( $order_by ) {
			case 'post_title':
				$args['orderby'] = 'title';
				break;
			case 'date_created':
				$args['orderby'] = 'modified';
				break;
			case 'code_type':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = 'code_type'; // phpcs:ignore
				break;
			case 'load_location':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = 'load_location'; // phpcs:ignore
				break;
			case 'priority':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'priority'; // phpcs:ignore
				break;
			case 'snippet_status':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = 'is_active'; // phpcs:ignore
				break;
			default:
				$args['orderby'] = 'title';
		}

		$args['order'] = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ) ) ? strtoupper( $order ) : 'ASC'; // phpcs:ignore

		$query = new \WP_Query( $args );

		$this->items = $query->posts;
		$total_count = $query->found_posts;
		$total_pages = $query->max_num_pages;

		$this->set_pagination_args(
			array(
				'total_items' => $total_count,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			)
		);
	}

	/**
	 * Returns an associative array listing all the views that can be used
	 * with this table.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] An array of HTML links keyed by their view.
	 */
	protected function get_views() {
		$current      = $this->get_request_status( 'all' );
		$status_links = array();
		$snippets     = array(
			'all'        => __( 'All', 'animation-addons-for-elementor' ),
			'html'       => __( 'HTML', 'animation-addons-for-elementor' ),
			'css'        => __( 'CSS', 'animation-addons-for-elementor' ),
			'javascript' => __( 'JAVA SCRIPT', 'animation-addons-for-elementor' ),
			'php'        => __( 'PHP', 'animation-addons-for-elementor' ),
		);

		foreach ( $snippets as $snippet => $label ) {
			$link  = 'all' === $snippet ? $this->base_url : add_query_arg( 'code_type', $snippet, $this->base_url );
			$args  = 'all' === $snippet ? array() : array( 'code_type' => $snippet );
			$count = CodeSnippet::get_snippet_count( $args );
			$label = sprintf( '%s <span class="count">(%s)</span>', esc_html( $label ), number_format_i18n( $count ) );

			$status_links[ $snippet ] = array(
				'url'     => $link,
				'label'   => $label,
				'current' => $current === $snippet,
			);
		}

		return $this->get_views_links( $status_links );
	}

	/**
	 * No items found text.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No code snippet found.', 'animation-addons-for-elementor' );
	}

	/**
	 * Get the table columns
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'name'            => __( 'Title', 'animation-addons-for-elementor' ),
			'code_type'       => __( 'Code Type', 'animation-addons-for-elementor' ),
			'visibility_list' => __( 'Visibility List', 'animation-addons-for-elementor' ),
			'load_location'   => __( 'Load Location', 'animation-addons-for-elementor' ),
			'priority'        => __( 'Priority', 'animation-addons-for-elementor' ),
			'snippet_status'  => __( 'Status', 'animation-addons-for-elementor' ),
			'date_created'    => __( 'Date Modified', 'animation-addons-for-elementor' ),
		);
	}

	/**
	 * Get the table sortable columns
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name'           => array( 'post_title', true ),
			'date_created'   => array( 'date_created', true ),
			'code_type'      => array( 'code_type', true ),
			'load_location'  => array( 'load_location', true ),
			'priority'       => array( 'priority', true ),
			'snippet_status' => array( 'snippet_status', true ),
		);
	}

	/**
	 * Get the table hidden columns
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Get bulk actions
	 *
	 * since 1.0.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'animation-addons-for-elementor' ),
		);
	}

	/**
	 * Process bulk action.
	 *
	 * @param string $doaction Action name.
	 *
	 * @since 1.0.0
	 */
	/**
	 * Process bulk action.
	 *
	 * @param string $doaction Action name.
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action( $doaction ) {
		if ( empty( $doaction ) || ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
			parent::process_bulk_actions( $doaction );
			return;
		}

		$id  = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		$ids = filter_input( INPUT_GET, 'ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Handle single item deletion.
		if ( ! empty( $id ) ) {
			$ids = array( absint( $id ) );
		} elseif ( ! empty( $ids ) ) {
			$ids = array_map( 'absint', $ids );
		}

		// If no valid IDs found, return without redirect.
		if ( empty( $ids ) ) {
			return;
		}

		$deleted_count = 0;

		switch ( $doaction ) {
			case 'delete':
				foreach ( $ids as $snippet_id ) {
					if ( wp_delete_post( $snippet_id, true ) ) {
						++$deleted_count;
					}
				}
				break;
		}

		// Prepare redirect URL.
		$redirect_url = remove_query_arg(
			array( 'action', 'action2', 'ids', 'id', '_wpnonce', '_wp_http_referer' ),
			wp_get_referer()
		);

		// Add status parameters for notice display.
		if ( $deleted_count > 0 ) {
			$redirect_url = add_query_arg( 'deleted', $deleted_count, $redirect_url );
		}

		// Clean any output and redirect.
		if ( ! headers_sent() ) {
			if ( ob_get_level() ) {
				ob_clean();
			}
		}

		wp_safe_redirect( $redirect_url );
		exit();
	}

	/**
	 * Define primary column.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_primary_column_name() {
		return 'name';
	}

	/**
	 * Renders the checkbox column in the items list table.
	 *
	 * @param object $item The current ticket object.
	 *
	 * @since  1.0.0
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d"/>', esc_attr( $item->ID ) );
	}

	/**
	 * Renders the name column in the item list table.
	 *
	 * @param object $item The current post-tab object.
	 *
	 * @since  1.0.0
	 * @return string Displays the tab name.
	 */
	public function column_name( $item ) {
		$admin_url = admin_url( 'admin.php?page=wcf-code-snippet&' );
		$id_url    = add_query_arg( 'id', $item->ID, $admin_url );
		$actions   = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'edit', $item->ID, $admin_url ) ), __( 'Edit', 'animation-addons-for-elementor' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', wp_nonce_url( add_query_arg( 'action', 'delete', $id_url ), 'bulk-snippets' ), __( 'Delete', 'animation-addons-for-elementor' ) ),
		);

		return sprintf( '<a href="%s">%s</a> %s', esc_url( add_query_arg( 'edit', $item->ID, $admin_url ) ), esc_html( $item->post_title ), $this->row_actions( $actions ) );
	}


	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @param object $item The current tab object.
	 * @param string $column_name The name of the column.
	 *
	 * @since 1.0.0
	 */
	public function column_default( $item, $column_name ) {
		$value = '&mdash;';
		switch ( $column_name ) {
			case 'code_type':
				$id    = $item->ID;
				$value = strtoupper( str_replace( '-', ' ', esc_html( get_post_meta( $id, 'code_type', true ) ) ) );
				break;
			case 'visibility_list':
				$id              = $item->ID;
				$visibility_list = get_post_meta( $id, 'visibility_page_list', true );
				if ( ! empty( $visibility_list ) && is_array( $visibility_list ) ) {
					$value = '';
					foreach ( $visibility_list as $visibility ) {
						$value .= '<a href="' . get_the_permalink( $visibility ) . '"><span class="visibility-list-item">' . esc_html( get_the_title( $visibility ) ) . '</span></a>,';
					}
					$value = rtrim( $value, ',' );
				}
				break;
			case 'load_location':
				$id = $item->ID;
				if ( ! empty( get_post_meta( $id, 'load_location', true ) ) ) {
					$value = strtoupper( str_replace( '-', ' ', esc_attr( get_post_meta( $id, 'load_location', true ) ) ) );
				}
				break;
			case 'date_created':
				$date = $item->post_modified;
				if ( $date ) {
					$value = sprintf( '<time datetime="%s">%s</time>', esc_attr( $date ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) );
				}
				break;
			case 'priority':
				$id    = $item->ID;
				$value = strtoupper( str_replace( '-', ' ', absint( get_post_meta( $id, 'priority', true ) ) ) );
				break;
			case 'snippet_status':
				$value = $this->get_status_toggle( $item );
				break;
			default:
				$value = parent::column_default( $item, $column_name );
		}

		return $value;
	}

	/**
	 * Get the status toggle HTML for a snippet.
	 *
	 * @param object $item The current snippet object.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_status_toggle( $item ) {
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
}
