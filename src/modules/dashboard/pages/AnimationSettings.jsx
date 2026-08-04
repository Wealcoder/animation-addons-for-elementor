import FeaturePanel from "@/components/animation-settings/FeaturePanel";
import InfoNote from "@/components/shared/InfoToggle";
import ShowIntegrationsLibrary from "@/components/integrations/ShowIntegrationsLibrary";
import Performance from "@/pages/Performance";
import { Switch } from "@/components/ui/switch";
import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { RiSettings3Line } from "react-icons/ri";
import { toast } from "sonner";

/**
 * Animation Settings — the dashboard home for AAE's site-wide animation chrome.
 *
 * Most of these features previously lived only as Elementor Site Settings tabs
 * in the Pro plugin (v3, stored in the Kit). This screen is the v4 replacement
 * and writes to its own `aae_animation_settings` option; nothing here touches
 * the Kit. See inc/AnimationSettings/class-animation-settings.php for how the
 * two systems are kept from colliding.
 *
 * Smooth Scroll is the exception and came from somewhere else: its v3 form is
 * the Smooth Scroller EXTENSION, not a Site Settings tab. It leads the list
 * because it governs how the whole page moves, which is a bigger decision than
 * any of the individual pieces of chrome that follow it.
 *
 * Every panel is rendered by the generic FeaturePanel from the schema the
 * server ships, so there is no per-feature React here — adding a field to
 * Animation_Settings::schema() is enough. The left menu's order follows the
 * old Site Settings list users already know.
 *
 * Popup is the odd one out and has no panel here on purpose: it already has a
 * full v4 implementation of its own (the AAE Popup builder — a template type
 * with its own editor and settings modal), so duplicating a thin settings panel
 * for it would create a second, weaker place to configure the same feature.
 */
const SECTIONS = [
  { id: "smooth-scroll", schemaKey: "smooth_scroll", label: "Smooth Scroll" },
  { id: "preloader", schemaKey: "preloader", label: "Preloader" },
  { id: "cursor", schemaKey: "cursor", label: "Cursor" },
  { id: "scroll-to-top", schemaKey: "scroll_to_top", label: "Scroll to Top" },
  { id: "scroll-indicator", schemaKey: "scroll_indicator", label: "Scroll Indicator" },
  { id: "popup", schemaKey: null, label: "Popup" },
  { id: "performance", schemaKey: null, label: "Performance" },
  /*
   * The GSAP Library screen, moved in from the Integrations page (its old
   * `?tab=integrations` URL stays routable for bookmarks, same as
   * Performance's). Pro feeds the whole section through the dashboard config
   * filter, so with Pro absent there is no data to render and the tab is
   * filtered out below rather than shown empty.
   */
  { id: "library", schemaKey: null, label: "Library" },
];

const HAS_LIBRARY = !!(
  typeof WCF_ADDONS_ADMIN !== "undefined" &&
  WCF_ADDONS_ADMIN?.addons_config?.integrations?.library
);

const VISIBLE_SECTIONS = SECTIONS.filter(
  (item) => item.id !== "library" || HAS_LIBRARY
);

const PopupNotice = () => (
  <div className="bg-background rounded-lg p-10 max-w-[560px]">
    <h3 className="text-[16px] font-medium text-[var(--900,#181B25)]">
      {__("AAE Popup", "animation-addons-for-elementor")}
    </h3>
    <p className="text-sm text-text-secondary mt-3">
      {__(
        "Popups already have a full v4 builder — create one under Templates, then set its triggers and appearance from the popup's own settings.",
        "animation-addons-for-elementor",
      )}
    </p>
    <p className="text-[12px] text-[var(--600,#525866)] mt-3">
      {__(
        "There is nothing to configure here, so this tab intentionally has no panel.",
        "animation-addons-for-elementor",
      )}
    </p>
  </div>
);

const AnimationSettings = () => {
  const boot = WCF_ADDONS_ADMIN?.animation_settings || {};

  const [section, setSection] = useState(SECTIONS[0].id);
  const [settings, setSettings] = useState(boot.settings || {});
  const [saving, setSaving] = useState(false);

  const schema = boot.schema || {};
  const globals = boot.global_colors || [];
  const hasPro = !!boot.has_pro;

  /**
   * Commit a patch. Always posts the WHOLE settings object built from the
   * current committed state, so a save from one panel can never write back a
   * stale copy of another panel's value.
   */
  const save = async (patch) => {
    if (!WCF_ADDONS_ADMIN?.nonce || !WCF_ADDONS_ADMIN?.ajaxurl) return;

    const next = { ...settings, ...patch };
    setSaving(true);

    try {
      const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        credentials: "same-origin",
        body: new URLSearchParams({
          action: "aae_save_animation_settings",
          settings: JSON.stringify(next),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });

      const body = await response.json();

      if (!body?.success) {
        throw new Error(body?.data || "save failed");
      }

      // Adopt the SERVER's copy, not the optimistic one — the sanitizer may
      // have corrected a layout or colour, and the panel should show what was
      // actually stored.
      setSettings(body.data.settings);
      WCF_ADDONS_ADMIN.animation_settings.settings = body.data.settings;

      toast.success(__("Settings saved", "animation-addons-for-elementor"), {
        position: "top-right",
      });
    } catch (error) {
      toast.error(
        __("Could not save settings", "animation-addons-for-elementor"),
        { position: "top-right", description: String(error?.message || error) },
      );
    } finally {
      setSaving(false);
    }
  };

  const legacyOn = !!settings.legacy_v3;

  const active =
    VISIBLE_SECTIONS.find((item) => item.id === section) || VISIBLE_SECTIONS[0];

  return (
    /*
     * Sidebar layout (2026-08-04, design): the section nav is a LEFT menu in
     * its own card, the active panel renders beside it. Below `lg` the menu
     * card stacks on top and its items wrap as a horizontal pill row — a
     * 240px column would eat most of a phone's width.
     */
    <div className="flex flex-col lg:flex-row items-start gap-6">
      <aside className="bg-background rounded-lg p-3 w-full lg:w-[248px] lg:shrink-0">
        <div className="flex items-center gap-2 px-3 pt-2 pb-3">
          <RiSettings3Line size={18} className="text-[var(--900,#181B25)]" />
          <h2 className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {__("Settings", "animation-addons-for-elementor")}
          </h2>
        </div>

        <nav className="flex flex-row flex-wrap lg:flex-col gap-1">
          {VISIBLE_SECTIONS.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setSection(item.id)}
              aria-current={section === item.id ? "true" : undefined}
              data-aae-settings-nav={item.id}
              // bg-transparent is load-bearing on the inactive branch: the
              // admin page's base styles paint <button> gray, which made every
              // item look active.
              className={`text-left rounded-lg border-0 px-3 py-2.5 text-[13px] transition-colors cursor-pointer ${
                section === item.id
                  ? "bg-[#F1F3F6] font-medium text-[var(--900,#181B25)]"
                  : "bg-transparent text-[var(--600,#525866)] hover:bg-[#F5F7FA] hover:text-[var(--900,#181B25)]"
              }`}
            >
              {__(item.label, "animation-addons-for-elementor")}
            </button>
          ))}
        </nav>
      </aside>

      <div className="flex-1 min-w-0 w-full">
        {active.schemaKey && schema[active.schemaKey] ? (
          <FeaturePanel
            feature={active.schemaKey}
            schema={schema[active.schemaKey]}
            value={settings[active.schemaKey] || {}}
            globals={globals}
            hasPro={hasPro}
            saving={saving}
            onSave={save}
          />
        ) : active.id === "performance" ? (
          /*
           * The whole screen, embedded. It keeps its own state, its own
           * schema (which comes from Pro through a filter, not from
           * `animation_settings`) and its own save endpoint — sharing this
           * page's would mean two sources of truth for one option.
           */
          <Performance embedded />
        ) : active.id === "library" ? (
          // Same arrangement as Performance: own store slice (allLibrary
          // through the app context), own save endpoint, own Save button.
          <ShowIntegrationsLibrary embedded />
        ) : (
          <PopupNotice />
        )}

        {/*
          Legacy escape hatch. Lives in the content column, below whichever
          panel is open, because it governs all five features at once — and
          outside the Preloader form because a switch this consequential
          should take effect the moment it is flipped rather than waiting on
          a Save the user may not press.

          Default is decided per site by detect_legacy_usage(): sites with v3
          chrome already in their Kit keep the tabs, brand-new sites never see
          them. This switch is how the many existing v3 users get them back if
          the detection guessed wrong for them.
        */}
        <div
          className="bg-background rounded-lg p-6 mt-6"
          // Hidden on the Performance section: that one governs how scripts
          // are DELIVERED and has nothing to do with the v3 chrome surface, so
          // leaving this sitting under it reads as if the switch applied there.
          hidden={section === "performance"}
        >
          <div className="flex flex-row items-center justify-between gap-4">
            <InfoNote
              // flex-1 so the disclosed paragraphs get the row's width instead
              // of wrapping into a column as narrow as the heading beside the
              // switch.
              className="flex-1 min-w-[260px]"
              label={__(
                "Legacy (v3) features",
                "animation-addons-for-elementor",
              )}
              labelClassName="text-[14px] font-medium text-[var(--900,#181B25)]"
              testid="legacy_v3.help"
            >
              <p>
                {__(
                  "Keeps the older AAE widgets in the Elementor widget panel, and the AAE Preloader, Cursor, Scroll to Top, Scroll Indicator and Popup tabs in Elementor → Site Settings.",
                  "animation-addons-for-elementor",
                )}
              </p>
              <p>
                {__(
                  "Turning this off only hides them. Pages that already use an older widget keep rendering exactly as before, and anything configured in those tabs keeps working — nothing is deleted or switched off.",
                  "animation-addons-for-elementor",
                )}
              </p>
            </InfoNote>

            <Switch
              checked={legacyOn}
              disabled={saving}
              onCheckedChange={(next) => save({ legacy_v3: next })}
              sx={{ marginTop: "0" }}
              data-aae-legacy-toggle
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default AnimationSettings;
