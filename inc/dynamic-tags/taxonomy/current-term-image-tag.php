<?php
/**
 * Current Term Image Dynamic Tag
 *
 * For ACF or other plugins that add images to terms
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Taxonomy;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Current Term Image Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Taxonomy
 */
class Current_Term_Image_Tag extends Data_Tag_Base {
	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-current-term-image';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Current Term Image', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-taxonomy' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array(
			TagsModule::IMAGE_CATEGORY,
			TagsModule::URL_CATEGORY,
		);
	}

	/**
	 * Get current term image payload.
	 *
	 * @param array $options Options (unused).
	 * @return array{id:int|null,url:string,alt:string}|array<string,never>
	 */
	protected function get_value( array $options = array() ) {
		if ( ! ( is_category() || is_tag() || is_tax() ) ) {
			return array();
		}

		$term = get_queried_object();
		if ( ! $term || ! isset( $term->term_id ) ) {
			return array();
		}

		$image_id  = null;
		$image_url = '';

		// Try thumbnail_id meta first.
		$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumbnail_id ) {
			$image_id  = $thumbnail_id;
			$image_url = wp_get_attachment_url( $thumbnail_id );
		}

		// Try the ACF image field if available.
		if ( empty( $image_url ) && function_exists( 'get_field' ) ) {
			$acf_image = get_field( 'image', $term );
			if ( is_array( $acf_image ) && isset( $acf_image['url'] ) ) {
				$image_url = $acf_image['url'];
				$image_id  = $acf_image['ID'] ?? null;
			} elseif ( is_string( $acf_image ) ) {
				$image_url = $acf_image;
			}
		}

		if ( empty( $image_url ) ) {
			return array();
		}

		return array(
			'id'  => $image_id,
			'url' => esc_url( $image_url ),
			'alt' => $term->name ?? '',
		);
	}

	/**
	 * Render image URL if available.
	 *
	 * @return void
	 */
	public function render() {
		$value = $this->get_value();
		if ( isset( $value['url'] ) ) {
			echo esc_url( $value['url'] );
		}
	}
}
