<?php

namespace WCF_ADDONS\Admin\Base;

use WP_Error;
use XMLReader;
if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class AAEImporter extends WXRImporter {
		/**
	 * Constructor method.
	 *
	 * @param array $options Importer options.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		// Set current user to $mapping variable.
		// Fixes the [WARNING] Could not find the author for ... log warning messages.
		$current_user_obj = wp_get_current_user();
		$this->mapping['user_slug'][ $current_user_obj->user_login ] = $current_user_obj->ID;

		// WooCommerce product attributes registration.
		if ( class_exists( 'WooCommerce' ) ) {
			add_filter( 'wxr_importer.pre_process.term', array( $this, 'woocommerce_product_attributes_registration' ), 10, 1 );
		}
	}

	/**
	 * Get all protected variables from the WXR_Importer needed for continuing the import.
	 */
	public function get_importer_data() {
		return array(
			'mapping'            => $this->mapping,
			'requires_remapping' => $this->requires_remapping,
			'exists'             => $this->exists,
			'user_slug_override' => $this->user_slug_override,
			'url_remap'          => $this->url_remap,
			'featured_images'    => $this->featured_images,
		);
	}

	/**
	 * Sets all protected variables from the WXR_Importer needed for continuing the import.
	 *
	 * @param array $data with set variables.
	 */
	public function set_importer_data( $data ) {
		$this->mapping            = empty( $data['mapping'] ) ? array() : $data['mapping'];
		$this->requires_remapping = empty( $data['requires_remapping'] ) ? array() : $data['requires_remapping'];
		$this->exists             = empty( $data['exists'] ) ? array() : $data['exists'];
		$this->user_slug_override = empty( $data['user_slug_override'] ) ? array() : $data['user_slug_override'];
		$this->url_remap          = empty( $data['url_remap'] ) ? array() : $data['url_remap'];
		$this->featured_images    = empty( $data['featured_images'] ) ? array() : $data['featured_images'];
	}

	/**
	 * Hook into the pre-process term filter of the content import and register the
	 * custom WooCommerce product attributes, so that the terms can then be imported normally.	
	 *
	 * @param  array $date The term data to import.
	 * @return array       The unchanged term data.
	 */
	public function woocommerce_product_attributes_registration( $data ) {
		if ( strstr( $data['taxonomy'], 'pa_' ) ) {
			if ( ! taxonomy_exists( $data['taxonomy'] ) ) {
				$attribute_name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $data['taxonomy'] ) );
	
				// Get existing attributes
				$existing_attributes = wc_get_attribute_taxonomies();
				$existing_names = wp_list_pluck($existing_attributes, 'attribute_name');
	
				// Create the attribute if it doesn't exist
				if ( ! in_array( $attribute_name, $existing_names, true ) ) {
					$attribute_id = wc_create_attribute( array(
						'name'         => $attribute_name,
						'slug'         => $attribute_name,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					) );
	
					if ( ! is_wp_error( $attribute_id ) ) {
						delete_transient( 'wc_attribute_taxonomies' );
					}
				}
	
				// Register the taxonomy to ensure it works in imports
				register_taxonomy(
					$data['taxonomy'],
					apply_filters( 'woocommerce_taxonomy_objects_' . $data['taxonomy'], array( 'product' ) ),
					apply_filters( 'woocommerce_taxonomy_args_' . $data['taxonomy'], array(
						'hierarchical' => true,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					) )
				);
			}
		}
	
		return $data;
	}
	
}
