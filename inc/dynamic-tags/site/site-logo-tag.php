<?php
/**
 * Site Logo Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Site;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Site Logo Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Site
 */
class Site_Logo_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-site-logo';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Site Logo', 'animation-addons-for-elementor' );
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
		return array( TagsModule::IMAGE_CATEGORY );
	}

	/**
	 * Register controls (fallback image).
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
	 * Get site logo image payload.
	 *
	 * @param array $options Options (unused).
	 * @return array{id:int|null,url:string}
	 */
	public function get_value( array $options = array() ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			$image_url = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $image_url ) {
				return array(
					'id'  => $custom_logo_id,
					'url' => $image_url[0],
				);
			}
		}

		// Return fallback if no logo.
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
