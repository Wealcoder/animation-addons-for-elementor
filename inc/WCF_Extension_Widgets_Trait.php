<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

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

		$widgets = get_option( 'wcf_save_widgets' );

		return self::active_from_config( 'widgets', is_array( $widgets ) ? array_keys( $widgets ) : [] );
	}

	/**
	 * Pick the saved keys out of a config section.
	 *
	 * Replaces a full recursive walk of the ~2,125-node config tree per call
	 * (see wcf_config_index()). Iteration is over the INDEX, not the saved list,
	 * so the returned order stays config-traversal order exactly as before —
	 * registration order is observable, so it is not safe to reorder.
	 *
	 * @param string   $section 'widgets' or 'extensions'.
	 * @param string[] $saved   Saved keys from the option.
	 * @return array<string,array>
	 */
	private static function active_from_config( $section, array $saved ) {

		if ( empty( $saved ) ) {
			return [];
		}

		$wanted = array_flip( $saved );
		$active = [];

		foreach ( wcf_config_index( $section ) as $key => $node ) {
			if ( isset( $wanted[ $key ] ) ) {
				$node['is_active'] = 1;
				$active[ $key ]    = $node;
			}
		}

		return $active;
	}

	/**
	 * Get Extension List.
	 *
	 * @return array
	 */
	public static function get_extensions() {

		$extensions = get_option( 'wcf_save_extensions' );

		return self::active_from_config( 'extensions', is_array( $extensions ) ? array_keys( $extensions ) : [] );
	}
}
