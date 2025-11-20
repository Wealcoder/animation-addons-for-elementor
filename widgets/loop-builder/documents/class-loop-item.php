<?php
namespace WCF_ADDONS\Widgets\Loop_Builder\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Loop Item Document Class.
 *
 * Custom document type for loop item templates.
 */
class Loop_Item extends \Elementor\Core\Base\Document {

	/**
	 * Get document type.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public static function get_type() {
		return 'loop-item';
	}

	/**
	 * Get document title.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public static function get_title() {
		return __( 'Loop Item', 'animation-addons-for-elementor' );
	}

	/**
	 * Get document properties.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['admin_tab_group']           = 'theme';
		$properties['support_kit']               = true;
		$properties['support_site_editor']       = false;
		$properties['show_in_finder']            = true;
		$properties['show_on_admin_bar']         = false;
		$properties['has_elements']              = true;
		$properties['support_wp_page_templates'] = false;
		$properties['is_editable']               = true;

		return $properties;
	}

	/**
	 * Get document configuration for library.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public static function get_create_url() {
		return \Elementor\Plugin::$instance->documents->get_create_new_post_url( self::get_type() );
	}

	/**
	 * Get initial config.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	protected function get_initial_config() {
		$config = parent::get_initial_config();

		$config['support_site_editor']           = false;
		$config['container_attributes']['class'] = 'e-loop-item';

		return $config;
	}

	/**
	 * Get editor panel config.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public static function get_editor_panel_config() {
		$config = parent::get_editor_panel_config();

		$config['has_elements']                     = true;
		$config['messages']['publish_notification'] = __( 'Loop template saved successfully!', 'animation-addons-for-elementor' );

		return $config;
	}


	/**
	 * Get CSS wrapper selector.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_css_wrapper_selector() {
		return '.e-loop-item.elementor-' . $this->get_main_id();
	}

	/**
	 * Get container attributes.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_container_attributes() {
		$attributes = parent::get_container_attributes();

		$attributes['class']               = 'e-loop-item';
		$attributes['data-elementor-type'] = self::get_type();

		return $attributes;
	}

	/**
	 * Get preview URL.
	 *
	 * @param array $args Optional. Arguments to override the default.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_preview_url( array $args = array() ) {
		$preview_id = $this->get_settings( 'preview_id' );

		if ( empty( $preview_id ) ) {
			$source_type = get_post_meta( $this->get_main_id(), '_elementor_source', true );
			$source_type = $source_type ? $source_type : 'post';
			$sample_post = get_posts(
				array(
					'post_type'      => $source_type,
					'posts_per_page' => 1,
					'post_status'    => 'publish',
				)
			);

			if ( ! empty( $sample_post ) ) {
				$preview_id = $sample_post[0]->ID;
			}
		}

		$args = array_merge(
			array(
				'preview_id' => $preview_id,
			),
			$args
		);

		return parent::get_preview_url( $args );
	}
}
