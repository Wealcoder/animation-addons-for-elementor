<?php
/**
 * Filterable Slider – Projects WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Filterable_Slider_Projects extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater field name
	 */
	public function get_items_field() {
		return 'project_items';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'project_item_filter_name',
			'title',
			'sub_title',
			'description',
			'link',
		];
	}

	/**
	 * Labels in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {

			case 'project_item_filter_name':
				return __( 'Filtarable Slider: Filter Name', 'animation-addons-for-elementor' );

			case 'title':
				return __( 'Filtarable Slider: Title', 'animation-addons-for-elementor' );

			case 'sub_title':
				return __( 'Filtarable Slider: Sub Title', 'animation-addons-for-elementor' );

			case 'description':
				return __( 'Filtarable Slider: Description', 'animation-addons-for-elementor' );

			case 'link':
				return __( 'Filtarable Slider: Link', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * Editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'description':
				return 'AREA';

			case 'link':
				return 'LINK';

			default:
				return 'LINE';
		}
	}
}
