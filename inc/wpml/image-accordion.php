<?php

/**
 * Accordion Widget WPML integration
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\INC\WPML\WIDGET;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

defined('ABSPATH') || die();

class Image_Accordion extends \WPML_Elementor_Module_With_Items
{

	/**
	 * Repeater control name
	 */
	public function get_items_field()
	{
		return 'accordions';
	}

	/**
	 * Translatable fields inside repeater
	 */
	public function get_fields()
	{
		return [
			'title',
			'subtitle',
			'description',
			'details_link',
			'btn_text',
		];
	}

	/**
	 * Field label in WPML editor
	 */
	protected function get_title($field)
	{
		switch ($field) {
			case 'title':
				return __('Image Accordion: Title', 'animation-addons-for-elementor');

			case 'subtitle':
				return __('Image Accordion: Sub Title', 'animation-addons-for-elementor');

			case 'description':
				return __('Image Accordion: Description', 'animation-addons-for-elementor');

			case 'details_link':
				return __('Image Accordion: Link', 'animation-addons-for-elementor');

			case 'btn_text':
				return __('Image Accordion: Button', 'animation-addons-for-elementor');

			default:
				return '';
		}
	}

	/**
	 * WPML editor type
	 */
	protected function get_editor_type($field)
	{
		switch ($field) {
			case 'description':
				return 'AREA';

			case 'details_link':
				return 'LINK';

			default:
				return 'LINE';
		}
	}
}
