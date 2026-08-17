import {
  countAtomicWidgets,
  groupAtomicWidgetsByCategory,
} from "@/lib/atomicWidgetService";
import {
  countAtomicExtensions,
  groupAtomicExtensionsByCategory,
} from "@/lib/atomicExtensionService";

/**
 * Which element system (Elementor V3 / V4 Atomic) this site is offered.
 *
 * A site that only ever uses one of the two should not be shown the other —
 * a new user landing on a V3 widget list has no idea what they are looking at,
 * and a long-time V3 user does not want an Atomic tab they will never open.
 * The dashboard therefore hides the system that is entirely switched off.
 *
 * SNAPSHOT, NOT LIVE STATE — deliberately. Every value here is derived once at
 * module load from the payload PHP shipped with the page (i.e. the SAVED
 * option), never from the reducer in app.context.jsx. Reading live state would
 * make the tab the user is standing on disappear the moment they toggled the
 * last widget off, mid-gesture and before they had saved anything. The new
 * layout appears on the next page load, which is when the saved state is
 * actually what these booleans claim it is.
 *
 * The hidden system is still REACHABLE by URL (`&system=v3` / `&system=atomic`)
 * — see resolveSystems() — so hiding is never a one-way door. Same arrangement
 * `performance` and `integrations` already have in showFullContent.jsx.
 */

const atomicWidgetCount = countAtomicWidgets(
  groupAtomicWidgetsByCategory(WCF_ADDONS_ADMIN?.addons_config?.atomic_widgets)
);

const atomicExtensionCount = countAtomicExtensions(
  groupAtomicExtensionsByCategory(
    WCF_ADDONS_ADMIN?.addons_config?.atomic_extensions
  )
);

/**
 * Is the atomic registry present at all?
 *
 * `class-atomic.php::init_hooks()` bails on `meets_requirements()` (Elementor
 * 4.0+ with the atomic experiment on), which means `inject_dashboard_config()`
 * never runs and BOTH atomic keys are absent from the payload. On such a site
 * the V4 tab must never appear, however the V3 counts read — an empty tab is
 * worse than no tab.
 */
export const ATOMIC_AVAILABLE =
  atomicWidgetCount.total + atomicExtensionCount.total > 0;

/**
 * V3's active counts come pre-computed from PHP (`get_widgets()` /
 * `get_extensions()`, i.e. the `wcf_save_widgets` / `wcf_save_extensions`
 * options intersected with config.php). Atomic has no server-side pair, so it
 * is counted client-side from the same grouped shape the lists render.
 */
export const V3_HAS_ACTIVE =
  (WCF_ADDONS_ADMIN?.widgets?.active || 0) > 0 ||
  (WCF_ADDONS_ADMIN?.extensions?.active || 0) > 0;

export const V4_HAS_ACTIVE =
  atomicWidgetCount.active > 0 || atomicExtensionCount.active > 0;

/**
 * Does the site's CONTENT use v3 widgets, whatever the toggles say?
 * (`Animation_Settings::has_v3_usage()`, shipped as `v3_in_use`.)
 *
 * The active count alone is the wrong question. A site can hold 34 pages built
 * from `wcf--*` widgets while `wcf_save_widgets` is empty — an import does
 * exactly that, and `maybe_enable_used_v3_widgets()` only heals it while the
 * option has NEVER been written. Deciding "this is a V4-only site" from the
 * count would then hide V3 from the one person who needs it most: the pages
 * are already rendering nothing, and the screen that could switch their
 * widgets back on would be gone too.
 *
 * So it is a RATCHET, matching `legacy_v3` (Rule 5): evidence of v3 can only
 * ever turn V3 back on, never off.
 *
 * `!!`, NOT `=== true`. wp_localize_script stringifies scalars, so the PHP
 * boolean arrives as `"1"` / `""` — an identity check against `true` is false
 * on both, which fails in the silent direction (the ratchet just never fires).
 */
export const V3_IN_USE = !!WCF_ADDONS_ADMIN?.v3_in_use;

/** V3 counts as present if it is switched on OR the content depends on it. */
export const V3_PRESENT = V3_HAS_ACTIVE || V3_IN_USE;

/**
 * The rules, and the one case that is not symmetric:
 *
 * | V3 present | V4 active | shown        |
 * |------------|-----------|--------------|
 * | yes        | yes       | both         |
 * | yes        | no        | V3 only      |
 * | no         | yes       | V4 only      |
 * | no         | no        | **V4 only**  |
 *
 * The last row is a fresh install — nothing is enabled anywhere (config.php
 * ships no `is_active => true` and neither atomic option exists until the
 * wizard writes one) AND no page uses a v3 widget. Both tabs would qualify for
 * hiding, so V4 wins: a brand new user is exactly who this feature exists for,
 * and the wizard already offers them the atomic registry only.
 */
export const SHOW_V3_SYSTEM = !ATOMIC_AVAILABLE || V3_PRESENT;

export const SHOW_V4_SYSTEM = ATOMIC_AVAILABLE && (V4_HAS_ACTIVE || !V3_PRESENT);

/**
 * Animation Settings is the V4 replacement for the five v3 chrome features that
 * used to live in Elementor's Site Settings (Preloader, Cursor, Scroll to Top,
 * Scroll Indicator, Popup), so it is offered once the v3 widgets and extensions
 * are all switched off.
 *
 * `V3_HAS_ACTIVE`, NOT `V3_PRESENT` — the toggles alone, deliberately. The
 * content ratchet exists to stop a site LOSING the V3 list while pages still
 * depend on it; that is an argument about the widget list, not about which
 * settings screen this site configures its chrome from. A site whose v3
 * switches are all off has moved to v4 no matter what its old pages still
 * contain, so it gets the v4 screen — and does not have to keep both.
 *
 * Hidden from the MENU only — `?tab=animation-settings` still renders.
 *
 * KNOWN GAP: on a site with v3 switches ON there is no in-dashboard route to
 * this screen, so its `legacy_v3` switch, Performance and GSAP Library tabs are
 * URL-only there. The Legacy (V3) link on the V4 view does not go here — it
 * reveals the V3 LIST, which is a different job. Worth closing, and it is the
 * reason the route must stay registered in showFullContent.jsx.
 */
export const SHOW_ANIMATION_SETTINGS = !V3_HAS_ACTIVE;

/**
 * Resolve what a Widgets/Extensions page should show, honouring an explicit
 * `?system=` in the URL.
 *
 * An explicit request always wins and REVEALS its tab even when the rules
 * above would hide it — that is what keeps a deep link (and the Starter
 * Template / search deep links that carry `system=`) working on a site whose
 * saved state points the other way.
 *
 * Deliberately does NOT decide whether the switcher renders. That depends on
 * the Legacy (V3) reveal link's runtime state, which is the page's to own —
 * returning a `showTabs` here as well would give the same question two answers.
 *
 * @param {string|null} requested Raw `system` query param.
 * @return {{ showV3: boolean, showV4: boolean, system: string }}
 */
export const resolveSystems = (requested) => {
  const forced =
    requested === "v3" || requested === "atomic" ? requested : null;

  const showV3 = SHOW_V3_SYSTEM || forced === "v3";
  const showV4 = SHOW_V4_SYSTEM || forced === "atomic";

  return {
    showV3,
    showV4,
    // Falls back to whichever tab is on screen, so a `system=v3` on a site
    // that hides V3 can never leave the page rendering a tab nobody can see.
    system: forced || (showV4 ? "atomic" : "v3"),
  };
};
