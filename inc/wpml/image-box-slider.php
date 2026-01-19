<?php
/**
 * Image Box Slider Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Image_Box_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'image_box_slider';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'title',
			'subtitle',
			'description',
			'btn_text',
		];
	}

	/**
	 * Field label shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'title':
				return __( 'Image Box Slider: Title', 'animation-addons-for-elementor' );

			case 'subtitle':
				return __( 'Image Box Slider: Sub Title', 'animation-addons-for-elementor' );

			case 'description':
				return __( 'Image Box Slider: Description', 'animation-addons-for-elementor' );

			case 'btn_text':
				return __( 'Image Box Slider: Button Text', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor field type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'description':
				return 'AREA';

			default:
				return 'LINE';
		}
	}
}
