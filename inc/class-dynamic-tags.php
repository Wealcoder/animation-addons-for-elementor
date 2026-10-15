<?php
/**
 * Dynamic Tags extension (free).
 *
 * Elementor dynamic tags for Post, Author, Site, Archive, Taxonomy, Comments
 * and Contact & Social data. Used to ship in the Pro plugin only; the tag
 * NAMES (`aae-post-title`, `aae-site-logo`, ...), groups and categories are
 * unchanged, so content already saved with them keeps rendering.
 *
 * The Pro plugin still carries its own copy of this module for installs where
 * Pro is newer than free. Pro checks for `Dynamic_Tags_Module` below and stays
 * out of the way when it exists, so the two never register the same tag twice.
 * ACF, Post Meta, Core Stats Count and the AAE Custom Fields tags remain Pro.
 *
 * Loaded from `Plugin::register_extensions()` -- by the v3 extension list
 * (`dynamic-tags` slug) or by the v4 Atomic Extensions toggle of the same name.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons;

defined( 'ABSPATH' ) || die();

class Aaeaddon_Dynamic_Tags {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Loads the module, which hooks its own groups, tags and controls.
		require_once AAEADDON_PATH . 'inc/dynamic-tags/dynamic-tags-module.php';

		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_groups' ), 20 );

		// 'wcf_get_terms_by_taxonomy' is a deprecated alias (a page cache or combined bundle can still post the old name) -- remove in 4.5.
		Ajax_Alias::register( 'wcf_get_terms_by_taxonomy', 'aaeaddon_get_terms_by_taxonomy', array( $this, 'get_terms_by_taxonomy' ), false, '4.3.0' );
	}

	/**
	 * Register the two top-level groups the Post tags (and Pro's ACF tags) use.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 * @return void
	 */
	public function register_groups( $dynamic_tags_manager ) {
		$dynamic_tags_manager->register_group(
			'aae',
			array(
				'title' => esc_html__( 'AAE', 'animation-addons-for-elementor' ),
			)
		);

		$dynamic_tags_manager->register_group(
			'aae-posts',
			array(
				'title' => esc_html__( 'AAE Posts', 'animation-addons-for-elementor' ),
			)
		);
	}

	/**
	 * Terms of one taxonomy, for the editor's term picker.
	 *
	 * Needs the editor nonce and the capability the editor itself requires:
	 * without them it would list every term of ANY taxonomy, private ones
	 * included, to anyone.
	 *
	 * @return void
	 */
	public function get_terms_by_taxonomy() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::EDITOR ) ) ) {
			wp_send_json_error( __( 'Security check failed', 'animation-addons-for-elementor' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'animation-addons-for-elementor' ) );
		}

		if ( ! isset( $_POST['taxonomy'] ) ) {
			wp_send_json_error( __( 'Invalid Taxonomy', 'animation-addons-for-elementor' ) );
		}

		$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			wp_send_json_success( array() );
		}

		$term_options = array();
		foreach ( $terms as $term ) {
			$term_options[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
			);
		}

		wp_send_json_success( $term_options );
	}
}

Aaeaddon_Dynamic_Tags::instance();
