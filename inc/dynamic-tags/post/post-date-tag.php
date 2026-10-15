<?php
/**
 * Post Date Dynamic Tag
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
 * Post-Date Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Date_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-date';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Date', 'animation-addons-for-elementor' );
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
		return array(
			TagsModule::TEXT_CATEGORY,
			TagsModule::POST_META_CATEGORY,
		);
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'date_type',
			array(
				'label'   => esc_html__( 'Date Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'published',
				'options' => array(
					'published' => esc_html__( 'Published Date', 'animation-addons-for-elementor' ),
					'modified'  => esc_html__( 'Modified Date', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'date_format',
			array(
				'label'   => esc_html__( 'Date Format', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'F j, Y'  => esc_html__( 'January 1, 2024', 'animation-addons-for-elementor' ),
					'Y-m-d'   => esc_html__( '2024-01-01', 'animation-addons-for-elementor' ),
					'M j, Y'  => esc_html__( 'Jan 1, 2024', 'animation-addons-for-elementor' ),
					'd/m/Y'   => esc_html__( '01/01/2024', 'animation-addons-for-elementor' ),
					'm/d/Y'   => esc_html__( '01/01/2024 (US)', 'animation-addons-for-elementor' ),
					'custom'  => esc_html__( 'Custom', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'custom_date_format',
			array(
				'label'     => esc_html__( 'Custom Format', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'F j, Y',
				'condition' => array(
					'date_format' => 'custom',
				),
			)
		);

		$this->add_control(
			'show_time_ago',
			array(
				'label'        => esc_html__( 'Human Readable', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);
	}

	/**
	 * Render formatted date.
	 *
	 * @return void
	 */
	public function render() {
		$post_id   = $this->get_post_id();
		$settings  = $this->get_settings();
		$date_type = $settings['date_type'];

		if ( 'yes' === $settings['show_time_ago'] ) {
			if ( 'published' === $date_type ) {
				$time = get_the_time( 'U', $post_id );
			} else {
				$time = get_the_modified_time( 'U', $post_id );
			}

			echo esc_html(
				sprintf(
					// translators: %s: time difference.
					_x( '%s ago', '%s = time difference', 'animation-addons-for-elementor' ),
					human_time_diff( $time, current_time( 'timestamp' ) )
				)
			);
			return;
		}

		$format = 'default' !== $settings['date_format'] ? $settings['date_format'] : get_option( 'date_format' );

		if ( 'custom' === $settings['date_format'] ) {
			$format = $settings['custom_date_format'];
		}

		if ( 'published' === $date_type ) {
			echo esc_html( get_the_date( $format, $post_id ) );
		} else {
			echo esc_html( get_the_modified_date( $format, $post_id ) );
		}
	}
}
