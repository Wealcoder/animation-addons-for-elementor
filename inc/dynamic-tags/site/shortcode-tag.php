<?php
/**
 * Shortcode Dynamic Tag
 *
 * Allows executing WordPress shortcodes as dynamic content.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Site;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shortcode Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Site
 */
class Shortcode_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-shortcode';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Shortcode', 'animation-addons-for-elementor' );
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
			TagsModule::NUMBER_CATEGORY,
			TagsModule::URL_CATEGORY,
			TagsModule::POST_META_CATEGORY,
			TagsModule::DATETIME_CATEGORY,
		);
	}

	/**
	 * Register tag controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'shortcode',
			array(
				'label' => esc_html__( 'Shortcode', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::TEXTAREA,
				'ai'    => array(
					'active' => false,
				),
			)
		);
	}

	/**
	 * Render shortcode output.
	 *
	 * @return void
	 */
	public function render() {
		$settings = $this->get_settings();

		if ( empty( $settings['shortcode'] ) ) {
			return;
		}

		$shortcode_string = $settings['shortcode'];
		$value            = do_shortcode( $shortcode_string );

		$should_escape = true;

		/**
		 * Should escape shortcodes.
		 *
		 * By default, shortcodes in dynamic tags are escaped. This hook allows developers
		 * to avoid shortcodes from being escaped. Defaults to true.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $should_escape Whether to escape shortcodes in dynamic tags.
		 */
		$should_escape = apply_filters( 'aaeaddon/dynamic_tags/shortcode_should_escape', $should_escape );

		// The spelling this filter shipped with in Pro. A site snippet may still hook it, and
		// it only ever switches escaping OFF, so honour it once more rather than silently
		// start escaping what the site owner chose not to.
		$should_escape = apply_filters( 'wcf_addons_pro_dynamic_tags_shortcode_should_escape', $should_escape ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- shipped hook name, kept for existing snippets.

		if ( $should_escape ) {
			echo wp_kses_post( $value );
			return;
		}

		aaeaddon_print_builder_html( $value );
	}
}
