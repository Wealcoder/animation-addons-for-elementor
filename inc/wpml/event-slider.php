<?php
/**
 * Events Slider Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Event_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'events';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'event_name',
			'event_date',
			'event_desc',
			'event_link',
		];
	}

	/**
	 * Field labels shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'event_name':
				return __( 'Event Slider: Name', 'animation-addons-for-elementor' );

			case 'event_date':
				return __( 'Event Slider: Date', 'animation-addons-for-elementor' );

			case 'event_desc':
				return __( 'Event Slider: Description', 'animation-addons-for-elementor' );

			case 'event_link':
				return __( 'Event Slider: Link', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'event_desc':
				return 'AREA';

			case 'event_link':
				return 'LINK';

			default:
				return 'LINE';
		}
	}
}
