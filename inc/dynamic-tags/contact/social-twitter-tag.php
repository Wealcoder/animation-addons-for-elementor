<?php
/**
 * Social Twitter/X Dynamic Tag
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
 * Social Twitter/X Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Social_Twitter_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-social-twitter';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Social Twitter/X', 'animation-addons-for-elementor' );
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
	 * Register control for Twitter username.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'twitter_username',
			array(
				'label'       => esc_html__( 'Twitter/X Username', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'username',
				'description' => esc_html__( 'Enter username without @', 'animation-addons-for-elementor' ),
			)
		);
	}

	/**
	 * Build profile URL for Twitter/X.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$username = $this->get_settings( 'twitter_username' );

		if ( empty( $username ) ) {
			return '';
		}

		// Remove @ if provided.
		$username = ltrim( $username, '@' );

		// Build Twitter/X URL.
		return 'https://twitter.com/' . sanitize_text_field( $username );
	}

	/**
	 * Render plain Twitter username for TEXT fields.
	 *
	 * @return void
	 */
	public function render() {
		$username = $this->get_settings( 'twitter_username' );

		if ( ! empty( $username ) ) {
			// Remove @ if provided.
			$username = ltrim( $username, '@' );
			// For TEXT fields, output username with @
			echo esc_html( '@' . sanitize_text_field( $username ) );
		}
	}
}
