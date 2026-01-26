<?php
/**
 * Pricing Table integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Pricing_Table extends \WPML_Elementor_Module_With_Items  {

	/**
	 * @return string
	 */
	public function get_items_field() {
		return 'features_list';
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return ['text'];
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'text':
				return __( 'Pricing Table: Feature Text', 'animation-addons-for-elementor' );
			default:
				return '';
		}
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'text':
				return 'AREA';
			default:
				return '';
		}
	}
}
