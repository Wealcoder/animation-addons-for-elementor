<?php
/**
 * Contact WhatsApp Dynamic Tag
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
 * Contact WhatsApp Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Contact_WhatsApp_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-contact-whatsapp';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Contact WhatsApp', 'animation-addons-for-elementor' );
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
	 * Register controls for WhatsApp number and pre-filled message.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'whatsapp_number',
			array(
				'label'       => esc_html__( 'WhatsApp Number', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '+1234567890',
				'description' => esc_html__( 'Enter WhatsApp number with country code (no spaces or dashes)', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'pre_filled_message',
			array(
				'label'       => esc_html__( 'Pre-filled Message', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'placeholder' => esc_html__( 'Hello, I would like to...', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Optional: Message that will be pre-filled in WhatsApp', 'animation-addons-for-elementor' ),
			)
		);
	}

	/**
	 * Build WhatsApp URL with optional pre-filled message.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$number  = $this->get_settings( 'whatsapp_number' );
		$message = $this->get_settings( 'pre_filled_message' );

		if ( empty( $number ) ) {
			return '';
		}

		// Remove all non-numeric characters except +.
		$clean_number = preg_replace( '/[^0-9]/', '', $number );

		// Build WhatsApp URL.
		$url = 'https://wa.me/' . $clean_number;

		// Add pre-filled message if provided.
		if ( ! empty( $message ) ) {
			$url .= '?text=' . urlencode( $message );
		}

		return $url;
	}

	/**
	 * Render plain WhatsApp number for TEXT fields.
	 *
	 * @return void
	 */
	public function render() {
		$number = $this->get_settings( 'whatsapp_number' );

		if ( ! empty( $number ) ) {
			// For TEXT fields, output plain number WITHOUT URL
			echo esc_html( $number );
		}
	}
}
