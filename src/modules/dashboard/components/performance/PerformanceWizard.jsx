import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { __ } from "@wordpress/i18n";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

/**
 * Performance Wizard — a guided, self-contained setup that lives under the
 * Performance tab. It writes NOTHING of its own: every step drives an endpoint
 * that already exists and is already tested —
 *
 *   - v3 widgets / extensions / chrome  -> aae_wizard_toggle_v3   (Pro, reversible)
 *   - legacy (v3) UI visibility         -> aae_save_animation_settings
 *   - theme assets / WordPress assets   -> aae_save_performance_settings
 *   - completion marker                 -> aae_complete_performance_wizard
 *
 * The step LIST is derived from the payload, so a fresh v4-only site (no v3 to
 * clean, companion theme already active) never sees the steps that would do
 * nothing for it.
 */

const ajax = async (action, body) => {
  const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json" },
    credentials: "same-origin",
    body: new URLSearchParams({ action, nonce: WCF_ADDONS_ADMIN.nonce, ...body }),
  });
  const json = await res.json();
  if (!json?.success) throw new Error(json?.data || "request failed");
  return json.data;
};

/** A labelled toggle row with an optional description. */
const ToggleRow = ({ id, label, description, checked, disabled, onChange, danger }) => (
  <div className="flex items-start justify-between gap-4 py-3" data-aae-wizard-toggle={id}>
    <div className="flex-1 min-w-0">
      <p className="text-[13px] font-medium text-[var(--900,#181B25)]">{label}</p>
      {description && (
        <p className={`text-[12px] mt-1 ${danger ? "text-[#a15c00]" : "text-[var(--600,#525866)]"}`}>
          {description}
        </p>
      )}
    </div>
    <Switch checked={!!checked} disabled={disabled} onCheckedChange={onChange} sx={{ marginTop: "2px" }} />
  </div>
);

/**
 * The one destructive, IRREVERSIBLE action in the wizard: permanently delete the
 * site's v3 configuration. Unlike every toggle above it, there is no backup — so
 * it is gated behind a typed "DELETE" confirmation on both sides (the server
 * refuses the same token too). Page content is never touched: the copy says so,
 * and a site that still renders v3 keeps its used widgets via the server's own
 * rescue.
 */
const CleanV3 = ({ onCleaned }) => {
  const [open, setOpen] = useState(false);
  const [text, setText] = useState("");
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);

  const run = async () => {
    setBusy(true);
    try {
      const data = await ajax("aae_wizard_clean_v3", { confirm: "DELETE" });
      setDone(true);
      setOpen(false);
      setText("");
      onCleaned?.();
      const n = (data?.removed_options?.length || 0) + (data?.removed_kit_keys || 0);
      toast.success(__("v3 data deleted", "animation-addons-for-elementor"), {
        position: "top-right",
        description:
          n > 0
            ? __("Your v3 settings were permanently removed.", "animation-addons-for-elementor")
            : __("There was no v3 configuration left to remove.", "animation-addons-for-elementor"),
      });
    } catch (e) {
      toast.error(__("Could not delete v3 data", "animation-addons-for-elementor"), {
        position: "top-right",
        description: String(e?.message || e),
      });
    } finally {
      setBusy(false);
    }
  };

  if (done) {
    return (
      <div className="mt-5 rounded-lg border border-[#E1E4EA] bg-[#f6f8fa] p-3 text-[12px] text-[var(--600,#525866)]">
        {__(
          "v3 configuration permanently deleted. Your page content is untouched; any v3 widget still used on a page comes back by itself.",
          "animation-addons-for-elementor",
        )}
      </div>
    );
  }

  return (
    <div className="mt-5 rounded-lg border border-[#f4c7c3] bg-[#fef3f2] p-3.5">
      <p className="text-[13px] font-semibold text-[#912018]">
        {__("Permanently delete v3 data", "animation-addons-for-elementor")}
      </p>
      <p className="text-[12px] text-[#a15c00] mt-1 leading-relaxed">
        {__(
          "Deletes the v3 widget, extension, GSAP-library and smooth-scroll settings, plus the v3 site chrome and popup styling stored in Elementor Site Settings. This CANNOT be undone. Your page content is not touched, and the Pro licence, menus and v4 settings are left alone.",
          "animation-addons-for-elementor",
        )}
      </p>

      {!open ? (
        <button
          type="button"
          className="mt-3 text-[12px] font-medium text-[#b42318] underline"
          onClick={() => setOpen(true)}
          data-aae-wizard-clean-open
        >
          {__("I understand — delete v3 data…", "animation-addons-for-elementor")}
        </button>
      ) : (
        <div className="mt-3">
          <label className="block text-[12px] text-[#912018] mb-1.5">
            {__('Type DELETE to confirm', "animation-addons-for-elementor")}
          </label>
          <input
            type="text"
            value={text}
            onChange={(e) => setText(e.target.value)}
            placeholder="DELETE"
            disabled={busy}
            autoFocus
            className="w-full rounded-[8px] border border-[#f4c7c3] bg-white px-3 py-2 text-[13px] outline-none focus:border-[#b42318]"
            data-aae-wizard-clean-input
          />
          <div className="flex items-center gap-2 mt-3">
            <Button
              className="rounded-[8px] bg-[#b42318] hover:bg-[#912018]"
              disabled={busy || text !== "DELETE"}
              onClick={run}
              data-aae-wizard-clean-confirm
            >
              {busy
                ? __("Deleting…", "animation-addons-for-elementor")
                : __("Delete permanently", "animation-addons-for-elementor")}
            </Button>
            <Button
              variant="secondary"
              className="rounded-[8px]"
              disabled={busy}
              onClick={() => {
                setOpen(false);
                setText("");
              }}
            >
              {__("Cancel", "animation-addons-for-elementor")}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

const PerformanceWizard = ({ wizard, perfSettings, onSavePerf, onDone, onExit }) => {
  // Local, live view of the v3 state (the payload is the initial snapshot).
  const [v3, setV3] = useState({
    widgets: !!wizard.v3_widgets,
    extensions: !!wizard.v3_extensions,
    global: !!wizard.v3_global,
    legacy: !!wizard.legacy_v3,
  });
  const [perf, setPerf] = useState(perfSettings || {});
  const [busy, setBusy] = useState(false);

  const theme = wizard.theme || {};
  const hasV3 =
    wizard.v3_usage || v3.widgets || v3.extensions || v3.global || v3.legacy;

  // Applicable steps only — fresh user skips what does not apply to them.
  const steps = useMemo(
    () =>
      [
        hasV3 && "cleanup",
        !theme.active && "theme",
        "theme_assets",
        "wp_assets",
        "litespeed",
        "done",
      ].filter(Boolean),
    // hasV3/theme.active only change on actions that also advance the wizard,
    // so recomputing per render is fine and keeps the list honest.
    [hasV3, theme.active],
  );

  const [i, setI] = useState(0);
  const step = steps[i] || "done";
  const isLast = i >= steps.length - 1;

  const next = () => setI((n) => Math.min(n + 1, steps.length - 1));
  const back = () => setI((n) => Math.max(n - 1, 0));

  // Why "on" can't stick when there is nothing to turn on: the switch flips
  // back to off, and without this it looks broken rather than empty.
  const NOTHING_TO_ENABLE = {
    widgets: __(
      "This site uses no v3 widgets, so there is nothing to switch on. If a page ever uses one, it comes back on by itself.",
      "animation-addons-for-elementor",
    ),
    extensions: __(
      "This site has no v3 extensions saved, so there is nothing to switch on.",
      "animation-addons-for-elementor",
    ),
    global: __(
      "Your Kit has no v3 chrome (preloader, cursor, …) to restore, so there is nothing to switch on.",
      "animation-addons-for-elementor",
    ),
  };

  // The v3 toggles (Pro endpoint). Optimistic, reverts on failure.
  const toggleV3 = async (target, enabled) => {
    const key = { widgets: "widgets", extensions: "extensions", global: "global" }[target];
    setV3((s) => ({ ...s, [key]: !enabled ? false : true }));
    setBusy(true);
    try {
      const data = await ajax("aae_wizard_toggle_v3", { target, enabled: enabled ? "1" : "0" });
      setV3((s) => ({ ...s, [key]: !!data.on }));

      // Asked to turn it on, but the server reports it still off: there was
      // nothing to enable (empty on this site). Explain, so the switch bouncing
      // back reads as "nothing here" instead of a bug.
      if (enabled && !data.on) {
        toast(__("Nothing to turn on", "animation-addons-for-elementor"), {
          position: "top-right",
          duration: 6000,
          description: NOTHING_TO_ENABLE[target],
        });
      }
    } catch (e) {
      setV3((s) => ({ ...s, [key]: !enabled })); // revert
      toast.error(__("Could not change that", "animation-addons-for-elementor"), {
        position: "top-right",
        description: String(e?.message || e),
      });
    } finally {
      setBusy(false);
    }
  };

  // legacy_v3 lives in animation settings, not performance.
  const toggleLegacy = async (enabled) => {
    setV3((s) => ({ ...s, legacy: enabled }));
    setBusy(true);
    try {
      const cur = WCF_ADDONS_ADMIN?.animation_settings?.settings || {};
      const data = await ajax("aae_save_animation_settings", {
        settings: JSON.stringify({ ...cur, legacy_v3: enabled }),
      });
      if (WCF_ADDONS_ADMIN?.animation_settings) {
        WCF_ADDONS_ADMIN.animation_settings.settings = data.settings;
      }
    } catch (e) {
      setV3((s) => ({ ...s, legacy: !enabled }));
      toast.error(__("Could not change that", "animation-addons-for-elementor"), {
        position: "top-right",
        description: String(e?.message || e),
      });
    } finally {
      setBusy(false);
    }
  };

  // Performance groups (theme_assets, wp_assets). Patches one group, saves whole.
  const savePerf = async (group, patch) => {
    const nextGroup = { ...(perf[group] || {}), ...patch };
    const nextAll = { ...perf, [group]: nextGroup };
    setPerf(nextAll);
    setBusy(true);
    try {
      const data = await onSavePerf(nextAll);
      if (data) setPerf(data);
    } catch (e) {
      setPerf(perf); // revert
    } finally {
      setBusy(false);
    }
  };

  const finish = async () => {
    setBusy(true);
    try {
      await ajax("aae_complete_performance_wizard", {});
      onDone?.();
      toast.success(__("Performance setup complete", "animation-addons-for-elementor"), {
        position: "top-right",
      });
    } catch (e) {
      toast.error(__("Could not finish", "animation-addons-for-elementor"), {
        position: "top-right",
        description: String(e?.message || e),
      });
    } finally {
      setBusy(false);
    }
  };

  const wp = perf.wp_assets || {};
  const ta = perf.theme_assets || {};

  return (
    <div className="bg-background rounded-lg p-6 max-w-[640px]" data-aae-perf-wizard>
      {/* Progress strip */}
      <div className="flex items-center gap-1.5 mb-5">
        {steps.map((s, idx) => (
          <span
            key={s}
            className={`h-1.5 flex-1 rounded-full ${idx <= i ? "bg-[#FC6848]" : "bg-[#E1E4EA]"}`}
          />
        ))}
      </div>

      {/* ---- STEP: v3 cleanup ---- */}
      {step === "cleanup" && (
        <div data-aae-wizard-step="cleanup">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Clean up legacy (v3)", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[12px] text-[var(--600,#525866)] mt-2">
            {__(
              "Your site is built with the v4 (atomic) widgets. Turning the old v3 layer off makes pages lighter. Nothing here is deleted — every switch is reversible.",
              "animation-addons-for-elementor",
            )}
          </p>

          {wizard.v3_usage && (
            <div className="mt-3 rounded-lg border border-[#ffe0a3] bg-[#fff6e5] p-3 text-[12px] text-[#7a4a00]">
              {__(
                "Heads up: some of your existing pages still use v3 widgets or v3 chrome. Turning these off stops that content from showing until you turn it back on — it is not deleted, and switching back restores it.",
                "animation-addons-for-elementor",
              )}
            </div>
          )}

          <div className="mt-3 divide-y divide-[#E1E4EA]">
            <ToggleRow
              id="widgets"
              label={__("v3 widgets", "animation-addons-for-elementor")}
              description={__(
                "The ~70 legacy widgets in the Elementor panel. Off = they stop loading; pages that use them show that content only when switched back on.",
                "animation-addons-for-elementor",
              )}
              danger={wizard.v3_usage}
              checked={v3.widgets}
              disabled={busy}
              onChange={(on) => toggleV3("widgets", on)}
            />
            <ToggleRow
              id="extensions"
              label={__("v3 extensions", "animation-addons-for-elementor")}
              description={__(
                "Legacy effect extensions. Off stops their assets loading.",
                "animation-addons-for-elementor",
              )}
              checked={v3.extensions}
              disabled={busy}
              onChange={(on) => toggleV3("extensions", on)}
            />
            <ToggleRow
              id="global"
              label={__("v3 global settings (preloader, cursor, …)", "animation-addons-for-elementor")}
              description={__(
                "The site-wide v3 chrome stored in your Kit. Off backs it up and stops it rendering; on restores it exactly.",
                "animation-addons-for-elementor",
              )}
              danger={wizard.v3_usage}
              checked={v3.global}
              disabled={busy}
              onChange={(on) => toggleV3("global", on)}
            />
            <ToggleRow
              id="legacy"
              label={__("Show v3 settings in Elementor", "animation-addons-for-elementor")}
              description={__(
                "Keeps the legacy tabs visible in Elementor → Site Settings. Off only hides the UI; it changes nothing on the front end.",
                "animation-addons-for-elementor",
              )}
              checked={v3.legacy}
              disabled={busy}
              onChange={toggleLegacy}
            />
          </div>

          <CleanV3
            onCleaned={() =>
              setV3((s) => ({ ...s, widgets: false, extensions: false, global: false }))
            }
          />
        </div>
      )}

      {/* ---- STEP: theme ---- */}
      {step === "theme" && (
        <div data-aae-wizard-step="theme">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Companion theme", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[12px] text-[var(--600,#525866)] mt-2">
            {__(
              "The Hello Animation theme is the lightest base for AAE — it lets the next step drop the theme's own CSS, fonts and jQuery on pages Elementor paints. This step is optional.",
              "animation-addons-for-elementor",
            )}
          </p>
          <a
            href={theme.installed ? theme.activate_url : theme.install_url}
            className="inline-block mt-3 text-[13px] font-medium text-[#FC6848] underline"
          >
            {theme.installed
              ? __("Activate Hello Animation", "animation-addons-for-elementor")
              : __("Install Hello Animation", "animation-addons-for-elementor")}
          </a>
        </div>
      )}

      {/* ---- STEP: theme assets ---- */}
      {step === "theme_assets" && (
        <div data-aae-wizard-step="theme_assets">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Stop the theme's assets", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[12px] text-[var(--600,#525866)] mt-2">
            {__(
              "On a page Elementor paints end to end, the theme's own CSS, fonts and jQuery render nothing. This stops them being loaded there. Pages the theme still lays out itself (blog, archives, search) keep their stylesheet.",
              "animation-addons-for-elementor",
            )}
          </p>
          {wizard.theme?.active ? (
            <div className="mt-3 divide-y divide-[#E1E4EA]">
              <ToggleRow
                label={__("Stop the theme loading its CSS and JS", "animation-addons-for-elementor")}
                checked={!!ta.enable}
                disabled={busy}
                onChange={(on) => savePerf("theme_assets", { enable: on })}
              />
            </div>
          ) : (
            <p className="mt-3 text-[12px] text-[#a15c00]">
              {__(
                "This needs the Hello Animation theme active. Go back a step to activate it, or skip — you can enable this later from the Theme Assets tab.",
                "animation-addons-for-elementor",
              )}
            </p>
          )}
        </div>
      )}

      {/* ---- STEP: WordPress assets ---- */}
      {step === "wp_assets" && (
        <div data-aae-wizard-step="wp_assets">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Trim WordPress core assets", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[12px] text-[var(--600,#525866)] mt-2">
            {__(
              "WordPress ships a few things every page rarely uses. These are safe on a visitor-facing site and recommended on. They apply to logged-out visitors only.",
              "animation-addons-for-elementor",
            )}
          </p>
          <div className="mt-3 divide-y divide-[#E1E4EA]">
            <ToggleRow
              label={__("Trim WordPress core assets", "animation-addons-for-elementor")}
              description={__("The master switch for this group.", "animation-addons-for-elementor")}
              checked={!!wp.enable}
              disabled={busy}
              onChange={(on) =>
                savePerf("wp_assets", {
                  enable: on,
                  // Turning the group on pre-selects the safe defaults.
                  ...(on
                    ? { emoji: true, jquery_migrate: true, block_assets: true, head_cleanup: true }
                    : {}),
                })
              }
            />
            <ToggleRow
              label={__("Remove the emoji script and styles (~25 KB)", "animation-addons-for-elementor")}
              checked={!!wp.emoji}
              disabled={busy || !wp.enable}
              onChange={(on) => savePerf("wp_assets", { emoji: on })}
            />
            <ToggleRow
              label={__("Remove jQuery Migrate (~13 KB)", "animation-addons-for-elementor")}
              checked={!!wp.jquery_migrate}
              disabled={busy || !wp.enable}
              onChange={(on) => savePerf("wp_assets", { jquery_migrate: on })}
            />
            <ToggleRow
              label={__("Remove block editor styles where Elementor paints the page", "animation-addons-for-elementor")}
              checked={!!wp.block_assets}
              disabled={busy || !wp.enable}
              onChange={(on) => savePerf("wp_assets", { block_assets: on })}
            />
            <ToggleRow
              label={__("Remove RSD, feed and generator tags", "animation-addons-for-elementor")}
              checked={!!wp.head_cleanup}
              disabled={busy || !wp.enable}
              onChange={(on) => savePerf("wp_assets", { head_cleanup: on })}
            />
          </div>
        </div>
      )}

      {/* ---- STEP: LiteSpeed ---- */}
      {step === "litespeed" && (
        <div data-aae-wizard-step="litespeed">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Page caching", "animation-addons-for-elementor")}
          </h3>
          <LiteSpeedAdvice cache={wizard.cache} />
        </div>
      )}

      {/* ---- STEP: done ---- */}
      {step === "done" && (
        <div data-aae-wizard-step="done">
          <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("You're set up", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[12px] text-[var(--600,#525866)] mt-2">
            {__(
              "Your pages now ship less. A few things to know:",
              "animation-addons-for-elementor",
            )}
          </p>
          <ul className="mt-3 space-y-2 text-[12px] text-[var(--600,#525866)] list-disc pl-4">
            <li>
              {__(
                "Every change here is reversible from the Performance tabs — nothing was deleted.",
                "animation-addons-for-elementor",
              )}
            </li>
            <li>
              {__(
                "Check your site in a private (logged-out) window: some optimizations only apply to visitors, so you will still see the old assets while logged in.",
                "animation-addons-for-elementor",
              )}
            </li>
            <li>
              {__(
                "If a page looks wrong, re-run this wizard and turn the matching switch back on.",
                "animation-addons-for-elementor",
              )}
            </li>
          </ul>
        </div>
      )}

      {/* ---- footer ---- */}
      <div className="flex items-center justify-between gap-3 mt-8">
        <Button
          variant="secondary"
          className="rounded-[8px]"
          disabled={busy}
          onClick={i === 0 ? onExit : back}
        >
          {i === 0 ? __("Close", "animation-addons-for-elementor") : __("Back", "animation-addons-for-elementor")}
        </Button>

        {isLast ? (
          <Button className="rounded-[8px]" disabled={busy} onClick={finish} data-aae-wizard-finish>
            {__("Finish", "animation-addons-for-elementor")}
          </Button>
        ) : (
          <Button className="rounded-[8px]" disabled={busy} onClick={next} data-aae-wizard-next>
            {__("Continue", "animation-addons-for-elementor")}
          </Button>
        )}
      </div>
    </div>
  );
};

/** Reuses the Cache_Advisor payload the Performance screen already gets. */
const LiteSpeedAdvice = ({ cache }) => {
  if (!cache?.headline) {
    return (
      <p className="text-[12px] text-[var(--600,#525866)] mt-2">
        {__(
          "A page-cache plugin is a separate speed-up on top of everything above. AAE supports LiteSpeed Cache best.",
          "animation-addons-for-elementor",
        )}
      </p>
    );
  }
  return (
    <div className="mt-2">
      <p className="text-[13px] font-semibold text-[var(--900,#181B25)]">{cache.headline}</p>
      <p className="text-[12px] text-[var(--600,#525866)] mt-1.5 leading-relaxed">{cache.body}</p>
      {cache.action && (
        <a
          href={cache.action.url}
          className="inline-block text-[12px] font-medium text-[#FC6848] underline mt-2.5"
          data-aae-wizard-cache-action={cache.action.kind}
        >
          {cache.action.label}
        </a>
      )}
    </div>
  );
};

export default PerformanceWizard;
