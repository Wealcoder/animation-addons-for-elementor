<?php
/**
 * Post Featured Image Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Post;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post Featured Image Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Featured_Image_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-featured-image';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Featured Image', 'animation-addons-for-elementor' );
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
		return array( TagsModule::IMAGE_CATEGORY );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'fallback',
			array(
				'label' => esc_html__( 'Fallback', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
	}

	/**
	 * Get image value payload.
	 *
	 * @param array $options Options (unused).
	 * @return array{id:int|null,url:string}
	 */
	public function get_value( array $options = array() ) {
		$post_id      = $this->get_post_id();
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			$image_url = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			if ( $image_url ) {
				return array(
					'id'  => $thumbnail_id,
					'url' => $image_url[0],
				);
			}
		}

		// Return fallback if no featured image.
		$fallback = $this->get_settings( 'fallback' );
		if ( ! empty( $fallback['id'] ) ) {
			return array(
				'id'  => $fallback['id'],
				'url' => $fallback['url'],
			);
		}

		return array(
			'id'  => null,
			'url' => '',
		);
	}
}
