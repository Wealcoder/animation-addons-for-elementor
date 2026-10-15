<?php
/**
 * Contact Phone Dynamic Tag
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
 * Contact Phone Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Contact_Phone_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-contact-phone';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Contact Phone', 'animation-addons-for-elementor' );
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
	 * Register controls for phone number.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'phone_number',
			array(
				'label'       => esc_html__( 'Phone Number', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '+1234567890',
				'description' => esc_html__( 'Enter phone number with country code (e.g., +1234567890)', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'format',
			array(
				'label'   => esc_html__( 'Format', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'plain' => esc_html__( 'Plain Number', 'animation-addons-for-elementor' ),
					'link'  => esc_html__( 'tel: Link', 'animation-addons-for-elementor' ),
				),
				'default' => 'plain',
			)
		);
	}

	/**
	 * Get phone number - respects format setting.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$phone  = $this->get_settings( 'phone_number' );
		$format = $this->get_settings( 'format' );

		if ( empty( $phone ) ) {
			return '';
		}

		// Return tel: only if format is 'link'
		if ( 'link' === $format ) {
			$clean_phone = preg_replace( '/[^0-9+]/', '', $phone );
			return 'tel:' . $clean_phone;
		}

		return $phone;
	}

	/**
	 * Render phone number - respects format setting.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( $this->get_value() );
	}
}
