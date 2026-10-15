<?php
namespace Wealcoder\AnimationAddons\DynamicTags\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Core\Settings\Manager as Settings_Manager;

/**
 * Custom Post ID utilities for Dynamic Tags
 *
 * Provides helpers to resolve the effective context ID for Elementor
 * dynamic tags, including template fallbacks for the custom
 * `wcf-addons-template` post type and archive handling.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */
/**
 * Trait: CustomPostIdTrait
 *
 * Resolves context identifiers for posts and taxonomies when dynamic tags
 * are rendered inside template posts or archive contexts.
 */
trait CustomPostIdTrait {

	/**
	 * Target taxonomy slug used for archive contexts.
	 *
	 * @var string|null
	 */
	public $tax = null;

	/**
	 * Get custom post-type taxonomies.
	 *
	 * Returns an associative array of taxonomy slug => label for the
	 * detected post-type in archive template contexts.
	 *
	 * @param int|null $post_id Optional. Post ID to evaluate. Defaults to current post.
	 * @return array<string,string> Map of taxonomy slug to human label.
	 */
	protected function get_cpt_taxonomies( $post_id = null ) {

		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$options       = array();
		$post_type     = null;
		$meta_type     = get_post_meta( $post_id, 'wcf-addons-template-meta_type', true );
		$meta_location = get_post_meta( $post_id, 'wcf-addons-template-meta_location', true );

		if ( 'archive' === $meta_type && ! empty( $meta_location ) ) {
			$exclude = explode( '-archive', $meta_location );
			if ( isset( $exclude[0] ) ) {
				$post_type = $exclude[0];
			} else {
				return $options;
			}
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy_slug => $taxonomy_object ) {
				$options[ $taxonomy_slug ] = $taxonomy_object->label;
			}
		}

		return $options;
	}

	/**
	 * Get custom post-ID with template fallback support.
	 *
	 * Handles special logic for the `wcf-addons-template` post type,
	 * including single and archive template support. In taxonomy archive
	 * contexts, it may return a term object or an encoded taxonomy-term key.
	 *
	 * @param int|null $post_id Optional. Post ID to evaluate. Defaults to current post.
	 * @return int|object|string Post ID, term object, or taxonomy-term key string.
	 */
	public function get_custom_id( $post_id = null ) {
			
		// Use the current post ID if none is provided.
		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}
		
		// Fallback logic for 'wcf-addons-template' post type.
		if ( get_post_type( $post_id ) === 'wcf-addons-template' ) {

			$args = array(
				'numberposts' => 1,
				'post_type'   => 'post',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			);

			$meta_type     = get_post_meta( $post_id, 'wcf-addons-template-meta_type', true );
			$meta_location = get_post_meta( $post_id, 'wcf-addons-template-meta_location', true );

			if ( 'single' === $meta_type && ! empty( $meta_location ) ) {
				$explode = explode( '-sing', $meta_location );
				if ( isset( $explode[0] ) ) {
					if ( '' !== $explode[0] ) {
						$args['post_type'] = $explode[0];
						$latest_posts      = get_posts( $args );
						if ( ! is_wp_error( $latest_posts ) && ! empty( $latest_posts ) && isset( $latest_posts[0] ) ) {
							$post_id = $latest_posts[0]->ID;
						}
					}
				} elseif ( 'singulars' === $meta_location ) {
					$latest_posts = get_posts( $args );
					// Update $post_id if a valid post is found.
					if ( ! is_wp_error( $latest_posts ) && ! empty( $latest_posts ) && isset( $latest_posts[0] ) ) {
						$post_id = $latest_posts[0]->ID;
					}
				}
			} elseif ( 'archive' === $meta_type && ! empty( $meta_location ) && '' !== $this->tax ) {

				$tax_args = array(
					'taxonomy'   => 'category',
					'orderby'    => 'id', // Order by term ID (creation order).
					'order'      => 'DESC', // Get the latest one.
					'number'     => 1, // Limit to 1 result.
					'hide_empty' => false, // Include terms without posts.
				);

				if ( preg_match( '/^(.*?)-/', $meta_location, $matches ) ) {

					if ( ! empty( $matches[1] ) ) {
						$tax_args['taxonomy'] = $this->tax;
						$taxonomy_term        = get_terms( $tax_args );

						if ( ! is_wp_error( $taxonomy_term ) && ! empty( $taxonomy_term ) ) {
							$post_id = $taxonomy_term[0];
						}
					}
				} elseif ( 'archives' === $meta_location ) {

					$tax_args['taxonomy'] = 'category';
					$taxonomy_term        = get_terms( $tax_args );
					if ( ! is_wp_error( $taxonomy_term ) && ! empty( $latest_taxonomy_term ) ) {
						$post_id = $taxonomy_term[0];
					}
				}
			}
		}
		
		
		$post_id = $this->get_editor_fallback_post_id( $post_id );	

		return $post_id;
	}

	public function get_editor_fallback_post_id($post_id = null) {
		// The atomic canvas renders elements in the PREVIEW iframe and via editor
		// ajax/REST sub-requests where is_edit_mode() is false — but is_preview_mode()
		// (or an active edit/render ajax action) is true. Gate on any of these so the
		// sample-post fallback below also applies to the canvas render, not just the
		// initial editor page-load config. Without this, post-context dynamic tags
		// (Post Terms / Date) render empty in the canvas even though the initial
		// config resolved them.
		$plugin       = \Elementor\Plugin::instance();
		$is_edit      = $plugin->editor->is_edit_mode();
		$is_preview   = isset( $plugin->preview ) && $plugin->preview->is_preview_mode();
		$is_edit_ajax = wp_doing_ajax() && isset( $_REQUEST['action'] ) && 0 === strpos( (string) $_REQUEST['action'], 'elementor' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $is_edit || $is_preview || $is_edit_ajax ) {
			$page_id = get_queried_object_id() ? get_queried_object_id() : $post_id;
			$manager = Settings_Manager:: get_settings_managers( 'page' );
			$model   = $manager->get_model( $page_id );

			$resolved = false;

			if ( $model ) {
				$aae_loop_post   = $model->get_settings( 'aae_loop_page_post' );
				if($aae_loop_post > 0){
					$post_id  = $aae_loop_post;
					$resolved = true;
				}

			}

			// No explicit Preview Settings post: the current context is the edited
			// PAGE itself (e.g. a Page with no categories/terms), so post-context
			// dynamic tags (Post Terms / Date / etc.) resolve to nothing and the
			// widget shows its placeholder. Fall back to the Loop Grid's own sample
			// post — a real published post (with a thumbnail) — so the editor preview
			// shows real values, matching the frontend loop. Only used as a LAST
			// resort and only when the free plugin's helper is available.
			if ( ! $resolved && is_callable( array( '\Wealcoder\AnimationAddons\AtomicWidgets\Atomic', 'get_sample_post' ) ) ) {
				$sample = \Wealcoder\AnimationAddons\AtomicWidgets\Atomic::get_sample_post();
				if ( $sample instanceof \WP_Post ) {
					$post_id = $sample->ID;
				}
			}
		}

		return $post_id;
	}
}
