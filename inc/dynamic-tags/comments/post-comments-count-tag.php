<?php
/**
 * Post Comments Count Dynamic Tag
 * 
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Comments;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post-Comments Count Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Comments
 */
class Post_Comments_Count_Tag extends Tag_Base {

    /**
     * Unique tag name.
     *
     * @return string
     */
	public function get_name() {
		return 'aae-post-comments-count';
	}

    /**
     * Tag title for Elementor UI.
     *
     * @return string
     */
	public function get_title() {
		return esc_html__( 'Post Comments Count', 'animation-addons-for-elementor' );
	}

    /**
     * Tag group(s).
     *
     * @return array<int,string>
     */
	public function get_group() {
		return [ 'aae-comments' ];
	}

    /**
     * Supported categories.
     *
     * @return array<int,string>
     */
	public function get_categories() {
		return [ 
			TagsModule::TEXT_CATEGORY,
			TagsModule::NUMBER_CATEGORY,
		];
	}

    /**
     * Render numeric comments count.
     *
     * @return void
     */
	public function render() {
		$post_id = $this->get_post_id();
		echo absint( get_comments_number( $post_id ) );
	}
}

