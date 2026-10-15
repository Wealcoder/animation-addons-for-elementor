<?php
/**
 * Contact Email Dynamic Tag
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
 * Contact Email Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Contact_Email_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-contact-email';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Contact Email', 'animation-addons-for-elementor' );
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
	 * Register controls for email source and custom email value.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'email_source',
			array(
				'label'   => esc_html__( 'Email Source', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'admin'  => esc_html__( 'Site Admin Email', 'animation-addons-for-elementor' ),
					'author' => esc_html__( 'Post Author Email', 'animation-addons-for-elementor' ),
					'custom' => esc_html__( 'Custom Email', 'animation-addons-for-elementor' ),
				),
				'default' => 'admin',
			)
		);

		$this->add_control(
			'custom_email',
			array(
				'label'     => esc_html__( 'Custom Email', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array(
					'email_source' => 'custom',
				),
			)
		);

		$this->add_control(
			'format',
			array(
				'label'   => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'plain' => esc_html__( 'Plain Email', 'animation-addons-for-elementor' ),
					'link'  => esc_html__( 'mailto: Link', 'animation-addons-for-elementor' ),
				),
				'default' => 'plain',
			)
		);
	}

	/**
	 * Get email address.
	 *
	 * @return string
	 */
	private function get_email() {
		$source = $this->get_settings( 'email_source' );
		$email  = '';

		switch ( $source ) {
			case 'admin':
				$email = get_option( 'admin_email' );
				break;
			case 'author':
				$email = get_the_author_meta( 'user_email' );
				break;
			case 'custom':
				$email = $this->get_settings( 'custom_email' );
				break;
		}

		return sanitize_email( $email );
	}

	/**
	 * Resolve email value - respects format setting.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$email  = $this->get_email();
		$format = $this->get_settings( 'format' );

		if ( empty( $email ) ) {
			return '';
		}

		// Return based on format setting.
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
