<?php
/**
 * Author Meta Security Filter
 *
 * Prevents non-admin users from exposing sensitive author data (email)
 * unless the post was authored by an admin.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Author Meta Security Filter.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Components
 */
class Author_Meta_Security_Filter {

	/**
	 * Pattern to match author-meta/author-info dynamic tags with email
	 */
	const DYNAMIC_TAG_SHORTCODE_PATTERN = '/\[elementor-tag.*?name=.*?"(aae-author-info|author-info).*?".*?settings=.*?".*?(user_email|email).*?".*?\]/';

	/**
	 * Filter document save data to remove unauthorized email tags.
	 *
	 * @param array                         $data     Element data.
	 * @param \Elementor\Core\Base\Document $document Document object.
	 *
	 * @return array
	 */
	public function filter( $data, $document ) {
		// Admins can do anything.
		if ( current_user_can( 'manage_options' ) || empty( $data['elements'] ) ) {
			return $data;
		}

		$dynamic_tag = $this->get_dynamic_tag( $data['elements'] );

		if ( ! $dynamic_tag ) {
			return $data;
		}

		// Allow if admin authors post.
		if ( $this->is_dynamic_tag_authored_by_admin( $document, $dynamic_tag ) ) {
			return $data;
		}

		// Remove the unauthorized dynamic tags.
		return \Elementor\Plugin::$instance->db->iterate_data(
			$data,
			function ( $element ) {
				if ( $this->is_dynamic_tag_to_escape( $element ) ) {
					$element['settings']['__dynamic__'] = array();
				}

				return $element;
			}
		);
	}

	/**
	 * Check if an admin authored dynamic tag.
	 *
	 * @param \Elementor\Core\Base\Document $document    Document object.
	 * @param string                        $needle_tag Tag to search for.
	 *
	 * @return bool
	 */
	private function is_dynamic_tag_authored_by_admin( $document, $needle_tag ) {
		global $post;

		$post_author_id            = $post->post_author ?? 0;
		$is_post_authored_by_admin = user_can( $post_author_id, 'manage_options' );

		if ( ! $is_post_authored_by_admin ) {
			return false;
		}

		$json = wp_json_encode( $document->get_elements_data() );

		return false !== strpos( $json, $needle_tag );
	}

	/**
	 * Check if an element contains a dynamic tag to escape.
	 *
	 * @param array $element Element data.
	 *
	 * @return bool
	 */
	private function is_dynamic_tag_to_escape( $element ) {
		if ( 'widget' !== $element['elType'] ) {
			return false;
		}

		if ( empty( $element['settings']['__dynamic__'] ) || ! is_array( $element['settings']['__dynamic__'] ) ) {
			return false;
		}

		// Every dynamic control, not only the heading's `title`: the tag works in any text control.
		foreach ( $element['settings']['__dynamic__'] as $tag_text ) {
			if ( is_string( $tag_text ) && 1 === preg_match( self::DYNAMIC_TAG_SHORTCODE_PATTERN, $tag_text ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get dynamic tag from elements.
	 *
	 * @param array $elements Elements array.
	 *
	 * @return string|false
	 */
	private function get_dynamic_tag( $elements ) {
		$json = wp_json_encode( $elements );

		preg_match( self::DYNAMIC_TAG_SHORTCODE_PATTERN, $json, $matches );

		if ( empty( $matches ) ) {
			return false;
		}

		return $matches[0];
	}
}
