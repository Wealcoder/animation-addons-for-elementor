<?php
/**
 * Team Slider Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Team_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'team_slides';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'title',
			'desc',
			'team_link',
		];
	}

	/**
	 * Field labels shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'title':
				return __( 'Team Slider: Name', 'animation-addons-for-elementor' );

			case 'desc':
				return __( 'Team Slider: Description', 'animation-addons-for-elementor' );

			case 'team_link':
				return __( 'Team Slider: Link', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'desc':
				return 'AREA';

			case 'team_link':
				return 'LINK';

			default:
				return 'LINE';
		}
	}
}
