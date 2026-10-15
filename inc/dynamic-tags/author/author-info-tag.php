<?php
/**
 * Author Info Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Author;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Data_Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Author Info Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Author
 */
class Author_Info_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-author-info';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Author Info', 'animation-addons-for-elementor' );
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
	 * Register controls for selecting author info field.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'key',
			array(
				'label'   => esc_html__( 'Field', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'description',
				'options' => $this->get_key_options(),
			)
		);

		$this->add_control(
			'format',
			array(
				'label'     => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'plain' => esc_html__( 'Plain Text', 'animation-addons-for-elementor' ),
					'link'  => esc_html__( 'Link Format', 'animation-addons-for-elementor' ),
				),
				'default'   => 'plain',
				'condition' => array(
					'key' => array( 'email', 'url' ),
				),
			)
		);
	}

	/**
	 * Build select options for author info fields.
	 *
	 * @return array<string,string>
	 */
	private function get_key_options() {
		$options                = array();
		$options['description'] = esc_html__( 'Bio', 'animation-addons-for-elementor' );

		if ( current_user_can( 'manage_options' ) || $this->is_post_authored_by_admin() ) {
			$options['email'] = esc_html__( 'Email', 'animation-addons-for-elementor' );
		}

		$options['url']        = esc_html__( 'Website', 'animation-addons-for-elementor' );
		$options['first_name'] = esc_html__( 'First Name', 'animation-addons-for-elementor' );
		$options['last_name']  = esc_html__( 'Last Name', 'animation-addons-for-elementor' );
		$options['nickname']   = esc_html__( 'Nickname', 'animation-addons-for-elementor' );

		return $options;
	}

	/**
	 * Get value - respects format setting.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$key    = $this->get_settings( 'key' );
		$format = $this->get_settings( 'format' );

		if ( empty( $key ) ) {
			return '';
		}

		// Security check for sensitive fields.
		$restricted_fields = array( 'user_email', 'email' );
		if ( ! $this->is_field_allowed( $key, $restricted_fields ) ) {
			return '';
		}

		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}

		$value = get_the_author_meta( $key, $author_id );

		if ( empty( $value ) ) {
			return '';
		}

		// Format based on field type and format setting.
		if ( 'email' === $key ) {
			// Return mailto: only if format is 'link'
			if ( 'link' === $format ) {
				return 'mailto:' . sanitize_email( $value );
			}
			return sanitize_email( $value );
		} elseif ( 'url' === $key ) {
			// URLs are the same in both formats
			return esc_url( $value );
		}

		// For other fields, return plain value.
		return $value;
	}

	/**
	 * Render the selected author info field.
	 *
	 * @return void
	 */
	public function render() {
		$value = $this->get_value();
		
		if ( empty( $value ) ) {
			return;
		}

		$key = $this->get_settings( 'key' );

		// Output with appropriate escaping
		if ( 'email' === $key || 'url' === $key ) {
			echo esc_html( $value );
		} else {
			echo wp_kses_post( $value );
		}
	}
}
