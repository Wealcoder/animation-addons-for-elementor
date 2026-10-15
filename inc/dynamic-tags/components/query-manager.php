<?php
/**
 * Dynamic Tags Query Manager
 *
 * Handles database queries for dynamic tags autocomplete functionality.
 * Completely independent from other modules.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Query Manager Class.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags
 */
class Query_Manager {

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
	 * Get posts for autocomplete.
	 *
	 * @param string $search Search term.
	 * @param string $post_type Post type.
	 *
	 * @return array Results for autocomplete.
	 */
	public function get_posts_for_autocomplete( $search = '', $post_type = 'any' ) {
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows' => true, 
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,

		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query   = new \WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_type_obj   = get_post_type_object( get_post_type() );
				$post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : '';

				$results[] = array(
					'id'   => get_the_ID(),
					'text' => get_the_title() . ' (' . $post_type_label . ' #' . get_the_ID() . ')',
				);
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Get terms for autocomplete.
	 *
	 * @param string $search Search term.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array Results for autocomplete.
	 */
	public function get_terms_for_autocomplete( $search = '', $taxonomy = '' ) {
		// If no specific taxonomy, search all public taxonomies.
		if ( empty( $taxonomy ) ) {
			$taxonomy = get_taxonomies( array( 'public' => true ), 'names' );
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 20,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms   = get_terms( $args );
		$results = array();

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$taxonomy_obj   = get_taxonomy( $term->taxonomy );
				$taxonomy_label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : '';

				$results[] = array(
					'id'   => $term->term_id,
					'text' => $term->name . ' (' . $taxonomy_label . ' #' . $term->term_id . ')',
				);
			}
		}

		return $results;
	}

	/**
	 * Get authors for autocomplete.
	 *
	 * @param string $search Search term.
	 *
	 * @return array Results for autocomplete.
	 */
	public function get_authors_for_autocomplete( $search = '' ) {
		$args = array(
			'number'  => 20,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'who'     => 'authors',
		);

		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email', 'display_name' );
		}

		$users   = get_users( $args );
		$results = array();

		foreach ( $users as $user ) {
			$results[] = array(
				'id'   => $user->ID,
				'text' => $user->display_name . ' (@' . $user->user_login . ' #' . $user->ID . ')',
			);
		}

		return $results;
	}

	/**
	 * Get attachments for autocomplete.
	 *
	 * @param string $search Search term.
	 *
	 * @return array Results for autocomplete.
	 */
	public function get_attachments_for_autocomplete( $search = '' ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows' => true, 
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query   = new \WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$file_type = wp_check_filetype( get_attached_file( get_the_ID() ) );
				$ext       = ! empty( $file_type['ext'] ) ? strtoupper( $file_type['ext'] ) : 'FILE';

				$results[] = array(
					'id'   => get_the_ID(),
					'text' => get_the_title() . ' (' . $ext . ' #' . get_the_ID() . ')',
				);
			}
			wp_reset_postdata();
		}

		return $results;
	}
}
