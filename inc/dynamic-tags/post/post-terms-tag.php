<?php
/**
 * Post Terms Dynamic Tag
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
 * Post-Terms Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Terms_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-terms';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Terms', 'animation-addons-for-elementor' );
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
			'taxonomy',
			array(
				'label'       => esc_html__( 'Taxonomy', 'animation-addons-for-elementor' ),
				// Plain SELECT (not SELECT2): Elementor inlines a SELECT's options into
				// get_editor_config(), which the Atomic (V4) dynamic-tag converter needs
				// to build a populated dropdown. SELECT2 is treated as ajax-fed, so its
				// options are NOT inlined — under Atomic the control then degrades to an
				// empty text field (the "nothing shows" bug). Custom taxonomies still
				// appear because get_taxonomies() below includes them.
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'category',
				'options'     => $this->get_taxonomies(),
			)
		);

		$this->add_control(
			'taxonomy_show_count',
			array(
				'label'   => esc_html__( 'Terms to Show', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'step'    => 1,
				'default' => 1,
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => ', ',
			)
		);

		$this->add_control(
			'link',
			array(
				'label'        => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);
	}

	/**
	 * Build taxonomy options for selector.
	 *
	 * Includes CUSTOM taxonomies: we list anything that is either public OR
	 * merely UI-visible (`show_ui`). A custom taxonomy registered with
	 * `public => false, show_ui => true` (common for CPT-builder / WooCommerce
	 * taxonomies) would otherwise be missing from this dropdown even though its
	 * terms resolve fine on the frontend. `post_format` is dropped unless the
	 * active theme supports post formats.
	 *
	 * @return array<string,string> Map of taxonomy => label
	 */
	private function get_taxonomies() {
		$public  = get_taxonomies( array( 'public' => true ), 'objects' );
		$show_ui = get_taxonomies( array( 'show_ui' => true ), 'objects' );

		// Union — keyed by slug, so duplicates collapse.
		$taxonomies = $public + $show_ui;

		if ( isset( $taxonomies['post_format'] ) && ! current_theme_supports( 'post-formats' ) ) {
			unset( $taxonomies['post_format'] );
		}

		$options = array();
		foreach ( $taxonomies as $taxonomy ) {
			$options[ $taxonomy->name ] = $this->build_taxonomy_label( $taxonomy );
		}

		return $options;
	}

	/**
	 * Build a disambiguating label for a taxonomy option.
	 *
	 * Several taxonomies can share the SAME label — e.g. a "Categories" for Posts,
	 * a WooCommerce Product "Categories", and a CPT's own "Categories" all show up
	 * as three identical "Categories" rows, so the user can't tell which is which.
	 * We suffix the post type(s) the taxonomy is attached to (by their singular
	 * labels), e.g. "Categories (Posts)" / "Categories (Products)". The raw
	 * taxonomy slug is appended too when even the post-type-qualified label would
	 * still collide (two custom taxonomies on the same post type). Post-type-less
	 * taxonomies fall back to the bare label.
	 *
	 * @param \WP_Taxonomy $taxonomy Taxonomy object.
	 * @return string
	 */
	private function build_taxonomy_label( $taxonomy ) {
		$label = $taxonomy->label;

		$post_type_labels = array();
		foreach ( (array) $taxonomy->object_type as $object_type ) {
			$pt_object = get_post_type_object( $object_type );
			if ( $pt_object && ! empty( $pt_object->labels->singular_name ) ) {
				$post_type_labels[] = $pt_object->labels->singular_name;
			} elseif ( $pt_object && ! empty( $pt_object->label ) ) {
				$post_type_labels[] = $pt_object->label;
			}
		}

		if ( empty( $post_type_labels ) ) {
			return $label;
		}

		// De-dupe + keep order, then join a handful (cap so the dropdown stays readable).
		$post_type_labels = array_values( array_unique( $post_type_labels ) );
		$shown            = array_slice( $post_type_labels, 0, 3 );
		$suffix           = implode( ', ', $shown );
		if ( count( $post_type_labels ) > count( $shown ) ) {
			$suffix .= '…';
		}

		return sprintf( '%1$s (%2$s)', $label, $suffix );
	}

	/**
	 * Render taxonomy terms with optional links.
	 *
	 * @return void
	 */
	public function render() {
		$post_id  = $this->get_post_id();
		$settings = $this->get_settings();

		$taxonomy   = $settings['taxonomy'];
		$separator  = $settings['separator'];
		$show_terms = $settings['taxonomy_show_count'];

		// The Link switcher stores 'yes' under the V3 editor but a real boolean
		// `true` under Atomic (V4) — the atomic converter maps switchers to a
		// Boolean prop. Accept both so links render in either editor.
		$link_enabled = ( 'yes' === $settings['link'] ) || ( true === $settings['link'] );

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return;
		}

		if ( $show_terms > 0 ) {
			$terms = array_slice( $terms, 0, $show_terms );
		}

		$terms_array = array();

		foreach ( $terms as $term ) {
			if ( $link_enabled ) {
				$terms_array[] = sprintf( '<a href="%s">%s</a>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
			} else {
				$terms_array[] = esc_html( $term->name );
			}
		}

		echo wp_kses_post( implode( $separator, $terms_array ) );
	}
}
