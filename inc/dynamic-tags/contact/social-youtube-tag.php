<?php
/**
 * Social YouTube Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Contact;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Social YouTube Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Social_YouTube_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-social-youtube';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Social YouTube', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-contact' );
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
	 * Register control for YouTube URL.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'youtube_url',
			array(
				'label'       => esc_html__( 'YouTube Channel URL', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'https://youtube.com/@yourchannel',
			)
		);
	}

	/**
	 * Get sanitized YouTube URL.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$url = $this->get_settings( 'youtube_url' );

		if ( empty( $url ) ) {
			return '';
		}

		return esc_url( $url );
	}

	/**
	 * Render YouTube URL for TEXT fields.
	 *
	 * @return void
	 */
	public function render() {
		$url = $this->get_settings( 'youtube_url' );

		if ( ! empty( $url ) ) {
			// For TEXT fields, output the URL as-is
			echo esc_url( $url );
		}
	}
}
