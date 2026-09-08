/**
 * The client side of importing an Elementor V4 (atomic) starter template.
 *
 * Three things, and why each lives where it does:
 *
 *  - `ATOMIC_IMPORT_AVAILABLE` is a SNAPSHOT of the page payload, like every
 *    rule in systemVisibility.js: it decides whether a V4 card's Import button
 *    is offered at all, and that must not flicker mid-session. It is absent
 *    (→ false) whenever the atomic registry did not load — Elementor < 4, or
 *    the atomic experiment switched off — which is exactly when a V4 template
 *    would import and render nothing.
 *
 *  - `fetchAtomicImportStatus()` asks the server FRESH, at click time, because
 *    `in_use` changes during a session: the first V4 import makes every later
 *    one a "second import", and the dialog exists for that case. Reading it
 *    from the payload would show the warning one page load too late.
 *
 *  - `isV4Template()` is the one place the server's `builder_version` field is
 *    interpreted. Absent means V3 — the ~960 older templates carry no value.
 */

export const ATOMIC_IMPORT_AVAILABLE = !!(
  typeof WCF_ADDONS_ADMIN !== "undefined" &&
  WCF_ADDONS_ADMIN?.addons_config?.atomic_import?.available
);

export const isV4Template = (template) => template?.builder_version === "v4";

/**
 * URL param that carries the "copy images into my media library" choice from
 * the V4 dialog through Required Features and Demo Importing, where it becomes
 * `aae_localize_images` on the importer request. Off by default: the import is
 * then exactly what it was, with every linked image hot-linked from the demo
 * host. See inc/admin/atomic-image-localize.php.
 */
export const LOCALIZE_IMAGES_PARAM = "v4images";

/**
 * @returns {Promise<{available: boolean, in_use: boolean}|null>} null when the
 *   request failed — callers then proceed without the dialog, since the
 *   importer picks the same mode server-side from the same signal anyway.
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
