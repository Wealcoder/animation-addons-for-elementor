<?php
/**
 * Comments Number Dynamic Tag
 *
 * Displays the number of comments for a post
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Comments;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Comments Number Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Comments
 */
class Comments_Number_Tag extends Tag_Base {
	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-comments-number';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Comments Number', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-comments' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY, TagsModule::NUMBER_CATEGORY );
	}

	/**
	 * Register controls for output format.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'format',
			array(
				'label'   => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'number',
				'options' => array(
					'number'    => esc_html__( 'Number', 'animation-addons-for-elementor' ),
					'formatted' => esc_html__( 'Formatted (K, M, B)', 'animation-addons-for-elementor' ),
				),
			)
		);
	}

	/**
	 * Render comments number (raw or formatted).
	 *
	 * @return void
	 */
	public function render() {
		$post_id = $this->get_post_id();
		$format  = $this->get_setting( 'format', 'number' );

		if ( ! $post_id ) {
			return;
		}

		$comments_number = get_comments_number( $post_id );

		if ( 'formatted' === $format ) {
			echo esc_html( $this->format_number( $comments_number ) );
		} else {
			echo esc_html( $comments_number );
		}
	}
}
