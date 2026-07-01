<?php
/**
 * Post Social Share Widget WPML integration
 */
namespace WCF_ADDONS\INC\WPML\WIDGET;

defined( 'ABSPATH' ) || die();

class Post_Social_Share extends \WPML_Elementor_Module_With_Items {

	/**
	 * Repeater control name
	 */
	public function get_items_field() {
		return 'list';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields() {
		return [
			'list_title',
		];
	}

	/**
	 * Field label shown in WPML editor
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'list_title':
				return __( 'Post Social Share: Icon Title', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'list_title':
				return 'LINE';

			default:
				return '';
		}
	}
}
