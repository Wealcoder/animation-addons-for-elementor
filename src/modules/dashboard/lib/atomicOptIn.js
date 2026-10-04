import { applyWizardSetup } from "@/lib/setupPresets";
import { flattenAtomicWidgets } from "@/lib/atomicWidgetService";
import { flattenAtomicExtensions } from "@/lib/atomicExtensionService";

/**
 * The writes behind the atomic opt-in notice and its return path
 * (`components/shared/AtomicOptInNotice.jsx`, `AtomicUndoNotice.jsx`).
 *
 * The SELECTION is not new machinery: it comes from the same
 * `applyWizardSetup()` the setup wizard uses and the same `flatten*` helpers
 * the Widgets and Extensions lists save through, so "what a recommended setup
 * switches on" keeps living in exactly one place (`lib/setupPresets.js`)
 * instead of being restated in PHP.
 *
 * The REQUEST is one call, not three — see `Atomic::ajax_atomic_optin()`. The
 * enable has to take its undo snapshot and write both options inside a single
 * request, because the ordinary save endpoints deliberately END the undo offer
 * (a hand save is the user's own choice) and would delete the snapshot the
 * notice had just taken.
 *
 * WHAT IS DELIBERATELY NOT TOUCHED: `wcf_save_widgets` / `wcf_save_extensions`,
 * the v3 pair. Not written, not cleared, not read. Switching the atomic set on
 * takes nothing away from a v3 site — its widgets stay registered, its pages
 * keep rendering, and its V3 tab is still on screen next to the new one. That
 * is also why the return path only has one option pair to restore. The wizard
 * has the same rule for a sharper reason (writing even an empty
 * `wcf_save_widgets` disarms `maybe_enable_used_v3_widgets()` and can blank an
 * imported page); see the long comment in `components/wizards/WizFooter.jsx`.
 */

const post = (body) =>
  fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams(body),
  })
    .then((response) => response.json())
    .then((result) => {
      /*
       * admin-ajax answers a refused request with HTTP 200 and
       * `{ success: false }`, so a bare `.json()` resolves happily on a
       * permission denial or a stale nonce. Every caller here reloads the
       * page on resolve — landing the user on a list that disagrees with what
       * they just pressed, with no error anywhere. Reject instead, so the
       * toast fires and the notice stays put.
       *
       * The two save endpoints answer `{ status, total }` with no `success`
       * key at all, which is why this tests for FALSE rather than for truth.
       */
      if (result && result.success === false) {
        throw new Error(result.data || "Request failed");
      }

      return result;
    });

/**
 * Record the user's answer, and — for an accept — enable the recommended set in
 * the same request.
 *
 * The wizard's "Custom" rule (`applyWizardSetup(…, "advance")`), not its
 * "Basic" one. Basic is free-only by design, which is right for a first-run
 * wizard that does not yet know what the site is; this notice is answered by
 * someone who has already moved to V4 and is asking for the atomic set, and
 * handing a licensed site a half-enabled registry would read as the purchase
 * not working. The rule is license-aware on its own — on a free site it
 * resolves to the free items, which is the same set Basic would have given.
 *
 * @param {"accepted"|"dismissed"} state
 * @param {Object|null} enable `{ widgets, extensions }` grouped state to switch
 *                             on, or null to record the decision only.
 */
export const recordAtomicOptIn = (state, enable = null) => {
  const body = {
    action: "aae_atomic_optin",
    state,
    nonce: WCF_ADDONS_ADMIN.nonce,
  };

  if (enable) {
    body.fields = JSON.stringify(
      flattenAtomicWidgets(applyWizardSetup(enable.widgets, "advance"))
    );
    body.ext_fields = JSON.stringify(
      flattenAtomicExtensions(applyWizardSetup(enable.extensions, "advance"))
    );
  }

  return post(body);
};

/**
 * Answer the undo offer.
 *
 * `undo` puts the previous atomic state back exactly as it was — including
 * DELETING the option rows that had never existed, which is a different site
 * state from empty ones (see `Atomic::UNDO_OPTION_NAME`). `keep` just ends the
 * offer.
 *
 * @param {"undo"|"keep"} decision
 */
export const answerAtomicUndo = (decision) =>
  post({
    action: "aae_atomic_optin_undo",
    decision,
    nonce: WCF_ADDONS_ADMIN.nonce,
  });

/**
 * Land the user on a widgets list, on a FULL page load.
 *
 * Not the in-app route the other shortcuts use. Every visibility rule in
 * `lib/systemVisibility.js` is a snapshot taken once at module load from the
 * payload PHP shipped with the page — deliberately, so a tab cannot vanish
 * mid-gesture — which means the tab this click just earned (or gave back) does
 * not exist until the payload is fetched again. `system=` is carried as well so
 * the right list is selected on arrival even before the snapshot agrees.
 *
 * @param {"atomic"|"v3"} system
 */
export const goToWidgets = (system) => {
  const url = new URL(window.location.href);
  const pageQuery = url.searchParams.get("page");

  url.search = `page=${pageQuery}`;
  url.hash = "";
  url.searchParams.set("tab", "widgets");
  url.searchParams.set("system", system);

  window.location.assign(url.toString());
};
