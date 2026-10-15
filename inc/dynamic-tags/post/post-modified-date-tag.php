<?php
/**
 * Post Modified Date Dynamic Tag
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
 * Post Modified Date Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Post
 */
class Post_Modified_Date_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-post-modified-date';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Modified Date', 'animation-addons-for-elementor' );
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
			'date_format',
			array(
				'label'   => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'default' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'F j, Y'  => date( 'F j, Y' ),
					'Y-m-d'   => date( 'Y-m-d' ),
					'm/d/Y'   => date( 'm/d/Y' ),
					'd/m/Y'   => date( 'd/m/Y' ),
					'custom'  => esc_html__( 'Custom', 'animation-addons-for-elementor' ),
				),
				'default' => 'default',
			)
		);

		$this->add_control(
			'custom_format',
			array(
				'label'     => esc_html__( 'Custom Format', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'F j, Y',
				'condition' => array(
					'date_format' => 'custom',
				),
			)
		);
	}

	/**
	 * Render modified date using selected format.
	 *
	 * @return void
	 */
	public function render() {
		$post_id = $this->get_post_id();
		$format  = $this->get_settings( 'date_format' );

		if ( 'default' === $format ) {
			$format = get_option( 'date_format' );
		} elseif ( 'custom' === $format ) {
			$format = $this->get_settings( 'custom_format' );
		}

		echo esc_html( get_the_modified_date( $format, $post_id ) );
	}
}
