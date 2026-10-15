<?php
/**
 * Post Excerpt Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Post;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post-Excerpt Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Excerpt_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-excerpt';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Excerpt', 'animation-addons-for-elementor' );
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
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'excerpt_length',
			array(
				'label'   => esc_html__( 'Excerpt Length', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 55,
				'min'     => 1,
				'max'     => 999,
			)
		);
	}

	/**
	 * Render the excerpt with a configurable length.
	 *
	 * @return void
	 */
	public function render() {
		$post_id = $this->get_post_id();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$excerpt_length = $this->get_settings( 'excerpt_length' );

		if ( $post->post_excerpt ) {
			$excerpt = $post->post_excerpt;
		} else {
			$excerpt = $post->post_content;
		}

		$excerpt = $this->trim_words( $excerpt, $excerpt_length );

		echo wp_kses_post( $excerpt );
	}
}
