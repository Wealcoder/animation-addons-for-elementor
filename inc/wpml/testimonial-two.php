<?php
/**
 * Testimonial Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Testimonial_Two extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater field name
	 */
	public function get_items_field() {
		return 'testimonials';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'testimonial_name',
			'testimonial_job',
			'testimonial_content',
		];
	}

	/**
	 * Field labels shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'testimonial_name':
				return __( 'Classic Testimonial: Name', 'animation-addons-for-elementor' );

			case 'testimonial_job':
				return __( 'Classic Testimonial: Designation', 'animation-addons-for-elementor' );

			case 'testimonial_content':
				return __( 'Classic Testimonial: Content', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'testimonial_content':
				return 'AREA';

			default:
				return 'LINE';
		}
	}
}
