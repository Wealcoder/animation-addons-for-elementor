<?php
/**
 * Typewriter Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Typewriter extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'typewriter_animated_text';
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
	 * Field label in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'list_text':
				return __( 'Typewriter: Animated Text', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		return 'LINE';
	}
}
