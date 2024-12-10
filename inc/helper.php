<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Retrieves an array of the elementor save template.
 *
 * For more information on the accepted arguments, see the
 * {@link https://developer.wordpress.org/reference/classes/wp_query/
 * WP_Query} documentation in the Developer Handbook.
 *
 *
 * @param array $args
 *
 * @return WP_Post[]|int[] Array of post objects or post IDs.
 * @see WP_Query::parse_query()
 *
 * @since 1.0.0
 *
 * @see WP_Query
 */
if ( ! function_exists( 'wcf_addons_get_saved_template_list' ) ) :
	function wcf_addons_get_saved_template_list( $args = null ) {
	
		$post_list = [];		
		$user = wp_get_current_user();
		$allowed_roles = array( 'editor', 'administrator', 'author' );
		
		if ( array_intersect( $allowed_roles, $user->roles ) ) {
		
			$defaults = array(
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
				'numberposts' => - 1,
			);
	
			$parsed_args              = wp_parse_args( $args, $defaults );
			$parsed_args['post_type'] = 'elementor_library'; //don't overwrite post type
			$posts                    = get_posts( $parsed_args );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$post_list[ $post->ID ] = esc_html( $post->post_title );
				}
			}
		}

		return $post_list;
	}
endif;

/**
 * Get database settings of a widget by widget id and element
 *
 * @param array $elements
 * @param string $widget_id
 *
 * @return false|mixed|string
 */
if ( ! function_exists( 'wcf_addons_get_widget_element_settings' ) ) :
	function wcf_addons_get_widget_element_settings( $elements, $widget_id ) {

		if ( is_array( $elements ) ) {
			foreach ( $elements as $d ) {
				if ( $d && ! empty( $d['id'] ) && $d['id'] == $widget_id ) {
					return $d;
				}
				if ( $d && ! empty( $d['elements'] ) && is_array( $d['elements'] ) ) {
					$value = wcf_addons_get_widget_element_settings( $d['elements'], $widget_id );
					if ( $value ) {
						return $value;
					}
				}
			}
		}

		return false;
	}
endif;

/**
 * Get database settings of a widget by widget id and post id
 *
 * @param number $post_id
 * @param string $widget_id
 *
 * @return false|mixed|string
 */
if ( ! function_exists( 'wcf_addons_get_widget_settings' ) ) :
	function wcf_addons_get_widget_settings( $post_id, $widget_id ) {

		$elementor_data = @json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );

		if ( $elementor_data ) {
			$element = wcf_addons_get_widget_element_settings( $elementor_data, $widget_id );

			return isset( $element['settings'] ) ? $element['settings'] : '';
		}

		return false;
	}
endif;

/**
 * Get local plugin data
 *
 * @param string $basename
 *
 * @return false|mixed|string
 */
if ( ! function_exists( 'wcf_addons_get_local_plugin_data' ) ) :
	function wcf_addons_get_local_plugin_data( $basename = '' ) {
		if ( empty( $basename ) ) {
			return false;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		if ( ! isset( $plugins[ $basename ] ) ) {
			return false;
		}

		return $plugins[ $basename ];
	}
endif;

/**
 * Get all widgets count
 *
 * @return numeric
 */
if ( ! function_exists( 'wcf_addons_get_all_widgets_count' ) ) :
	function wcf_addons_get_all_widgets_count() {
		$total   = 0;
		$widgets = $GLOBALS['wcf_addons_config']['widgets'];
		foreach ( $widgets as $group ) {
			$total = $total + count( $group['elements'] );
		}

		return $total;
	}
endif;

/**
 * Get active widgets count
 *
 * @return numeric
 */
if ( ! function_exists( 'wcf_addons_get_active_widgets_count' ) ) :
	function wcf_addons_get_active_widgets_count() {

		return get_option( 'wcf_save_widgets' ) ? count( get_option( 'wcf_save_widgets' ) ) : 0;
	}
endif;

/**
 * Get inactive widgets count
 *
 * @return numeric
 */
if ( ! function_exists( 'wcf_addons_get_inactive_widgets_count' ) ) :
	function wcf_addons_get_inactive_widgets_count() {

		return wcf_addons_get_all_widgets_count() - wcf_addons_get_active_widgets_count();
	}
endif;

/**
 * Get all Extensions count
 *
 * @return numeric
 */
if ( ! function_exists( 'wcf_addons_get_all_extensions_count' ) ) :
	function wcf_addons_get_all_extensions_count() {
		$total   = 0;
		$widgets = $GLOBALS['wcf_addons_config']['extensions'];
		foreach ( $widgets as $group ) {
			$total = $total + count( $group['elements'] );
		}

		return $total;
	}
endif;

/**
 * Check the element status
 *
 *  @return false|mixed|numeric
 */
if ( ! function_exists( 'wcf_addons_element_status' ) ) :
	function wcf_addons_element_status($option_name, $key, $element = null ) {
		$status = checked( 1, wcf_addons_get_settings( $option_name, $key ), false );

		if ( ! is_null( $element ) ) {
			if ( $element['is_pro'] || $element['is_extension'] ) {

				//pro elements
				if ( $element['is_pro'] && ! defined( 'WCF_ADDONS_PRO_VERSION' ) ) {
					$status = 'disabled';
				}

				//extension elements
				if ( $element['is_extension'] && ! defined( 'WCF_ADDONS_EX_VERSION' ) ) {
					$status = 'disabled';
				}
			}
		}

		return $status;
	}
endif;

if ( ! function_exists( 'wcf_addons_get_settings' ) ) {

	/**
	 * Return saved settings
	 *
	 */
	function wcf_addons_get_settings( $option_name, $element = null ) {
		$elements = get_option( $option_name );

		return ( isset( $element ) ? ( isset( $elements[ $element ] ) ? $elements[ $element ] : 0 ) : array_keys( array_filter( $elements ) ) );
	}
}

if ( ! function_exists( 'wcf_set_postview' ) ) {

	/**
	 * save single post view count
	 *
	 */
	function wcf_set_postview( $template ) {
	
		$postID     = get_the_ID();
		$count_key  = 'wcf_post_views_count';
		$count      = get_post_meta( $postID, $count_key, true );

		if ( $count == '' ) {
			$count = 0;
			delete_post_meta( $postID, $count_key );
			add_post_meta( $postID, $count_key, '0' );
		} else {
			$count ++;
			update_post_meta( $postID, $count_key, $count );
		}

		return $template;
	}
}

if( !function_exists('wcf_get_nested_config_keys') ) {
	function wcf_get_nested_config_keys($array, &$foundKeys, &$active) {
		foreach ($array as $key => $value) {
			// Check if the current key is one we're looking for
			if (isset($value['is_active']) && $value['is_active'] == true) {
				// Add to found keys list
				$foundKeys[] = $key;
				// Store the entire element in $active
				$active[$key] = true;
			}
	
			// If value is an array, recurse into it
			if (is_array($value)) {
				wcf_get_nested_config_keys($value,$foundKeys, $active);
			}
		}
	}
}

if( !function_exists('wcf_get_db_updated_config') ) {	
	
	function wcf_get_db_updated_config(array &$configs, array $dbActiveElements) {
		// Loop through each item in the configs array
		foreach ($configs as $key => &$element) {
			
			// Check if the current element is an array and has an 'is_active' field
			if (is_array($element) && isset($element['is_active'])) {
				// If the current key is in the dbActiveElements array, update is_active to true
				if (in_array($key, $dbActiveElements)) {
					$element['is_active'] = true;
				}
			}
	
			// Recursively call the function for any nested elements
			if (is_array($element) ) {
				wcf_get_db_updated_config($element, $dbActiveElements);
			}
		}
	}
	
}


if( !function_exists('wcf_get_total_config_elements_by_key') ) {
	function wcf_get_total_config_elements_by_key($array, &$foundKeys=0) {
		foreach ($array as $key => $value) {
			// Check if the current key is one we're looking for
			if (isset($value['is_active']) && isset($value['is_extension']) && isset($value['is_pro'])) {				
				++$foundKeys;	
			}
	
			// If value is an array, recurse into it
			if (is_array($value)) {
				wcf_get_total_config_elements_by_key($value,$foundKeys);
			}
		}
	}
}


if( !function_exists('wcf_get_search_active_keys') ) {
	function wcf_get_search_active_keys($array, $keysToFind, &$foundKeys, &$active) {
		foreach ($array as $key => $value) {
			// Check if the current key is one we're looking for
			if (in_array($key, $keysToFind) && is_array($value) && array_key_exists('is_extension', $value)) {
				// Add to found keys list
				$foundKeys[] = $key;
				// Store the entire element in $active
				$value['is_active'] = 1;
				$active[$key] = $value;
			}
	
			// If value is an array, recurse into it
			if (is_array($value)) {
				wcf_get_search_active_keys($value, $keysToFind, $foundKeys, $active);
			}
		}
	}
}

if(!function_exists('wcf_get_addon_active_extension_by_key')) {
	
	function wcf_get_addon_active_extension_by_key($search){
	
		$ext = get_option( 'wcf_save_extensions' );
		if(is_array($ext)){
		
			$saved_ext  = array_keys( $ext );	
			$found_key = array_search($search, $saved_ext);			
			if($found_key !==false){
				return true;
			}
			
		}else{
			return true;
		}	
		
		return false;
	}	

}




