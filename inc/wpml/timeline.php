<?php
/**
 * Timeline Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Timeline extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'timelines';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'step_text',
			'timeline_date',
			'timeline_title',
			'timeline_sub',
			'timeline_desc',
		];
	}

	/**
	 * Field label in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'step_text':
				return __( 'Timeline: Step Text', 'animation-addons-for-elementor' );

			case 'timeline_date':
				return __( 'Timeline: Date', 'animation-addons-for-elementor' );

			case 'timeline_title':
				return __( 'Timeline: Title', 'animation-addons-for-elementor' );

			case 'timeline_sub':
				return __( 'Timeline: Sub Title', 'animation-addons-for-elementor' );

			case 'timeline_desc':
				return __( 'Timeline: Content', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'timeline_desc':
				return 'AREA';

			default:
				return 'LINE';
		}
	}
}
