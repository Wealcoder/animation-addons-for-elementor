<?php
/**
 * Internal URL Dynamic Tag
 *
 * Creates links to internal WordPress content (posts, taxonomies, attachments, authors).
 * Uses custom SELECT2 control with AJAX autocomplete (no Elementor Pro dependency).
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Site;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Wealcoder\AnimationAddons\DynamicTags\Controls\Select2_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Internal URL Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Site
 */
class Internal_URL_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-internal-url';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Internal URL', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-site' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( TagsModule::URL_CATEGORY );
	}

	/**
	 * Get panel template.
	 *
	 * @return string
	 */
	public function get_panel_template() {
		return ' ({{ url }})';
	}

	/**
	 * Present the four ID pickers to the Atomic (v4) editor as native `query`
	 * autocomplete controls.
	 *
	 * The v3 stack keeps its `aae_select2` controls — the custom control, its
	 * Backbone template and the `aae_dt_get_*` AJAX endpoints are unchanged, and
	 * the v3 panel still receives them downgraded to `text` exactly as before.
	 * The `object` slugs are the same ones Elementor Pro's own Internal URL tag
	 * uses, which is what `Dynamic_Tags_Editor_Config` matches on when it picks a
	 * REST endpoint (post / term / attachment / user).
	 *
	 * @return array<string,array>
	 */
	protected function get_atomic_query_controls() {
		return array(
			'post_id'       => array(
				'object'  => 'post',
				'display' => 'detailed',
				'query'   => array(
					'post_type' => 'any',
				),
			),
			'taxonomy_id'   => array(
				'object'  => 'tax',
				'display' => 'detailed',
			),
			'attachment_id' => array(
				'object'  => 'attachment',
				'display' => 'detailed',
			),
			'author_id'     => array(
				'object'  => 'author',
				'display' => 'detailed',
			),
		);
	}

	/**
	 * Get internal URL based on type.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$settings = $this->get_settings();
		$type     = isset( $settings['type'] ) ? $settings['type'] : '';
		$url      = '';

		if ( 'post' === $type && ! empty( $settings['post_id'] ) ) {
			$url = get_permalink( (int) $settings['post_id'] );
		} elseif ( 'taxonomy' === $type && ! empty( $settings['taxonomy_id'] ) ) {
			$url = get_term_link( (int) $settings['taxonomy_id'] );
		} elseif ( 'attachment' === $type && ! empty( $settings['attachment_id'] ) ) {
			$url = get_attachment_link( (int) $settings['attachment_id'] );
		} elseif ( 'author' === $type && ! empty( $settings['author_id'] ) ) {
			$url = get_author_posts_url( (int) $settings['author_id'] );
		}

		if ( ! is_wp_error( $url ) ) {
			return esc_url( $url );
		}

		return '';
	}

	/**
	 * Register tag controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => array(
					'post'       => esc_html__( 'Content', 'animation-addons-for-elementor' ),
					'taxonomy'   => esc_html__( 'Taxonomy', 'animation-addons-for-elementor' ),
					'attachment' => esc_html__( 'Media', 'animation-addons-for-elementor' ),
					'author'     => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				),
			)
		);

		// Post selector with AJAX autocomplete.
		$this->add_control(
			'post_id',
			array(
				'label'       => esc_html__( 'Search & Select', 'animation-addons-for-elementor' ),
				'type'        => Select2_Control::CONTROL_ID,
				'label_block' => true,
				'placeholder' => esc_html__( 'Type to search...', 'animation-addons-for-elementor' ),
				'ajax'        => array(
					'action' => 'aaeaddon_dt_get_posts',
					'params' => array(
						'post_type' => 'any',
					),
				),
				'condition'   => array(
					'type' => 'post',
				),
			)
		);

		// Taxonomy selector with AJAX autocomplete.
		$this->add_control(
			'taxonomy_id',
			array(
				'label'       => esc_html__( 'Search & Select', 'animation-addons-for-elementor' ),
				'type'        => Select2_Control::CONTROL_ID,
				'label_block' => true,
				'placeholder' => esc_html__( 'Type to search...', 'animation-addons-for-elementor' ),
				'ajax'        => array(
					'action' => 'aaeaddon_dt_get_terms',
					'params' => array(
						'taxonomy' => 'category',
					),
				),
				'condition'   => array(
					'type' => 'taxonomy',
				),
			)
		);

		// Attachment selector with AJAX autocomplete.
		$this->add_control(
			'attachment_id',
			array(
				'label'       => esc_html__( 'Search & Select', 'animation-addons-for-elementor' ),
				'type'        => Select2_Control::CONTROL_ID,
				'label_block' => true,
				'placeholder' => esc_html__( 'Type to search...', 'animation-addons-for-elementor' ),
				'ajax'        => array(
					'action' => 'aaeaddon_dt_get_attachments',
				),
				'condition'   => array(
					'type' => 'attachment',
				),
			)
		);

		// Author selector with AJAX autocomplete.
		$this->add_control(
			'author_id',
			array(
				'label'       => esc_html__( 'Search & Select', 'animation-addons-for-elementor' ),
				'type'        => Select2_Control::CONTROL_ID,
				'label_block' => true,
				'placeholder' => esc_html__( 'Type to search...', 'animation-addons-for-elementor' ),
				'ajax'        => array(
					'action' => 'aaeaddon_dt_get_authors',
				),
				'condition'   => array(
					'type' => 'author',
				),
			)
		);
	}
}
