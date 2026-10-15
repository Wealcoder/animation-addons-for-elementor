<?php
/**
 * Author Email Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Author;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Author Email Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Author
 */
class Author_Email_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-author-email';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Author Email', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-author' );
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
	 * Register controls for format.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'format',
			array(
				'label'   => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'plain' => esc_html__( 'Plain Email', 'animation-addons-for-elementor' ),
					'link'  => esc_html__( 'mailto: Link', 'animation-addons-for-elementor' ),
				),
				'default' => 'plain',
			)
		);
	}

	/**
	 * Get value - respects format setting.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}

		$email  = get_the_author_meta( 'user_email', $author_id );
		$format = $this->get_settings( 'format' );

		if ( empty( $email ) ) {
			return '';
		}

		$email = sanitize_email( $email );

		// Return mailto: only if format is 'link'
		if ( 'link' === $format ) {
			return 'mailto:' . $email;
		}

		return $email;
	}

	/**
	 * Render email - respects format setting.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( $this->get_value() );
	}
}
