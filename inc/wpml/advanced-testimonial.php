<?php

/**
 * Testimonial integration for WPML
 */

namespace WCF_ADDONS\INC\WPML\WIDGET;

defined('ABSPATH') || die();

class Advanced_Testimonial extends \WPML_Elementor_Module_With_Items
{

	/**
	 * Repeater field name in widget settings
	 *
	 * @return string
	 */
	public function get_items_field()
	{
		return 'testimonials';
	}

	/**
	 * Fields inside each repeater item
	 *
	 * @return array
	 */
	public function get_fields()
	{
		return [
			'tsm_content',
			'tsm_reason',
			'tsm_name',
			'tsm_role',
		];
	}

	/**
	 * Human-readable labels shown in WPML String Translation
	 *
	 * @param string $field
	 * @return string
	 */
	protected function get_title($field)
	{
		switch ($field) {
			case 'tsm_content':
				return __( 'Advanced Testimonial: Feedback', 'animation-addons-for-elementor' );

			case 'tsm_reason':
				return __( 'Advanced Testimonial: Reason', 'animation-addons-for-elementor' );

			case 'tsm_name':
				return __( 'Advanced Testimonial: Client Name', 'animation-addons-for-elementor' );

			case 'tsm_role':
				return __( 'Advanced Testimonial: Client Designation', 'animation-addons-for-elementor' );

			default:
				return '';
		}
	}

	/**
	 * Editor type for WPML
	 *
	 * @param string $field
	 * @return string
	 */
	protected function get_editor_type($field)
	{
		switch ($field) {
			case 'tsm_content':
				return 'AREA';

			case 'tsm_reason':
			case 'tsm_name':
			case 'tsm_role':
				return 'LINE';

			default:
				return '';
		}
	}
}
