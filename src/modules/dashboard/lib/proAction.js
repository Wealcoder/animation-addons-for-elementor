/**
 * The admin-ajax actions the PRO plugin answers, addressed by what they do.
 *
 * Pro 4.3 renamed its actions to the shared `aaeaddon_` prefix and publishes
 * the names it registers as `addons_config.pro_actions` (through the
 * `wcf_addons_dashboard_config` filter). An older Pro publishes nothing and
 * answers only the pre-4.3 spelling, which is the fallback kept here — so this
 * dashboard keeps working across the update window whichever plugin moves
 * first. Drop the fallback table when Pro 4.3 is the floor.
 */
const LEGACY = {
  complete_performance_wizard: "aae_complete_performance_wizard",
  wizard_toggle_v3: "aae_wizard_toggle_v3",
  wizard_clean_v3: "aae_wizard_clean_v3",
  save_performance_settings: "aae_save_performance_settings",
  scan_widget_usage: "aae_scan_widget_usage",
  save_library_settings: "save_settings_dashboard_library_ajax",
  pro_sl_activate: "wcf_addon_pro_sl_activate",
  pro_sl_deactivate: "wcf_addon_pro_sl_deactivate",
  server_opcache_reset: "aae_server_opcache_reset",
};

export function proAction(key) {
  const published = window.WCF_ADDONS_ADMIN?.addons_config?.pro_actions;
  if (published && typeof published[key] === "string" && published[key]) {
    return published[key];
  }
  if (!(key in LEGACY)) {
    throw new Error(`proAction: unknown key "${key}"`);
  }
  return LEGACY[key];
}
