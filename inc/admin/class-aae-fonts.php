<?php
/**
 * The webfonts this plugin's admin screens use, served from this plugin.
 *
 * @package WCF_ADDONS
 */

namespace WCF_ADDONS;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the bundled Inter / Figtree / DM Sans faces as one stylesheet.
 *
 * These used to be fetched from fonts.googleapis.com by a CSS `@import` sitting
 * at the top of six different admin stylesheets. That was wrong twice over: it
 * made every admin page load contact a third party (with no disclosure and no
 * way for a site owner to stop it), and an `@import` INSIDE a stylesheet is
 * invisible to `wp_dequeue_style()` and to every filter around it, so it could
 * not be turned off even deliberately. The faces are bundled in assets/fonts/
 * now and this is an ordinary registered stylesheet, so it can be dequeued,
 * filtered and versioned like any other.
 *
 * Callers add HANDLE to their own stylesheet's dependency array, so the fonts
 * load on exactly the screens that ask for them and nowhere else.
 */
class AAE_Fonts {

	const HANDLE = 'aae-fonts';

	/**
	 * Register the stylesheet if it is not registered yet, and return its handle.
	 *
	 * Deliberately lazy rather than hooked: a dependency must already be
	 * REGISTERED when its dependent is enqueued, and WordPress does not warn
	 * when it is not -- it silently declines to print the dependent's chain.
	 * Calling this from the enqueue site itself means there is no hook order to
	 * get wrong.
	 *
	 * @return string The style handle, for use in a dependency array.
	 */
	public static function ensure(): string {
		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style(
				self::HANDLE,
				WCF_ADDONS_URL . 'assets/fonts/aae-fonts.css',
				array(),
				WCF_ADDONS_VERSION
			);
		}

		return self::HANDLE;
	}
}
