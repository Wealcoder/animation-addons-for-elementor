<?php
/**
 * Advance Accordion Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Advance_Accordion extends \WPML_Elementor_Module_With_Items {

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
			'tab_count',
			'tab_title',
			'tab_content',
		];
	}

	/**
	 * Field label in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {

			case 'tab_count':
				return __( 'Accordion: Tab Count', 'animation-addons-for-elementor' );

			case 'tab_title':
				return __( 'Accordion: Title', 'animation-addons-for-elementor' );

			case 'tab_content':
				return __( 'Accordion: Content', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {

			case 'tab_count':
				return 'NUMBER';

			case 'tab_content':
				return 'VISUAL';

			default:
				return 'LINE';
		}
	}
}
