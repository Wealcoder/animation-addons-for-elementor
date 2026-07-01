<?php

namespace WCF_ADDONS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WCF_Extension_Widgets_Trait {

	/**
	 * Get Widgets List.
	 *
	 * @return array
	 */
	public static function get_widgets() {

		$config = wcf_get_config();

		$widgets       = get_option( 'wcf_save_widgets' );
		$saved_widgets = is_array( $widgets ) ? array_keys( $widgets ) : [];

		$awidgets  = [];
		$foundKeys = [];

		if ( ! empty( $config['widgets'] ) ) {
			wcf_get_search_active_keys( $config['widgets'], $saved_widgets, $foundKeys, $awidgets );
		}

		return is_array( $awidgets ) ? $awidgets : [];
	}

	/**
	 * Get Extension List.
	 *
	 * @return array
	 */
	public static function get_extensions() {

		$config = wcf_get_config();

		$extensions       = get_option( 'wcf_save_extensions' );
		$saved_extensions = is_array( $extensions ) ? array_keys( $extensions ) : [];

		$active    = [];
		$foundKeys = [];

		if ( ! empty( $config['extensions'] ) ) {
			wcf_get_search_active_keys( $config['extensions'], $saved_extensions, $foundKeys, $active );
		}

		return is_array( $active ) ? $active : [];
	}
}
