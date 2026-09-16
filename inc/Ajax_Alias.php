<?php
/**
 * Register an admin-ajax action under its new, prefixed name while still
 * answering the old one for a transition window.
 *
 * WordPress.org asks for a unique prefix on every global name, admin-ajax
 * actions included (2026-09 review). The old spellings cannot simply vanish:
 * a page cache or a JS-combine plugin keeps serving the previous bundle —
 * which posts the OLD action — until the site owner purges, and the Pro
 * plugin posts some of them too. So both names resolve to the same callback
 * and the old one is flagged deprecated under WP_DEBUG, the way
 * `Helpers::do_action()` still fires the `st-*` hooks it replaced.
 *
 * Remove an alias by deleting its `register()` line; nothing else references
 * the old name. Each call site carries a "remove after <version>" note.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons;

defined( 'ABSPATH' ) || die();

class Ajax_Alias {

	/**
	 * Hook `$callback` on `wp_ajax_{$new}` and, as a deprecated alias, on
	 * `wp_ajax_{$old}`; the `nopriv` twins too when `$nopriv` is true.
	 *
	 * @param string   $old_action The pre-4.2 action name a cached bundle may still post.
	 * @param string   $new_action The prefixed action name current code posts.
	 * @param callable $callback The handler both names run.
	 * @param bool     $nopriv   Also register the logged-out variants.
	 * @param string   $version  Free version that introduced the new name (for the deprecation notice).
	 */
	public static function register( $old_action, $new_action, $callback, $nopriv = false, $version = '4.2.0' ) {
		add_action( 'wp_ajax_' . $new_action, $callback );
		add_action( 'wp_ajax_' . $old_action, self::deprecated( $old_action, $new_action, $callback, $version ) );

		if ( $nopriv ) {
			add_action( 'wp_ajax_nopriv_' . $new_action, $callback );
			add_action( 'wp_ajax_nopriv_' . $old_action, self::deprecated( $old_action, $new_action, $callback, $version ) );
		}
	}

	/**
	 * Wrap a callback so the old name is logged under WP_DEBUG and then
	 * behaves exactly like the new one.
	 *
	 * Deliberately NOT `_deprecated_hook()`: that ends in `trigger_error()`,
	 * and a PHP notice printed into an admin-ajax response corrupts the JSON
	 * the caller is waiting for — on every dev site with WP_DEBUG_DISPLAY on.
	 * A log line plus an action a test can listen to carries the same signal
	 * without touching the response body.
	 *
	 * @param string   $old_action Old action name.
	 * @param string   $new_action New action name.
	 * @param callable $callback Real handler.
	 * @param string   $version  Version the old name was deprecated in.
	 * @return \Closure
	 */
	private static function deprecated( $old_action, $new_action, $callback, $version ) {
		return static function () use ( $old_action, $new_action, $callback, $version ) {
			do_action( 'aaeaddon/ajax_alias/deprecated_call', $old_action, $new_action, $version );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Animation Addons: admin-ajax action "%s" is deprecated since %s, use "%s".', $old_action, $version, $new_action ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			call_user_func_array( $callback, func_get_args() );
		};
	}
}
