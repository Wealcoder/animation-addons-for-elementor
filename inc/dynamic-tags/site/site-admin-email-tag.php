<?php
/**
 * Site Admin Email Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Site;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Site Admin Email Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Site
 */
class Site_Admin_Email_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-site-admin-email';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Site Admin Email', 'animation-addons-for-elementor' );
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
		return array(
			TagsModule::TEXT_CATEGORY,
			TagsModule::URL_CATEGORY,
		);
	}

	/**
	 * Get admin email (mailto for URL context).
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$email = get_option( 'admin_email' );

		if ( empty( $email ) ) {
			return '';
		}

		// For URL fields, ALWAYS return mailto: format.
		return 'mailto:' . sanitize_email( $email );
	}

	/**
	 * Render plain admin email for text contexts.
	 *
	 * @return void
	 */
	public function render() {
		// For TEXT fields, output plain email without mailto:.
		$email = get_option( 'admin_email' );

		if ( empty( $email ) ) {
			return;
		}

		echo esc_html( sanitize_email( $email ) );
	}
}
