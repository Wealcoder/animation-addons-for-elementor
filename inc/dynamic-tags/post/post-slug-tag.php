<?php
/**
 * Post Slug Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Post;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post Slug Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Slug_Tag extends Tag_Base {
	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-slug';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Slug', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-posts' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Render the post slug.
	 *
	 * @return void
	 */
	public function render() {
		$post_id = $this->get_post_id();
		$post    = get_post( $post_id );
		if ( $post ) {
			echo esc_html( $post->post_name );
		}
	}
}
