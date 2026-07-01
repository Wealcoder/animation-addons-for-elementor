<?php
/**
 * Slider integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Slider extends \WPML_Elementor_Module_With_Items  {

	/**
	 * @return string
	 */
	public function get_items_field() {
		return 'slides';
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return [
			'title',
			'subtitle',
			'link' => ['url']
		];
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'title':
				return __( 'Slider: Title', 'animation-addons-for-elementor' );
			case 'subtitle':
				return __( 'Slider: Subtitle', 'animation-addons-for-elementor' );
			case 'url':
				return __( 'Slider: Link', 'animation-addons-for-elementor' );
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
			case 'title':
				return 'LINE';
			case 'subtitle':
				return 'AREA';
			case 'url':
				return 'LINK';
			default:
				return '';
		}
	}
}
