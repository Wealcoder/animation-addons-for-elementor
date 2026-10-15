<?php
/**
 * Contact Skype Dynamic Tag
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
 * Contact Skype Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Contact
 */
class Contact_Skype_Tag extends Data_Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-contact-skype';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Contact Skype', 'animation-addons-for-elementor' );
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
	 * Register controls for Skype username and action.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'skype_username',
			array(
				'label'       => esc_html__( 'Skype Username', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'username',
			)
		);

		$this->add_control(
			'skype_action',
			array(
				'label'   => esc_html__( 'Action', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'call'     => esc_html__( 'Call', 'animation-addons-for-elementor' ),
					'chat'     => esc_html__( 'Chat', 'animation-addons-for-elementor' ),
					'userinfo' => esc_html__( 'View Profile', 'animation-addons-for-elementor' ),
					'add'      => esc_html__( 'Add Contact', 'animation-addons-for-elementor' ),
				),
				'default' => 'chat',
			)
		);
	}

	/**
	 * Build Skype action URL.
	 *
	 * @param array $options Options (unused).
	 * @return string
	 */
	protected function get_value( array $options = array() ) {
		$username = $this->get_settings( 'skype_username' );
		$action   = $this->get_settings( 'skype_action' );

		if ( empty( $username ) ) {
			return '';
		}

		// Build Skype URL.
		return 'skype:' . sanitize_text_field( $username ) . '?' . $action;
	}

	/**
	 * Render plain Skype username for TEXT fields.
	 *
	 * @return void
	 */
	public function render() {
		$username = $this->get_settings( 'skype_username' );

		if ( ! empty( $username ) ) {
			// For TEXT fields, output plain username WITHOUT skype: protocol
			echo esc_html( sanitize_text_field( $username ) );
		}
	}
}
