<?php
/**
 * Social Facebook Dynamic Tag
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
 * Social Facebook Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Social_Facebook_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-social-facebook';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Social Facebook', 'animation-addons-for-elementor' );
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
	 * Register control for Facebook URL.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'facebook_url',
			array(
				'label'       => esc_html__( 'Facebook Page/Profile URL', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'https://facebook.com/yourpage',
			)
		);
	}

	/**
	 * Get sanitized Facebook URL.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$url = $this->get_settings( 'facebook_url' );

		if ( empty( $url ) ) {
			return '';
		}

		return esc_url( $url );
	}

	/**
	 * Render Facebook URL for TEXT fields.
	 *
	 * @return void
	 */
	public function render() {
		$url = $this->get_settings( 'facebook_url' );

		if ( ! empty( $url ) ) {
			// For TEXT fields, output the URL as-is
			echo esc_url( $url );
		}
	}
}
