<?php
/**
 * AAE Forms — backend bootstrap.
 *
 * The non-widget side of the AAE Atomic Form Builder (identity, schema
 * sync; later: submissions, REST, action queue, admin). Widgets live in
 * inc/AtomicWidgets/Widgets/Form/ — this namespace must keep working even
 * if the editor layer breaks (spec principle #1).
 *
 * Loaded from class-plugin.php next to \Wealcoder\AnimationAddons\Atomic\Bootstrap::init().
 * Classes resolve via the composer PSR-4 map (Wealcoder\AnimationAddons\ → inc/).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace Wealcoder\AnimationAddons\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	public static function init(): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		// Version-gated table creation (cheap option read per request).
		add_action( 'init', [ Database::class, 'migrate' ], 5 );

		// Licence gate for the Pro fields and multi-step. Registered FIRST and
		// unconditionally: it must be listening before any form renders, and it
		// deliberately does not gate registration — see Pro_Gate's docblock on
		// why unregistering a Pro element type would delete customer content.
		( new Pro_Gate() )->register();

		// Milestone 4 — identity: reconcile form_keys inside the data being
		// saved (create/duplicate/paste/import all funnel through here)...
		add_filter( 'elementor/document/save/data', [ Identity::class, 'filter_save_data' ], 10, 2 );

		// ...then version the canonical schema of every saved form.
		add_action( 'elementor/document/after_save', [ Sync::class, 'on_after_save' ], 10, 2 );

		// Milestone 5 — public submit runtime: REST routes + JS config.
		add_action( 'rest_api_init', [ Rest::class, 'register_routes' ] );
		Assets::init();

		// File uploads — local private storage, pre-upload endpoint, claim on
		// submission_saved, daily orphan sweep, admin download proxy.
		Uploads::init();

		// Milestone 7 — after-submit actions: enqueue jobs on submission_saved,
		// run attempt 1 on shutdown, retries via WP-Cron.
		Dispatcher::init();

		// Milestone 9 — admin data API + CSV export for the dashboard's
		// "Form Submissions" tab (React, inside the wcf_addons_settings app).
		//
		// init() registers five things and four of them -- admin_post_aae_form_csv,
		// admin_enqueue_scripts, admin_menu, submenu_file -- cannot fire outside
		// wp-admin. The fifth is rest_api_init, so off-admin we register THAT and
		// nothing else: a class-string callable is not resolved until the hook
		// runs, so the 38 KB file is parsed on a REST request and on no other.
		// Calling register_routes() from inside rest_api_init at the default
		// priority is exactly what init() would have arranged.
		if ( is_admin() ) {
			Admin_Rest::init();
		} else {
			add_action( 'rest_api_init', [ Admin_Rest::class, 'register_routes' ] );
		}
	}
}
