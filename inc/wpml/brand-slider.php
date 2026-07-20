<?php
/**
 * Brand Slider Widget WPML integration
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\INC\WPML\WIDGET;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

defined( 'ABSPATH' ) || die();

class Brand_Slider extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'repeat_list_text';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'list_text',
		];
	}

	/**
	 * Field label shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'list_text':
				return __( 'Brand Slider: Text', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'list_text':
				return 'LINE';

			default:
				return '';
		}
	}
}
