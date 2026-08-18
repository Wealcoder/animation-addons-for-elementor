/**
 * "Where is this widget actually used?" — the free half.
 *
 * The screen is free, the scan is Pro (pro/inc/Usage/class-widget-usage.php).
 * `WCF_ADDONS_ADMIN.usage` arrives through the `aae/usage/dashboard_payload`
 * filter Pro answers; with Pro absent it is `{}` and USAGE_AVAILABLE is false,
 * which is how the button knows to be an upsell rather than firing a request at
 * an endpoint nobody registered. Same seam Performance uses.
 */

/** Is the Pro scan endpoint there to answer? */
export const USAGE_AVAILABLE = !!WCF_ADDONS_ADMIN?.usage?.available;

/**
 * Run the scan.
 *
 * Deliberately has no cache of its own, in either direction: the server does
 * not store the result and this does not memoise it. The number is what someone
 * consults before switching a widget off, and a stale one is worse than none —
 * pressing the button IS the freshness guarantee, so pressing it again must
 * genuinely re-ask.
 *
 * @return {Promise<{v3: Object, atomic: Object, scanned: number}>}
 */
export const fetchWidgetUsage = async () => {
  const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams({
      action: "aae_scan_widget_usage",
      nonce: WCF_ADDONS_ADMIN.nonce,
    }),
  });

  const payload = await response.json();

  if (!payload?.success) {
    throw new Error(payload?.data || "usage scan failed");
  }

  return payload.data;
};
