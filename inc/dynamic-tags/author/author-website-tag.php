<?php
/**
 * Author Website Dynamic Tag
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
 * Author Website Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Author
 */
class Author_Website_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-author-website';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Author Website', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-author' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array(
			TagsModule::TEXT_CATEGORY,
			TagsModule::URL_CATEGORY,
		);
	}

	/**
	 * Get website URL value.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}

		$website = get_the_author_meta( 'user_url', $author_id );

		if ( empty( $website ) ) {
			return '';
		}

		return esc_url( $website );
	}

	/**
	 * Render website URL for text contexts.
	 *
	 * @return void
	 */
	public function render() {
		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}

		$website = get_the_author_meta( 'user_url', $author_id );
		
		if ( empty( $website ) ) {
			return;
		}

		echo esc_url( $website );
	}
}
