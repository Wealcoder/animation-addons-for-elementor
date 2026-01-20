<?php
/**
 * Image Hotspot Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Image_Hotspot extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'hsp_list';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'hsp_text',
			'tlp_content',
		];
	}

	/**
	 * Field label shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'hsp_text':
				return __( 'Image Hotspot: Title', 'animation-addons-for-elementor' );

			case 'tlp_content':
				return __( 'Image Hotspot: Content', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor field type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'tlp_content':
				return 'VISUAL'; // correct for WYSIWYG / tooltip content

			default:
				return 'LINE';
		}
	}
}
