<?php

namespace WCF_ADDONS\CodeSnippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait CodeSnippetSettingsTrait
 *
 * Provides helper method to get snippet settings by snippet ID.
 *
 * @package WCF_ADDONS\CodeSnippet
 */
trait CodeSnippetSettingsTrait {
	/**
	 * Add code snippet data settings.
	 *
	 * @param int $id Post ID.
	 *
	 * @since 2.3.10
	 * @return array
	 */
	public function aae_get_code_snippet_settings( $id = null ) {
		$defaults = array(
			'code_type'            => '',
			'load_location'        => '',
			'code_content'         => '',
			'is_active'            => 'no',
			'priority'             => '10',
			'visibility_page'      => '',
			'visibility_page_list' => array(),
		);

		/**
		 * Filter the default code snippet settings.
		 *
		 * @since 2.3.10
		 *
		 * @param array $defaults The default settings.
		 */
		$defaults = apply_filters( 'wcf_code_snippet_default_settings', $defaults );

		$settings = array();
		if ( ! empty( $id ) ) {
			$metadata                  = get_post_meta( $id );
			$settings['snippet_id']    = $id;
			$settings['snippet_title'] = get_the_title( $id );

			foreach ( $metadata as $key => $value ) {
				$value = maybe_unserialize( is_array( $value ) ? $value[0] : $value );
				if ( ! empty( $value ) ) {
					$settings[ $key ] = $value;
				} else {
					$settings[ $key ] = $defaults[ $key ];
				}
			}
		} else {
			foreach ( $defaults as $key => $value ) {
				$settings[ $key ] = $value;
			}
		}

		/**
		 * Filter the code snippet settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings The code snippet settings.
		 * @param array $defaults The default settings.
		 */
		return apply_filters( 'wcf_code_snippet_settings', $settings, $defaults );
	}
}
