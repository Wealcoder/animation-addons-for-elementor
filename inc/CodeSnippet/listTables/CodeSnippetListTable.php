<?php

namespace WCF_ADDONS\CodeSnippet\listTables;

use WCF_ADDONS\CodeSnippet\CodeSnippet;
use WCF_ADDONS\CodeSnippet\Helpers;
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
	 * @return void
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$hidden                = $this->get_hidden_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page         = $this->get_items_per_page( 'snippets_per_page', 20 );
		$order_by         = $this->get_sanitized_orderby();
		$order            = $this->get_sanitized_order();
		$search           = $this->get_search_query();
		$current_page     = $this->get_pagenum();
		$code_type_filter = $this->get_code_type_filter();

		// Build query arguments.
		$args = array(
			'post_type'      => 'wcf-code-snippet',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => $this->get_wp_orderby( $order_by ),
			'order'          => $order,
		);

		// Add meta_key for custom field sorting.
		if ( in_array( $order_by, array( 'code_type', 'load_location', 'priority', 'snippet_status' ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$args['meta_key'] = $this->get_meta_key_for_orderby( $order_by ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			if ( 'priority' === $order_by ) {
				$args['orderby'] = 'meta_value_num';
			} else {
				$args['orderby'] = 'meta_value';
			}
		}

		// Handle search.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
			// Add custom search to include meta fields.
			add_filter( 'posts_search', array( $this, 'custom_search_query' ), 10, 2 );
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

		$query = new \WP_Query( $args );

		// Remove the search filter after query.
		if ( ! empty( $search ) ) {
			remove_filter( 'posts_search', array( $this, 'custom_search_query' ), 10 );
		}

		$this->items = $query->posts;
		$total_count = $query->found_posts;

		$this->set_pagination_args(
			array(
				'total_items' => $total_count,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Get sanitized orderby parameter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	private function get_sanitized_orderby() {
		$orderby         = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'post_title'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$allowed_orderby = array( 'post_title', 'date_created', 'code_type', 'load_location', 'priority', 'snippet_status' );
		return in_array( $orderby, $allowed_orderby ) ? $orderby : 'post_title';
	}

	/**
	 * Get sanitized order parameter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	private function get_sanitized_order() {
		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'ASC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'ASC';
	}

	/**
	 * Get search query.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	private function get_search_query() {
		return isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get code type filter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	private function get_code_type_filter() {
		return isset( $_GET['code_type'] ) ? sanitize_text_field( wp_unslash( $_GET['code_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Convert orderby parameter to WP_Query compatible format.
	 *
	 * @param string $orderby The orderby parameter.
	 * @return string
	 * @since 1.0.0
	 */
	private function get_wp_orderby( $orderby ) {
		switch ( $orderby ) {
			case 'post_title':
				return 'title';
			case 'date_created':
				return 'modified';
			default:
				return 'title';
		}
	}

	/**
	 * Get meta-key for custom field sorting.
	 *
	 * @param string $orderby The orderby parameter.
	 * @return string
	 * @since 1.0.0
	 */
	private function get_meta_key_for_orderby( $orderby ) {
		$meta_keys = array(
			'code_type'      => 'code_type',
			'load_location'  => 'load_location',
			'priority'       => 'priority',
			'snippet_status' => 'is_active',
		);

		return isset( $meta_keys[ $orderby ] ) ? $meta_keys[ $orderby ] : 'code_type';
	}

	/**
	 * Custom search query to include meta fields.
	 *
	 * @param string    $search   Search SQL for WHERE clause.
	 * @param \WP_Query $wp_query The current WP_Query object.
	 * @return string Modified search SQL.
	 * @since 1.0.0
	 */
	public function custom_search_query( $search, $wp_query ) {
		global $wpdb;

		if ( empty( $search ) || empty( $wp_query->query_vars['search_terms'] ) ) {
			return $search;
		}

		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';

		$search_terms      = $q['search_terms'];
		$search_conditions = array();

		// Search in post title and content.
		foreach ( $search_terms as $term ) {
			$search_conditions[] = $wpdb->prepare(
				"($wpdb->posts.post_title LIKE %s OR $wpdb->posts.post_content LIKE %s)",
				$n . $wpdb->esc_like( $term ) . $n,
				$n . $wpdb->esc_like( $term ) . $n
			);
		}

		// Search in meta fields (code_content).
		$meta_search_conditions = array();
		foreach ( $search_terms as $term ) {
			$meta_search_conditions[] = $wpdb->prepare(
				"EXISTS (
					SELECT 1 FROM $wpdb->postmeta 
					WHERE $wpdb->postmeta.post_id = $wpdb->posts.ID 
					AND $wpdb->postmeta.meta_key = 'code_content' 
					AND $wpdb->postmeta.meta_value LIKE %s
				)",
				$n . $wpdb->esc_like( $term ) . $n
			);
		}

		// Combine all search conditions.
		$search_conditions = array_merge( $search_conditions, $meta_search_conditions );

		if ( ! is_user_logged_in() ) {
			$search_conditions[] = "$wpdb->posts.post_password = ''";
		}

		$search = ' AND (' . implode( ' OR ', $search_conditions ) . ')';

		return $search;
	}

	/**
	 * Returns an associative array listing all the views that can be used
	 * with this table.
	 *
	 * @return string[] An array of HTML links keyed by their view.
	 * @since 1.0.0
	 */
	protected function get_views() {
		$current      = $this->get_request_status( 'all' );
		$status_links = array();
		$snippets     = array(
			'all'        => __( 'All', 'animation-addons-for-elementor' ),
			'html'       => __( 'HTML', 'animation-addons-for-elementor' ),
			'css'        => __( 'CSS', 'animation-addons-for-elementor' ),
			'javascript' => __( 'JavaScript', 'animation-addons-for-elementor' ),
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

		return $this->get_status_links( $status_links );
	}

	/**
	 * No items found text.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No code snippets found.', 'animation-addons-for-elementor' );
	}

	/**
	 * Get the table columns
	 *
	 * @return array
	 * @since 1.0.0
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
			'date_created'    => __( 'Last Modified', 'animation-addons-for-elementor' ),
		);
	}

	/**
	 * Get the table sortable columns
	 *
	 * @return array
	 * @since 1.0.0
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
	 * Get bulk actions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete'     => __( 'Delete', 'animation-addons-for-elementor' ),
			'activate'   => __( 'Activate', 'animation-addons-for-elementor' ),
			'deactivate' => __( 'Deactivate', 'animation-addons-for-elementor' ),
		);
	}

	/**
	 * Get the table hidden columns
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Process bulk action - Disabled for AJAX handling
	 *
	 * @param string $doaction Action name.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function process_bulk_action( $doaction ) {
		// Bulk actions are now handled via AJAX, so we don't process them here.
		// This method is kept for compatibility but does nothing.
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_primary_column_name() {
		return 'name';
	}

	/**
	 * Renders the checkbox column in the items list table.
	 *
	 * @param object $item The current snippet object.
	 *
	 * @return string Displays a checkbox.
	 * @since  1.0.0
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d"/>', esc_attr( $item->ID ) );
	}

	/**
	 * Renders the name column in the item list table.
	 *
	 * @param object $item The current snippet object.
	 *
	 * @return string Displays the snippet name with actions.
	 * @since  1.0.0
	 */
	public function column_name( $item ) {
		$edit_url = add_query_arg( 'edit', $item->ID, $this->base_url );

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'animation-addons-for-elementor' ) ),
			'delete' => sprintf(
				'<a href="#" class="ajax-delete-snippet" data-id="%d">%s</a>',
				esc_attr( $item->ID ),
				__( 'Delete', 'animation-addons-for-elementor' )
			),
		);

		$title = sprintf(
			'<a href="%s"><strong>%s</strong></a>',
			esc_url( $edit_url ),
			esc_html( $item->post_title ? $item->post_title : __( '(no title)', 'animation-addons-for-elementor' ) )
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @param object $item The current snippet object.
	 * @param string $column_name The name of the column.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$value = '&mdash;';

		switch ( $column_name ) {
			case 'code_type':
				$code_type = get_post_meta( $item->ID, 'code_type', true );
				if ( ! empty( $code_type ) ) {
					$value = '<span class="code-type code-type-' . esc_attr( $code_type ) . '">' .
						esc_html( strtoupper( str_replace( array( '-', '_' ), ' ', $code_type ) ) ) .
						'</span>';
				}
				break;

			case 'visibility_list':
				$id              = $item->ID;
				$visibility_list = get_post_meta( $id, 'visibility_page_list', true );
				$visibility_page = get_post_meta( $id, 'visibility_page', true );
				if ( ! empty( $visibility_list ) && is_array( $visibility_list ) && 'specifics' === $visibility_page ) {
					$value = '';
					foreach ( $visibility_list as $visibility ) {
						$value .= '<a href="' . get_the_permalink( $visibility ) . '"><span class="visibility-list-item">' . esc_html( get_the_title( $visibility ) ) . '</span></a>,';
					}
					$value = rtrim( $value, ',' );
				} else {
					$value = ucwords( $visibility_page );
				}
				break;

			case 'load_location':
				$load_location = get_post_meta( $item->ID, 'load_location', true );
				if ( ! empty( $load_location ) ) {
					$value = '<span class="load-location">' .
						esc_html( strtoupper( str_replace( array( '-', '_' ), ' ', $load_location ) ) ) .
						'</span>';
				}
				break;

			case 'date_created':
				$date = $item->post_modified;
				if ( $date ) {
					$value = sprintf(
						'<time datetime="%s" title="%s">%s</time>',
						esc_attr( $date ),
						esc_attr( mysql2date( 'c', $date ) ),
						esc_html( human_time_diff( strtotime( $date ), current_time( 'timestamp' ) ) . ' ago' ) // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					);
				}
				break;

			case 'priority':
				$priority = get_post_meta( $item->ID, 'priority', true );
				if ( '' !== $priority ) {
					$value = '<span class="priority priority-' . esc_attr( $priority ) . '">' .
						esc_html( $priority ) .
						'</span>';
				}
				break;

			case 'snippet_status':
				$value = Helpers::get_status_toggle( $item );
				break;

			default:
				$value = apply_filters( 'wcf_code_snippet_list_table_column_' . $column_name, $value, $item );
		}

		return $value;
	}
}
