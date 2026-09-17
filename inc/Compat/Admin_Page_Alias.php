<?php
/**
 * The pre-4.2 admin page slugs, kept answering after the rename.
 *
 * `?page=wcf_addons_settings` is in bookmarks, in support articles, in the
 * shipped Pro (its licence screen redirects to it and its two submenu pages
 * register under `aaeaddon_page`), and in every theme that links to the
 * dashboard. A renamed slug that simply stopped resolving would answer all of
 * them with "Sorry, you are not allowed to access this page" -- which reads as
 * a permissions problem, not a moved page.
 *
 * Two mechanisms, both WordPress's own:
 *
 *   1. A request for an OLD slug is redirected (301) to the new one with the
 *      rest of the query string intact, on `admin_menu` at priority 0.
 *
 *      NOT `admin_init`, which is where it shipped first (2026-09-17) and
 *      where it never fired: wp-admin/admin.php requires wp-admin/menu.php
 *      (line 163) BEFORE `do_action( 'admin_init' )` (line 180), and
 *      wp-admin/includes/menu.php ends in the `user_can_access_admin_page()`
 *      check that `wp_die()`s 403 for a `?page=` nobody registered. The old
 *      slug is exactly such a page, so every request for it died before the
 *      redirect had its turn -- measured over real HTTP, while a suite that
 *      fired `admin_init` by hand had reported it working. `admin_menu` is
 *      fired at the TOP of includes/menu.php, before the check.
 *
 *   2. `$_wp_real_parent_file[ old ] = new`, the alias table core keeps for
 *      `edit.php?post_type=post` -> `edit.php`. `add_submenu_page()` and
 *      `get_admin_page_parent()` both consult it, so a submenu an older Pro
 *      registers under the OLD parent lands under the real menu, with the
 *      hookname computed from the real parent -- its callback still fires and
 *      the menu still highlights.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons\Compat;

defined( 'ABSPATH' ) || die();

final class Admin_Page_Alias {

	/** old slug => new slug. Every slug the plugin registered before 4.2. */
	const SLUGS = array(
		'wcf_addons_page'       => 'aaeaddon_page',
		'wcf_addons_settings'   => 'aaeaddon_settings',
		'wcf_addons_setup_page' => 'aaeaddon_setup_page',
		'aae-page-importer'     => 'aaeaddon-page-importer',
		'wcf-cpt-builder'       => 'aaeaddon-cpt-builder',
		'wcf-code-snippet'      => 'aaeaddon-code-snippet',
	);

	public static function init() {
		// Both at priority 0, alias first: the parent alias must be in place
		// before any plugin's add_submenu_page(), and the redirect must run
		// before includes/menu.php's access check (see the header).
		add_action( 'admin_menu', array( __CLASS__, 'alias_parent_slug' ), 0 );
		add_action( 'admin_menu', array( __CLASS__, 'redirect_old_slug' ), 0 );
	}

	/**
	 * Resolve a slug a caller may spell either way. Public for Pro and tests.
	 *
	 * @param string $slug Old or new spelling.
	 * @return string The current spelling.
	 */
	public static function current( $slug ) {
		return isset( self::SLUGS[ $slug ] ) ? self::SLUGS[ $slug ] : $slug;
	}

	/**
	 * `admin.php?page=<old>` -> `admin.php?page=<new>`, query string kept.
	 */
	public static function redirect_old_slug() {
		global $pagenow;

		if ( 'admin.php' !== $pagenow || wp_doing_ajax() || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing, nothing is written.
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( self::SLUGS[ $page ] ) ) {
			return;
		}

		$args         = array_map( 'sanitize_text_field', wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['page'] = self::SLUGS[ $page ];

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ), 301 );
		exit;
	}

	/**
	 * Let a submenu registered under the OLD parent slug attach to the new
	 * top-level menu. Priority 0 on admin_menu, so it is in place before any
	 * plugin's add_submenu_page() runs.
	 */
	public static function alias_parent_slug() {
		global $_wp_real_parent_file;

		if ( ! is_array( $_wp_real_parent_file ) ) {
			$_wp_real_parent_file = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- core's alias table, extended not replaced.
		}
		$_wp_real_parent_file['wcf_addons_page'] = 'aaeaddon_page';
	}
}
