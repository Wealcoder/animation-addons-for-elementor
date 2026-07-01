<?php
/**
 * Content Slider Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Content_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'content_slider';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'slide_content',
		];
	}

	/**
	 * Field label in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'slide_content':
				return __( 'Content Slider: Description', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'slide_content':
				return 'VISUAL';

			default:
				return 'LINE';
		}
	}
}
