<?php
/**
 * Services Tab Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Services_Tab extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'tabs';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'tab_number',
			'tab_title',
			'tab_content',
		];
	}

	/**
	 * Field label in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {

			case 'tab_number':
				return __( 'Service Tab: Number', 'animation-addons-for-elementor' );

			case 'tab_title':
				return __( 'Service Tab: Title', 'animation-addons-for-elementor' );

			case 'tab_content':
				return __( 'Service Tab: Content', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {

			case 'tab_number':
				return 'NUMBER';

			case 'tab_content':
				return 'VISUAL';

			default:
				return 'LINE';
		}
	}
}
