<?php
/**
 * AAE Forms — backend bootstrap.
 *
 * The non-widget side of the AAE Atomic Form Builder (identity, schema
 * sync; later: submissions, REST, action queue, admin). Widgets live in
 * inc/AtomicWidgets/Widgets/Form/ — this namespace must keep working even
 * if the editor layer breaks (spec principle #1).
 *
 * Loaded from class-plugin.php next to \WCF_ADDONS\Atomic\Bootstrap::init().
 * Classes resolve via the composer PSR-4 map (WCF_ADDONS\ → inc/).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

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

		// Milestone 4 — identity: reconcile form_keys inside the data being
		// saved (create/duplicate/paste/import all funnel through here)...
		add_filter( 'elementor/document/save/data', [ Identity::class, 'filter_save_data' ], 10, 2 );

		// ...then version the canonical schema of every saved form.
		add_action( 'elementor/document/after_save', [ Sync::class, 'on_after_save' ], 10, 2 );

		// Milestone 5 — public submit runtime: REST routes + JS config.
		add_action( 'rest_api_init', [ Rest::class, 'register_routes' ] );
		Assets::init();

		// Milestone 7 — after-submit actions: enqueue jobs on submission_saved,
		// run attempt 1 on shutdown, retries via WP-Cron.
		Dispatcher::init();

		// Milestone 9 — admin data API + CSV export for the dashboard's
		// "Form Submissions" tab (React, inside the wcf_addons_settings app).
		Admin_Rest::init();
	}
}
