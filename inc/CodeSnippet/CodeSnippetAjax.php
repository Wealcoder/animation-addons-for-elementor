<?php

namespace WCF_ADDONS\CodeSnippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

/**
 * CodeSnippetAjax Class
 *
 * Handles all AJAX operations for the CodeSnippet module
 *
 * @package WCF_ADDONS\CodeSnippet
 * @since 2.3.10
 */
class CodeSnippetAjax {

	/**
	 * Instance of the class
	 *
	 * @since 2.3.10
	 * @var CodeSnippetAjax
	 */
	private static $_instance = null;

	/**
	 * Get a singleton instance
	 *
	 * @since 2.3.10
	 * @return CodeSnippetAjax
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * CodeSnippetAjax constructor.
	 *
	 * @since 2.3.10
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize AJAX hooks
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function init_hooks() {
		// AJAX handlers for list table operations.
		add_action( 'wp_ajax_wcf_search_snippets', array( $this, 'ajax_search_snippets' ) );
		add_action( 'wp_ajax_wcf_delete_snippet', array( $this, 'ajax_delete_snippet' ) );
		add_action( 'wp_ajax_wcf_bulk_action_snippets', array( $this, 'ajax_bulk_action_snippets' ) );
		add_action( 'wp_ajax_wcf_toggle_snippet_status', array( $this, 'ajax_toggle_snippet_status' ) );
	}

	/**
	 * Verify AJAX request security
	 *
	 * @since 2.3.10
	 * @return bool
	 */
	private function verify_ajax_security() {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wcf_custom_code_security' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'animation-addons-for-elementor' ) ) );
			return false;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'animation-addons-for-elementor' ) ) );
			return false;
		}

		return true;
	}

	/**
	 * AJAX handler for searching snippets.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function ajax_search_snippets() {
		if ( ! $this->verify_ajax_security() ) {
			return;
		}

		$search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$code_type   = isset( $_POST['code_type'] ) ? sanitize_text_field( wp_unslash( $_POST['code_type'] ) ) : 'all';
		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page    = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;

		// Build query arguments.
		$args = array(
			'post_type'      => CodeSnippet::CPTTYPE,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		// Handle search.
		if ( ! empty( $search_term ) ) {
			$args['s'] = $search_term;
			// Add custom search filter to include meta fields.
			add_filter( 'posts_search', array( $this, 'custom_search_query' ), 10, 2 );
		}

		// Handle code type filtering.
		if ( ! empty( $code_type ) && 'all' !== $code_type ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'code_type',
					'value'   => $code_type,
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $args );

		// Remove the search filter after query.
		if ( ! empty( $search_term ) ) {
			remove_filter( 'posts_search', array( $this, 'custom_search_query' ), 10 );
		}

		$snippets = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$snippet_id = get_the_ID();
				$snippets[] = array(
					'id'              => $snippet_id,
					'title'           => get_the_title(),
					'code_type'       => get_post_meta( $snippet_id, 'code_type', true ),
					'visibility_list' => $this->ajax_get_visibility_options( $snippet_id ),
					'load_location'   => get_post_meta( $snippet_id, 'load_location', true ),
					'priority'        => get_post_meta( $snippet_id, 'priority', true ),
					'is_active'       => get_post_meta( $snippet_id, 'is_active', true ),
					'date_modified'   => sprintf(
						'<time datetime="%s" title="%s">%s</time>',
						esc_attr( get_the_modified_date( 'Y-m-d H:i:s' ) ),
						esc_attr( mysql2date( 'c', get_the_modified_date( 'Y-m-d H:i:s' ) ) ),
						esc_html( human_time_diff( strtotime( get_the_modified_date( 'Y-m-d H:i:s' ) ), current_time( 'timestamp' ) ) . ' ago' ) // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					),
					'edit_url'        => admin_url( 'admin.php?page=wcf-code-snippet&edit=' . $snippet_id ),
				);
			}
		}

		wp_reset_postdata();

		wp_send_json_success(
			array(
				'snippets'     => $snippets,
				'total'        => $query->found_posts,
				'total_pages'  => $query->max_num_pages,
				'current_page' => $page,
			)
		);
	}

	/**
	 * Get visibility options for a single item.
	 *
	 * @param int $id Itemed ID.
	 *
	 * @since 2.3.10
	 * @return string
	 */
	public function ajax_get_visibility_options( $id ) {
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

		return $value;
	}

	/**
	 * AJAX handler for deleting a single snippet.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function ajax_delete_snippet() {
		if ( ! $this->verify_ajax_security() ) {
			return;
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;

		if ( ! $snippet_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid snippet ID.', 'animation-addons-for-elementor' ) ) );
		}

		// Validate snippet exists and is of correct post-type.
		$snippet = get_post( $snippet_id );
		if ( ! $snippet || CodeSnippet::CPTTYPE !== $snippet->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid snippet.', 'animation-addons-for-elementor' ) ) );
		}

		// Delete the snippet.
		$deleted = wp_delete_post( $snippet_id, true );

		if ( $deleted ) {
			wp_send_json_success(
				array(
					'message'    => __( 'Snippet deleted successfully.', 'animation-addons-for-elementor' ),
					'snippet_id' => $snippet_id,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete snippet.', 'animation-addons-for-elementor' ) ) );
		}
	}

	/**
	 * AJAX handler for bulk actions on snippets.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function ajax_bulk_action_snippets() {
		if ( ! $this->verify_ajax_security() ) {
			return;
		}

		$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$ids    = isset( $_POST['snippet_ids'] ) ? array_map( 'absint', $_POST['snippet_ids'] ) : array();

		if ( empty( $action ) || empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action or no snippets selected.', 'animation-addons-for-elementor' ) ) );
		}

		$processed_count = 0;
		$action_message  = '';

		switch ( $action ) {
			case 'delete':
				foreach ( $ids as $snippet_id ) {
					$snippet = get_post( $snippet_id );
					if ( $snippet && CodeSnippet::CPTTYPE === $snippet->post_type ) {
						if ( wp_delete_post( $snippet_id, true ) ) {
							++$processed_count;
						}
					}
				}
				$action_message = sprintf(
					/* translators: %d: Number of items deleted. */
					_n( '%d snippet deleted.', '%d snippets deleted.', $processed_count, 'animation-addons-for-elementor' ),
					$processed_count
				);
				break;

			case 'activate':
				foreach ( $ids as $snippet_id ) {
					$snippet = get_post( $snippet_id );
					if ( $snippet && CodeSnippet::CPTTYPE === $snippet->post_type ) {
						if ( update_post_meta( $snippet_id, 'is_active', 'yes' ) ) {
							++$processed_count;
						}
					}
				}
				$action_message = sprintf(
					/* translators: %d: Number of items activated. */
					_n( '%d snippet activated.', '%d snippets activated.', $processed_count, 'animation-addons-for-elementor' ),
					$processed_count
				);
				break;

			case 'deactivate':
				foreach ( $ids as $snippet_id ) {
					$snippet = get_post( $snippet_id );
					if ( $snippet && CodeSnippet::CPTTYPE === $snippet->post_type ) {
						if ( update_post_meta( $snippet_id, 'is_active', 'no' ) ) {
							++$processed_count;
						}
					}
				}
				$action_message = sprintf(
					/* translators: %d: Number of items deactivated. */
					_n( '%d snippet deactivated.', '%d snippets deactivated.', $processed_count, 'animation-addons-for-elementor' ),
					$processed_count
				);
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid bulk action.', 'animation-addons-for-elementor' ) ) );
		}

		if ( $processed_count > 0 ) {
			wp_send_json_success(
				array(
					'message'         => $action_message,
					'processed_count' => $processed_count,
					'action'          => $action,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'No snippets were processed.', 'animation-addons-for-elementor' ) ) );
		}
	}

	/**
	 * AJAX handler for toggling snippet status.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function ajax_toggle_snippet_status() {
		if ( ! $this->verify_ajax_security() ) {
			return;
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? intval( $_POST['snippet_id'] ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		// Validate snippet exists and is of correct post-type.
		$snippet = get_post( $snippet_id );
		if ( ! $snippet || CodeSnippet::CPTTYPE !== $snippet->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid snippet.', 'animation-addons-for-elementor' ) ) );
		}

		// Update the status.
		$updated = update_post_meta( $snippet_id, 'is_active', $status );

		if ( $updated ) {
			$status_text = ( 'yes' === $status ) ? __( 'Activated', 'animation-addons-for-elementor' ) : __( 'Deactivated', 'animation-addons-for-elementor' );
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s: snippet status text. */
						__( 'Snippet %s successfully.', 'animation-addons-for-elementor' ),
						$status_text
					),
					'status'  => $status,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update snippet status.', 'animation-addons-for-elementor' ) ) );
		}
	}

	/**
	 * Custom search query to include meta fields.
	 * This extends the default WordPress search to include post meta fields.
	 *
	 * @param string    $search   Search SQL for WHERE clause.
	 * @param \WP_Query $wp_query The current WP_Query object.
	 * @return string Modified search SQL.
	 * @since 2.3.10
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
}

// Initialize the AJAX handler.
CodeSnippetAjax::instance();
