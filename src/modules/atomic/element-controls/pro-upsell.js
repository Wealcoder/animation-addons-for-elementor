/**
 * Shared constants for every in-editor Pro upsell.
 *
 * Three controls were about to carry their own copy of both values, and the URL
 * had already drifted once — PresetPickerControl points at the site root while
 * the newer gates point at /pricing/ — which is how half a campaign link ends up
 * shipped. PHP has the same single source in Atomic::UPGRADE_URL.
 */

/** Keep in step with Atomic::UPGRADE_URL (inc/AtomicWidgets/class-atomic.php). */
export const UPGRADE_URL = "https://animation-addons.com/pricing/";

/**
 * The plugin's established upgrade accent — see inc/admin/row-actions.php's
 * "Upgrade to Pro" link. Not a new palette entry.
 */
export const PRO_ACCENT = "#ff7a00";
