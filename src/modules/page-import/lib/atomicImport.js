/**
 * The client side of importing an Elementor V4 (atomic) starter page.
 *
 * Twin of src/modules/dashboard/lib/atomicImport.js — the two modules are
 * separate bundles by design, so this is a copy, not an import. Keep them in
 * step.
 *
 *  - `ATOMIC_IMPORT_AVAILABLE` is a SNAPSHOT of the page payload: it decides
 *    whether a V4 card's Import button is offered at all. Absent (→ false)
 *    whenever the atomic registry did not load, which is exactly when a V4
 *    page would import and render nothing.
 *
 *  - `fetchAtomicImportStatus()` asks the server FRESH at click time, because
 *    `in_use` changes during a session: the first V4 import makes every later
 *    one a "second import", and the dialog exists for that case.
 *
 *  - `isV4Template()` is the one place `builder_version` is interpreted.
 *    Absent means V3.
 */

export const ATOMIC_IMPORT_AVAILABLE = !!(
  typeof WCF_ADDONS_ADMIN !== "undefined" &&
  WCF_ADDONS_ADMIN?.addons_config?.atomic_import?.available
);

export const isV4Template = (template) => template?.builder_version === "v4";

/**
 * How a V4 page's global classes and variables land on a site that already
 * has some. Mirrors `aae_page_mode` in inc/admin/template-importer.php:
 * anything but `match_site` is `keep_create` there, so the default here is
 * the server's default too.
 */
export const PAGE_MODE_KEEP = "keep_create";
export const PAGE_MODE_MATCH = "match_site";

/**
 * @returns {Promise<{available: boolean, in_use: boolean}|null>} null when the
 *   request failed — callers then proceed without the dialog.
 */
export async function fetchAtomicImportStatus() {
  try {
    const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        action: "aaeaddon_atomic_import_status",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    });
    const json = await response.json();
    if (!json?.success) return null;
    return {
      available: !!json.data?.available,
      in_use: !!json.data?.in_use,
    };
  } catch (e) {
    return null;
  }
}
