<?php
/**
 * Filterable Slider – Filters WPML integration
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\INC\WPML\WIDGET;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

defined( 'ABSPATH' ) || die();

class Filterable_Slider_Filters extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater field name
	 */
	public function get_items_field() {
		return 'filter_items';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'filter_title',
		];
	}

	/**
	 * Labels in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'filter_title':
				return __( 'Filterable Slider: Filter Name', 'animation-addons-for-elementor' );
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
