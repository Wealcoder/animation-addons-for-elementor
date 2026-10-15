<?php
/**
 * Current Term URL Dynamic Tag
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
 * Current Term URL Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Taxonomy
 */
class Current_Term_URL_Tag extends Data_Tag_Base {
	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-current-term-url';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Current Term URL', 'animation-addons-for-elementor' );
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
			TagsModule::TEXT_CATEGORY,
			TagsModule::URL_CATEGORY,
		);
	}

	/**
	 * Get current term URL.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$queried_object = get_queried_object();
	
		if ( $queried_object instanceof \WP_Term )
		{		
			return get_term_link( $queried_object );		
		}

		// 2️⃣ Inside post loop (Page / Loop Grid / Query Loop)
		$post_id = $this->get_editor_fallback_post_id( get_the_ID() );	
	
		if ( ! $post_id ) {
			return;
		}

		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );

		foreach ( $taxonomies as $taxonomy ) {

			$terms = get_the_terms( $post_id, $taxonomy );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				return get_term_link( $terms[0] );				
			}
		}
	}	
	
}
