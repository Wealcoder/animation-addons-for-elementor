<?php
/**
 * Author Avatar Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Author;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Author Avatar Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Author
 */
class Author_Avatar_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name() {
		return 'aae-author-avatar';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Author Avatar', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	public function get_group() {
		return array( 'aae-author' );
	}

	/**
	 * Supported categories.
	 *
	 * @since 1.0.0
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array(
			TagsModule::IMAGE_CATEGORY,
			TagsModule::URL_CATEGORY,
		);
	}

	/**
	 * Get author avatar URL.
	 *
	 * @param array $options Options.
	 *
	 * @since 1.0.0
	 * @return array|string
	 */
	protected function get_value( array $options = array() ) {
		$author_id = get_the_author_meta( 'ID' );

		if ( empty( $author_id ) ) {
			return array();
		}

		// Get avatar URL.
		$avatar_url = get_avatar_url( $author_id, array( 'size' => 300 ) );

		// For IMAGE_CATEGORY, return an array with image data.
		if ( ! empty( $options['return'] ) && 'url' === $options['return'] ) {
			return esc_url( $avatar_url );
		}

		// Return image data array for IMAGE_CATEGORY.
		return array(
			'id'  => null,
			'url' => esc_url( $avatar_url ),
			'alt' => sprintf(
				/* translators: %s: Author name */
				esc_html__( '%s Avatar', 'animation-addons-for-elementor' ),
				get_the_author_meta( 'display_name' )
			),
		);
	}

	/**
	 * Render the tag.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render() {
		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}
		
		if ( empty( $author_id ) ) {
			return;
		}

		echo get_avatar( $author_id, 96 );
	}
}
