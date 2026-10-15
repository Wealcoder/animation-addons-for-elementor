<?php
/**
 * Dynamic Tags AJAX Handler
 *
 * Handles AJAX requests for dynamic tags autocomplete functionality.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags;

use Wealcoder\AnimationAddons\Nonce;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AJAX Handler Class.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags
 */
class Ajax_Handler {

	/**
	 * Instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Autocomplete AJAX actions.
		// 'aae_dt_get_posts' is a deprecated alias (a cached admin bundle) -- remove in 4.4.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_dt_get_posts', 'aaeaddon_dt_get_posts', array( $this, 'ajax_get_posts' ) );
		// 'aae_dt_get_terms' is a deprecated alias (a cached admin bundle) -- remove in 4.4.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_dt_get_terms', 'aaeaddon_dt_get_terms', array( $this, 'ajax_get_terms' ) );
		// 'aae_dt_get_authors' is a deprecated alias (a cached admin bundle) -- remove in 4.4.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_dt_get_authors', 'aaeaddon_dt_get_authors', array( $this, 'ajax_get_authors' ) );
		// 'aae_dt_get_attachments' is a deprecated alias (a cached admin bundle) -- remove in 4.4.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_dt_get_attachments', 'aaeaddon_dt_get_attachments', array( $this, 'ajax_get_attachments' ) );
	}

	/**
	 * AJAX handler for post autocomplete.
	 *
	 * @return void
	 */
	public function ajax_get_posts() {
		$this->verify_nonce();

		// Check if we're fetching a specific ID.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$post = get_post( $id );
			if ( $post ) {
				$result = array(
					'id'   => $id,
					'text' => sprintf( '%s (%s)', $post->post_title, get_post_type( $id ) ),
				);
				wp_send_json_success( array( 'results' => array( $result ) ) );
			}
			wp_send_json_success( array( 'results' => array() ) );
		}

		$search    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'any';

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_posts_for_autocomplete( $search, $post_type );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for term autocomplete.
	 *
	 * @return void
	 */
	public function ajax_get_terms() {
		$this->verify_nonce();

		// Check if we're fetching a specific ID.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$term = get_term( $id );
			if ( $term && ! is_wp_error( $term ) ) {
				$taxonomy  = get_taxonomy( $term->taxonomy );
				$tax_label = $taxonomy ? $taxonomy->label : $term->taxonomy;
				$result    = array(
					'id'   => $id,
					'text' => sprintf( '%s (%s)', $term->name, $tax_label ),
				);
				wp_send_json_success( array( 'results' => array( $result ) ) );
			}
			wp_send_json_success( array( 'results' => array() ) );
		}

		$search   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_terms_for_autocomplete( $search, $taxonomy );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for author autocomplete.
	 *
	 * @return void
	 */
	public function ajax_get_authors() {
		$this->verify_nonce();

		// Check if we're fetching a specific ID.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$author = get_user_by( 'id', $id );
			if ( $author ) {
				$result = array(
					'id'   => $id,
					'text' => sprintf( '%s (%s)', $author->display_name, $author->user_login ),
				);
				wp_send_json_success( array( 'results' => array( $result ) ) );
			}
			wp_send_json_success( array( 'results' => array() ) );
		}

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_authors_for_autocomplete( $search );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for attachment autocomplete.
	 *
	 * @return void
	 */
	public function ajax_get_attachments() {
		$this->verify_nonce();

		// Check if we're fetching a specific ID.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			$attachment = get_post( $id );
			if ( $attachment && 'attachment' === $attachment->post_type ) {
				$result = array(
					'id'   => $id,
					'text' => sprintf( '%s (ID: %d)', $attachment->post_title, $id ),
				);
				wp_send_json_success( array( 'results' => array( $result ) ) );
			}
			wp_send_json_success( array( 'results' => array() ) );
		}

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		$query_manager = Query_Manager::instance();
		$results       = $query_manager->get_attachments_for_autocomplete( $search );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Verify nonce for AJAX requests.
	 *
	 * @return void
	 */
	private function verify_nonce() {
		$nonce = '';

		if ( isset( $_REQUEST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );
		} elseif ( isset( $_GET['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
		} elseif ( isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}

		if ( ! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::DYNAMIC_TAGS ) ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		// The nonce proves the request came from a page that printed it; the
		// capability is what says who may read titles of ANY post (drafts and
		// private ones included -- the by-id branches take no status into
		// account) and every term of every taxonomy. That is the editor's own
		// floor, which is where these autocompletes are used.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}
	}
}

// Initialize the handler.
Ajax_Handler::instance();
