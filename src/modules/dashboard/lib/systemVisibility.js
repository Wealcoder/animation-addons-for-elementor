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
 * Has this site been TOLD that AAE has an atomic set, and what did it answer?
 *
 * `class-atomic.php::atomic_optin_signal()`, shipped as
 * `addons_config.atomic_optin`. It exists because the rules below have one
 * blind spot, and it is the worst possible one: the moment a long-time V3 site
 * MOVES to V4. Nothing atomic is switched on yet — which is exactly what
 * SHOW_V4_SYSTEM reads as "this site does not use V4" — so the tab that would
 * introduce the atomic registry is hidden from the one person who just went
 * looking for it. The notice (`components/shared/AtomicOptInNotice.jsx`) is the
 * way out, and `state: "accepted"` is the user having taken it.
 *
 * Nested under `addons_config`, so unlike `v3_in_use` these arrive as real
 * booleans — wp_localize_script only stringifies TOP-LEVEL scalars. `!!` anyway:
 * it costs nothing and survives the key being moved.
 */
const ATOMIC_OPTIN = WCF_ADDONS_ADMIN?.addons_config?.atomic_optin || {};

/**
 * The user pressed a button on the notice — either "Enable" or "Choose
 * manually". Both mean the same thing to the rules below: this person has asked
 * to see the atomic lists, so the lists stay visible from here on even if they
 * later switch every last item off again.
 */
export const ATOMIC_OPTED_IN = ATOMIC_OPTIN.state === "accepted";

/**
 * Has this site said NO -- either by dismissing the notice or by taking the
 * "Back to V3" way out, which records the same state?
 *
 * Distinct from "undecided", which is the state the notice itself covers. Once
 * the notice is gone there is no route back INTO V4 short of typing the
 * system=atomic query arg, and a door that only opens one way is not a door --
 * so this is what puts TryAtomicLink on the V3 tab row.
 */
export const ATOMIC_DISMISSED = ATOMIC_OPTIN.state === "dismissed";

/**
 * Why we think this site is on V4: "usage" (V4 elements are saved in its
 * pages), "experiment" (Elementor's V4 switch is on) or "none". Drives the
 * notice's wording only — `usage` is a statement about their pages and
 * `experiment` a statement about a setting, and saying the second when the
 * first is true reads as if we had not looked.
 */
export const ATOMIC_SIGNAL = ATOMIC_OPTIN.signal || "none";

/**
 * Is Elementor's own V4 switch on? (`e_atomic_elements`.)
 *
 * Separate from ATOMIC_SIGNAL because the two facts can BOTH be true, and the
 * notice is supposed to say so — "you switched V4 on AND you are already
 * building with it" is a different, stronger sentence than either half.
 * ATOMIC_SIGNAL only carries the strongest one, since that is what the ranked
 * dismissal compares against.
 */
export const ATOMIC_EXPERIMENT_ON = !!ATOMIC_OPTIN.experiment;

/**
 * Is anything in AAE atomic registry switched on right now?
 * (`Atomic::has_active_atomic()`, the PHP mirror of V4_HAS_ACTIVE.)
 *
 * Shipped rather than re-derived from the counts because the permanent
 * `BackToV3Link` needs it on screens that never group the registry, and two
 * answers to one question is what this module exists to prevent.
 */
export const ATOMIC_ACTIVE = !!ATOMIC_OPTIN.has_active;

/**
 * Does this site's CONTENT use Elementor V4 elements? The V4 mirror of
 * V3_IN_USE — see `Atomic::has_atomic_usage()`.
 *
 * Meaningful only while SHOW_ATOMIC_OPTIN_NOTICE is true: PHP skips the scan
 * entirely once its answer cannot change what is on screen, and ships `null`
 * rather than a `false` it never checked.
 */
export const ATOMIC_IN_USE = !!ATOMIC_OPTIN.in_use;

/**
 * How many posts on this site are built with V4 elements.
 *
 * 0 when PHP did not count -- it only pays for that query when a notice is
 * going on screen AND the signal is usage (see Atomic::atomic_optin_signal()),
 * so a 0 here means "not asked" as often as it means "none". Only ever read to
 * sharpen a sentence the usage signal has already earned, where it can be
 * neither.
 */
export const ATOMIC_IN_USE_COUNT = Number(ATOMIC_OPTIN.in_use_count) || 0;

/**
 * Should the dashboard offer the atomic set right now?
 *
 * PHP owns the whole rule (signal present, nothing atomic active yet, not
 * already answered at this signal strength) — re-deriving any part of it here
 * would give the same question two answers, which is the mistake this module
 * exists to prevent. ATOMIC_AVAILABLE is re-checked only because an offer for a
 * registry that is not loaded would lead to an empty tab.
 */
export const SHOW_ATOMIC_OPTIN_NOTICE =
  ATOMIC_AVAILABLE && !!ATOMIC_OPTIN.show_notice;

/**
 * The return path out of an accidental "Enable atomic features"
 * (`Atomic::atomic_undo_offer()`, shipped as `addons_config.atomic_undo`).
 *
 * One click switching on everything the site can register is the right shape
 * for the offer, and exactly the shape that needs a way back. `available` is
 * true only while a snapshot of the PREVIOUS atomic state is still on file —
 * PHP drops it the moment the user saves either atomic list by hand, because
 * from then on the stored state is their own work and restoring over it would
 * be the one thing an undo must never do.
 *
 * `widgets` / `extensions` are the counts currently switched on, so the notice
 * can name numbers somebody can check against the list in front of them.
 */
const ATOMIC_UNDO = WCF_ADDONS_ADMIN?.addons_config?.atomic_undo || {};

export const ATOMIC_UNDO_AVAILABLE = !!ATOMIC_UNDO.available;

export const ATOMIC_UNDO_COUNTS = {
  widgets: ATOMIC_UNDO.widgets || 0,
  extensions: ATOMIC_UNDO.extensions || 0,
};

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
 *
 * ATOMIC_OPTED_IN is a fourth way into the V4 column, and it changes no row
 * above — every one of them still resolves exactly as it did. It only adds the
 * case the table cannot express, because it is not a fact about the site but a
 * request from the user: a V3 site that answered the atomic notice keeps its V4
 * tab even with nothing atomic switched on, which is the state someone is in
 * for the whole of the first visit. Without it, "Choose manually" would reveal
 * a tab that vanishes on the next page load, and pressing Enable and then
 * switching a widget back off would take the list away mid-task.
 */
export const SHOW_V3_SYSTEM = !ATOMIC_AVAILABLE || V3_PRESENT;

export const SHOW_V4_SYSTEM =
  ATOMIC_AVAILABLE && (V4_HAS_ACTIVE || ATOMIC_OPTED_IN || !V3_PRESENT);

/**
 * Should the V3 view offer "Try Elementor V4" — the way back IN?
 *
 * LIVES HERE, not inside the component, because two callers need the same
 * answer. `TryAtomicLink` uses it to decide whether to render, and the
 * Extensions page needs it to decide whether to render the tab ROW at all: that
 * row is gated on `(showTabs || isAtomic)`, and a dismissed V3-only site is
 * neither — so a link that gated only itself was mounted inside a row nobody
 * ever built, and silently never appeared. Re-deriving the rule at the call
 * site instead would put the same question in two places and let them drift,
 * which is the thing this module exists to prevent.
 *
 * Four conditions, each removing a case where the link would be WRONG rather
 * than merely redundant:
 *
 *  - `ATOMIC_AVAILABLE` — the registry loaded. On Elementor <4 both atomic keys
 *    are absent from the payload, and this would offer an empty tab.
 *  - `ATOMIC_DISMISSED` — they were asked and said no. While UNDECIDED the
 *    notice is on screen doing this job better, and two offers of the same
 *    thing on one screen is worse than one.
 *  - `!ATOMIC_OPTED_IN` — never offer somebody what they already took.
 *  - `!SHOW_V4_SYSTEM` — the tab is genuinely hidden. Once it is on screen the
 *    tab IS the route, and a link beside it pointing at it is noise.
 */
export const SHOW_TRY_ATOMIC_LINK =
  ATOMIC_AVAILABLE && ATOMIC_DISMISSED && !ATOMIC_OPTED_IN && !SHOW_V4_SYSTEM;

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
    //
    // With BOTH on screen the default follows the WORK, not the opt-in.
    // "Choose manually" reveals the V4 tab without switching anything on, and
    // defaulting there would land a long-time V3 user on an empty list with
    // their whole site behind a tab. V4_HAS_ACTIVE is the honest test. An
    // explicit `?system=atomic` still wins, and that is what the accept flow
    // sends, so the celebration still lands on V4.
    //
    // Changes nothing that predates the opt-in: before it, showV4 without
    // V4_HAS_ACTIVE required !V3_PRESENT, which forces showV3 false -- so
    // "both visible AND nothing atomic on" was unreachable.
    system:
      forced || (showV4 && (V4_HAS_ACTIVE || !showV3) ? "atomic" : "v3"),
  };
};
