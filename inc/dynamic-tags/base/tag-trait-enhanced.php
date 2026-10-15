<?php
/**
 * Enhanced Tag Trait
 *
 * Provides additional functionality for dynamic tags including
 * taxonomy handling, meta filtering, and utility methods.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enhanced Tag Trait.
 *
 * Provides additional functionality for dynamic tags including
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags
 */
trait Tag_Trait {
	/**
	 * Render taxonomy content by key
	 *
	 * @param string $key Property key to render (name, description, etc.).
	 *
	 * @return void
	 */
	protected function render_taxonomy_content_by_key( $key = 'name' ) {
		global $wp_query;

		if ( ! isset( $wp_query->loop_term ) || ! is_object( $wp_query->loop_term ) ) {
			return;
		}

		$content = '';

		if ( isset( $wp_query->loop_term->$key ) ) {
			$content = $wp_query->loop_term->$key;
		}

		echo wp_kses_post( $content );
	}

	/**
	 * Get the term ID from a taxonomy loop query
	 *
	 * @return int
	 */
	protected function get_data_id_from_taxonomy_loop_query() {
		global $wp_query;

		if ( isset( $wp_query->loop_term ) && isset( $wp_query->loop_term->term_id ) ) {
			return $wp_query->loop_term->term_id;
		}

		return 0;
	}

	/**
	 * Get author meta data.
	 *
	 * @param string   $key       Meta key.
	 * @param int|null $author_id Author ID.
	 *
	 * @return mixed
	 */
	protected function get_author_meta( $key, $author_id = null ) {
		if ( null === $author_id ) {
			$author_id = get_the_author_meta( 'ID' );
		}

		return get_the_author_meta( $key, $author_id );
	}

	/**
	 * Check if the field is allowed for the current user.
	 *
	 * @param string $field Field name.
	 * @param array  $restricted_fields Restricted field names.
	 *
	 * @return bool
	 */
	protected function is_field_allowed( $field, $restricted_fields = array() ) {
		// Admins can access all fields.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if an admin-authored post.
		if ( $this->is_post_authored_by_admin() ) {
			return true;
		}

		return ! in_array( $field, $restricted_fields, true );
	}

	/**
	 * Check if the current post was authored by an admin.
	 *
	 * @return bool
	 */
	protected function is_post_authored_by_admin() {
		$post = get_post();

		if ( ! $post ) {
			return false;
		}

		$post_author_id = $post->post_author ?? 0;
		return user_can( $post_author_id, 'manage_options' );
	}

	/**
	 * Get queried object meta.
	 *
	 * @param string $meta_key Meta key.
	 *
	 * @return mixed
	 */
	protected function get_queried_object_meta( $meta_key ) {
		$value = '';

		if ( is_singular() ) {
			$value = get_post_meta( get_the_ID(), $meta_key, true );
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$value = get_term_meta( get_queried_object_id(), $meta_key, true );
		}

		return $value;
	}

	/**
	 * Format number with suffix (K, M, B).
	 *
	 * @param int|float $number Number to format.
	 *
	 * @return string
	 */
	protected function format_number( $number ) {
		if ( function_exists( 'aaeaddon_format_number_count' ) ) {
			return aaeaddon_format_number_count( $number );
		}

		if ( $number >= 1000000000 ) {
			return round( $number / 1000000000, 1 ) . 'B';
		} elseif ( $number >= 1000000 ) {
			return round( $number / 1000000, 1 ) . 'M';
		} elseif ( $number >= 1000 ) {
			return round( $number / 1000, 1 ) . 'K';
		}

		return $number;
	}

	/**
	 * Trim text by words.
	 *
	 * @param string $text       Text to trim.
	 * @param int    $word_count Number of words.
	 * @param string $more       More text.
	 *
	 * @return string
	 */
	protected function trim_words( $text, $word_count = 55, $more = '...' ) {
		return wp_trim_words( $text, $word_count, $more );
	}

	/**
	 * Get archive info.
	 *
	 * @return array
	 */
	protected function get_archive_info() {
		$info = array(
			'type'   => '',
			'object' => null,
		);

		if ( is_category() || is_tag() || is_tax() ) {
			$info['type']   = 'taxonomy';
			$info['object'] = get_queried_object();
		} elseif ( is_author() ) {
			$info['type']   = 'author';
			$info['object'] = get_queried_object();
		} elseif ( is_date() ) {
			$info['type'] = 'date';
		} elseif ( is_post_type_archive() ) {
			$info['type']   = 'post_type';
			$info['object'] = get_queried_object();
		}

		return $info;
	}
}
