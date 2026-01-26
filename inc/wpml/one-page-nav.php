<?php
/**
 * One Page Nav Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class One_Page_Nav extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'wcf_one_page_nav';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'nav_text',
		];
	}

	/**
	 * Field label shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'nav_text':
				return __( 'One Page Nav: Text', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'nav_text':
				return 'LINE';

			default:
				return 'LINE';
		}
	}
}
