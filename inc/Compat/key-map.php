<?php
/**
 * The key map — every persisted name this plugin (and the paid add-on) owns,
 * and what it is called from 4.2.0 on.
 *
 * THIS FILE IS THE ONLY PLACE THE PRE-4.2 SPELLINGS MAY APPEAR IN SOURCE.
 * The Plugins Team's "generic function/class/define/namespace/option names"
 * review asks for a ≥4-character prefix on option names; `wcf_` and `aae_`
 * are three. Every option is therefore renamed to `aaeaddon_…` — in the CODE.
 * In the DATABASE the old row is never moved and never deleted:
 *
 *   - `Key_Bridge` answers BOTH spellings at runtime (`pre_option_*` and
 *     friends), in whichever direction the site's migration state says, so
 *     an older Animation Addons Pro that still reads `wcf_save_widgets`
 *     sees exactly what the new code sees.
 *   - `Migration` copies old → new only when an administrator presses
 *     "Start migration" on the Migration screen; before that the OLD rows
 *     stay the only copy and the new code is redirected to them.
 *   - After the copy the old row is kept as a write-through mirror, so
 *     downgrading the plugin at any time is a full rollback.
 *
 * `map_version` is bumped when a pair is ADDED; the runner copies only what
 * the stored state has not seen. Never remove a pair — a site that skipped a
 * release still needs it.
 *
 * Read by `Wealcoder\AnimationAddons\Compat\Key_Bridge`, by the uninstall
 * hook, by `aaeaddon_key_map()` (the paid add-on reads it through that
 * function) and by `E:\Local Testing\verify-key-bridge.php`, which fails when
 * a literal `get_option( 'wcf_…' | 'aae_…' )` exists in either plugin outside
 * this file — the map is only a source of truth while it is complete.
 *
 * Inventory frozen 2026-09-16 against the 4.1.0 baseline (free HEAD
 * d31a1f3a + the working tree; Pro working tree e5ea9863, which IS the
 * released customer build).
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.0
 */

defined( 'ABSPATH' ) || exit;

return array(
	'map_version' => 1,

	/*
	 * wp_options owned by the FREE plugin. old => new.
	 * `*` in the comment = the paid add-on also reads or writes the OLD name,
	 * so the bridge is mandatory for that row whatever the migration state.
	 */
	'options'     => array(
		'wcf_save_widgets'                        => 'aaeaddon_save_widgets',            // * absent ≠ empty (maybe_enable_used_v3_widgets bails on a written row)
		'wcf_save_extensions'                     => 'aaeaddon_save_extensions',         // *
		'wcf_save_gsap_library'                   => 'aaeaddon_save_gsap_library',       // * written by Pro's dashboard through free's save endpoint
		'wcf_smooth_scroller'                     => 'aaeaddon_smooth_scroller',         // *
		'wcf_addons_setup_wizard'                 => 'aaeaddon_setup_wizard',            // 'complete' gates every settings screen
		'wcf_addons_version'                      => 'aaeaddon_version',                 // the upgrade runner's own marker
		'wcf_widget_dashboardv2'                  => 'aaeaddon_widget_dashboardv2',
		'wcf_extension_dashboardv2'               => 'aaeaddon_extension_dashboardv2',   // *
		'wcf_notice_data'                         => 'aaeaddon_notice_data',
		'wcf_custom_font_setting'                 => 'aaeaddon_custom_font_setting',
		'wcf_code_snippet_rewrite_rules_flushed'  => 'aaeaddon_code_snippet_rewrite_rules_flushed',
		'wcf_templates_library'                   => 'aaeaddon_templates_library',
		'wcf_addons_wizard_subscribed'            => 'aaeaddon_wizard_subscribed',       // written by a removed feature; listed so uninstall clears it
		'aae_installed'                           => 'aaeaddon_installed',
		'aae_animation_settings'                  => 'aaeaddon_animation_settings',      // holds legacy_v3 / legacy_v3_user_set
		'aae_atomic_widgets'                      => 'aaeaddon_atomic_widgets',          // * absent ≠ empty; Pro's Performance wizard writes it
		'aae_atomic_extensions'                   => 'aaeaddon_atomic_extensions',       // * absent ≠ empty
		'aae_atomic_extensions_offered'           => 'aaeaddon_atomic_extensions_offered',
		'aae_atomic_offered_migration'            => 'aaeaddon_atomic_offered_migration',
		'aae_atomic_optin'                        => 'aaeaddon_atomic_optin',
		'aae_atomic_optin_undo'                   => 'aaeaddon_atomic_optin_undo',       // absent ≠ empty
		'aae_atomic_widgets_forced_backfill'      => 'aaeaddon_atomic_widgets_forced_backfill',
		'aae_atomic_v3_admin_backfill'            => 'aaeaddon_atomic_v3_admin_backfill',
		'aae_mailchimp_api'                       => 'aaeaddon_mailchimp_api',           // *
		'aae_addon_mailchimp_form_field'          => 'aaeaddon_mailchimp_form_field',    // *
		'aae_weather_api_advanced_settings'       => 'aaeaddon_weather_api_settings',    // * twin widget in Pro reads it
		'aae_sc_error_status_current_support'     => 'aaeaddon_sc_error_status_current_support', // *
		'aae_gl_load'                             => 'aaeaddon_gl_load',                 // the OPTION; the post-meta key of the same name is kept
		'aae_cpts_032153'                         => 'aaeaddon_cpts_cache',              // CPT-builder registry cache, rebuilt from posts
		'aae_taxs_933153'                         => 'aaeaddon_taxs_cache',
		'aae_loop_grid_settings'                  => 'aaeaddon_loop_grid_settings',
		'aae_loop_grid_known_taxonomies'          => 'aaeaddon_loop_grid_known_taxonomies', // add-only evidence — copy, never reset
		'aae_pp_cache_versions'                   => 'aaeaddon_pp_cache_versions',       // non-autoload
		'aae_preset_manifest_cache'               => 'aaeaddon_preset_manifest_cache',   // non-autoload
		'aae_forms_db_version'                    => 'aaeaddon_forms_db_version',        // drives Forms\Database::migrate()
		'aae_forms_delete_data_on_uninstall'      => 'aaeaddon_forms_delete_data_on_uninstall',
		'aae_form_integration_keys'               => 'aaeaddon_form_integration_keys',
		'aae_form_recaptcha_keys'                 => 'aaeaddon_form_recaptcha_keys',
		'aae_last_import_batch'                   => 'aaeaddon_last_import_batch',
		'aae_import_attachment_map'               => 'aaeaddon_import_attachment_map',
		'aae_import_atomic_posts'                 => 'aaeaddon_import_atomic_posts',
		'aae_import_image_localize'               => 'aaeaddon_import_image_localize',
		'aae_v4_import_v3_off'                    => 'aaeaddon_v4_import_v3_off',        // the V3 RESTORE record — must survive
	),

	/*
	 * wp_options owned by the PAID ADD-ON. Bridged from here so a Pro update
	 * in either order is safe; the Pro code keeps reading the old spelling
	 * (Pro is not subject to the WordPress.org rule and is updated by hand),
	 * and can move to the new one in a later release with no further
	 * migration. Excludes the three licence keys — see `keep`.
	 */
	'options_pro' => array(
		'wcf_addon_version'                       => 'aaeaddon_pro_version',
		'aae_do_activation_redirect_pro'          => 'aaeaddon_pro_activation_redirect',
		'aae_performance_settings'                => 'aaeaddon_performance_settings',
		'aae_performance_wizard'                  => 'aaeaddon_performance_wizard',
		'aae_anim_builder_settings'               => 'aaeaddon_anim_builder_settings',
		'aae_v3_kit_chrome_backup'                => 'aaeaddon_v3_kit_chrome_backup',    // a BACKUP of the customer's Kit chrome
		'aae_v3_widgets_backup'                   => 'aaeaddon_v3_widgets_backup',      // the Performance wizard's reversible-toggle backups
		'aae_v3_extensions_backup'                => 'aaeaddon_v3_extensions_backup',
		'aae_v4_popup_chrome_migrated'            => 'aaeaddon_v4_popup_chrome_migrated',
		'aae_v4_popup_anim_migrated'              => 'aaeaddon_v4_popup_anim_migrated',
		'aae_speed_preview_secret'                => 'aaeaddon_speed_preview_secret',
		'aae_addon_remote_status'                 => 'aaeaddon_remote_status',
		'aae_addon_remote_request_status'         => 'aaeaddon_remote_request_status',
		'aae_addon_remote_request_store_status'   => 'aaeaddon_remote_request_store_status',
		'aaewcf_addon_error_invalid_counter'      => 'aaeaddon_remote_error_invalid_counter',
		'aae_tiktok_api_advanced_settings'        => 'aaeaddon_tiktok_api_settings',
		'aae_youtube_video_advanced_settings'     => 'aaeaddon_youtube_video_settings',
		'aae_yt_api_key'                          => 'aaeaddon_yt_api_key',
		'aae_enable_wpml_rewrite_fix'             => 'aaeaddon_enable_wpml_rewrite_fix',
		'aae_disable_smoother_in_editor'          => 'aaeaddon_disable_smoother_in_editor',
	),

	/*
	 * Option names built from a prefix plus an id (one row per menu). Matched
	 * by `str_starts_with` on the generic `pre_option` filter.
	 */
	'prefixes'    => array(
		'wcf_menu_options_' => 'aaeaddon_menu_options_',
		// Dismissed admin notices (Notices.php): one "yes" row per notice id.
		'aae_notice__'      => 'aaeaddon_notice_',
	),

	/*
	 * Deliberately NOT renamed. name => why. Reads of these are left alone in
	 * both plugins, and the reviewer reply names each one.
	 */
	'keep'        => array(
		'wcf_addon_sl_license_key'    => 'Pro gates its entire include_files() on the licence status being exactly "valid" and the EDD licence server keys on these; a copy landing one request late deactivates every Pro module.',
		'wcf_addon_sl_license_status' => 'see wcf_addon_sl_license_key',
		'wcf_addon_sl_license_email'  => 'see wcf_addon_sl_license_key',
		'aaeaddon_template_import_state'    => 'already prefixed',
		'aaeaddon_template_import_progress' => 'already prefixed',
		'aaeaddon_migration_state'    => 'the migration state itself',
		'aaeaddon_migration_log'      => 'the migration log itself',
	),

	/*
	 * Rows older releases wrote and nothing reads any more. Removed on
	 * uninstall only.
	 */
	'dead'        => array(
		'aae_do_activation_redirect', 'aae_activation_count', 'aae_deactivation_count',
		'aae_last_activated', 'aae_last_deactivated', 'aae_send_activation_event', 'aae_send_deactivation_event',
	),

	/*
	 * Everything below is LISTED, not renamed. Post/term/user meta keys are not
	 * in the Plugins Team's rule, and several are the `meta_key` of a WP_Query
	 * / ORDER BY in BOTH plugins (`wcf_post_views_count` is how "popular posts"
	 * sort) — a renamed key would freeze the old add-on's ordering on its
	 * snapshot. Transients expire and every reader tolerates a miss. Cron
	 * hooks and table names are not in the rule either. They are recorded here
	 * for the uninstall hook, the backup file and the reviewer reply.
	 */
	'postmeta'    => array(
		// own CPTs
		'wcf-addons-template-meta', 'wcf-addons-template-meta_type', 'wcf-addons-template-meta_location',
		'aae_header_smoother', 'aae_header_smoother_offsety',
		'popup_trigger', 'popup_selector', 'effect', 'delayTime', 'scrollPostion',
		'code_type', 'is_active', 'load_location', 'priority', 'visibility_page', 'visibility_page_list',
		'wcf_addon_custom_fonts', 'custom_font_global', 'wcf_addon_custom_icons', 'wcf_addon_custom_icontype', 'aae_gl_load',
		'wcf_mega_menu_settings',
		// post-rating CPT
		'rating', 'review', 'review_count', 'reviewed_post_type', 'name', 'email', 'user_id', 'post_id',
		// site-wide
		'wcf_post_views_count', 'aae_post_shares', 'aae_post_shares_count', 'aae_post_likes', 'aae_trending_score',
		'aaeaddon_post_reactions', 'aaeaddon_post_total_reactions',
		'aae_imported', 'aae_import_batch',
		'_aae_event_location', '_video_url', '_audio_url', '_gallery_images', '_video_story_link',
		'_aae_cache_compat', '_aae_cache_compat_ls',
	),
	'termmeta'    => array( 'aae_cat_bg_color', 'aae_cat_color', 'aae_cate_additional_text', 'aae_category_icon', 'aae_category_image' ),
	'usermeta'    => array( 'wcf_phone_number', 'author_social_profiles', 'aae_free_version_notice_dismissed', 'aae_theme_assets_notice_dismissed' ),
	'transients'  => array(
		// Both spellings: the pre-4.2 rows an upgraded site may still hold (they
		// expire within the hour) and the names the code writes now.
		'aae_v3_usage', 'aae_atomic_usage', 'aae_atomic_usage_count', 'aae_cat_badge_css', 'aae_preset_type_*',
		'aaeaddon_v3_usage', 'aaeaddon_atomic_usage', 'aaeaddon_atomic_usage_count', 'aaeaddon_cat_badge_css', 'aaeaddon_preset_type_*',
		'aaeaddon_st_importer_data', 'aaeaddon_st_importer_data_failed_attachment_imports',
		'aaeaddon_import_menu_mapping', 'aaeaddon_import_posts_with_nav_block',
		'aaeaddon_weather_current_*', 'aaeaddon_weather_forecast_*', 'aae_frl_*', 'aaeaddon_frl_*',
		'aae_addon_pro_plugin_update_notice', 'aaeaddon_pro_plugin_update_notice', 'aae_pro_ping_cooldown', 'aaeaddon_pro_ping_cooldown',
		// Renamed 2026-09-17, every one short-lived; both spellings listed for uninstall.
		'aae_templates_data_*', 'aaeaddon_templates_data_*', 'wcf_code_snippet_flash_*', 'aaeaddon_code_snippet_flash_*',
		'aae_ftok_*', 'aaeaddon_ftok_*', 'aae_video_thumb_*', 'aaeaddon_video_thumb_*', 'aae_pp_*', 'aaeaddon_pp_*',
		'wcf_feature_request_*', 'aaeaddon_feature_request_*', 'aae_pc_*', 'aaeaddon_pc_*', 'aae_rate_lock_*', 'aaeaddon_rate_lock_*',
		'aae_notice__*', 'aaeaddon_notice_*',
	),
	'cron'        => array( 'aae_form/process_queue', 'aae_form/process_queue_sweep', 'aae_form/cleanup_uploads' ),
	'tables'      => array( 'aae_forms', 'aae_form_schemas', 'aae_submissions', 'aae_submission_values', 'aae_action_jobs', 'aae_action_logs', 'aae_attachments' ),
);
