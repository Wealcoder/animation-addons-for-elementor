<?php
/**
 * Nested Slider – Repeater Items WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Nested_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater field name
	 */
	public function get_items_field() {
		return 'carousel_items';
	}

	/**
	 * Fields inside repeater
	 */
	public function get_fields() {
		return [
			'slide_title',
		];
	}

	/**
	 * Field label
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'slide_title':
				return __( 'Nested Slider: Slide Title', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * Editor type
	 */
	protected function get_editor_type( $field ) {
		return 'LINE';
	}
}
