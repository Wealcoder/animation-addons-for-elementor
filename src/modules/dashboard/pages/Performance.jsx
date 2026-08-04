import FeaturePanel from "@/components/animation-settings/FeaturePanel";
import PerformanceWizard from "@/components/performance/PerformanceWizard";
import { InfoToggle } from "@/components/shared/InfoToggle";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { toast } from "sonner";

/**
 * Performance — how AAE's animation runtime is DELIVERED, as opposed to what it
 * does.
 *
 * This SCREEN is the free plugin's; everything behind it is Pro. The settings
 * store, the AJAX endpoints and the script-delivery pipeline all live in
 * pro/inc/Performance/, and the payload reaches us through the
 * `aae/performance/dashboard_payload` filter Pro answers. Without Pro that
 * payload is an empty array, which is exactly how this page knows to render the
 * locked state rather than an empty one.
 *
 * Panels come from the server schema through the same generic FeaturePanel the
 * Animation Settings screen uses, so a field added to Performance::schema()
 * appears here with no JS change — and one TAB per group, built from the same
 * list, so a group added server-side gets its tab for free too.
 *
 * Tabbed rather than side by side: each panel now carries display conditions,
 * which are two selects wide and a row per rule, so four of them on one screen
 * is a wall of controls that has to be read to be understood. One at a time,
 * named in a strip at the top, is the same arrangement Animation Settings uses
 * and the reason the screen fits on a laptop again.
 */
const LOCKED_FEATURES = [
  {
    label: __("Lazy Animation", "animation-addons-for-elementor"),
    description: __(
      "GSAP and ScrollTrigger alone are about 114 KB. Deferred, they are not fetched until an animated element is close to the viewport — so a page whose animations are all below the fold starts with none of it.",
      "animation-addons-for-elementor",
    ),
  },
  {
    label: __("Reduced Motion", "animation-addons-for-elementor"),
    description: __(
      "Visitors who have asked their operating system to reduce motion get no animation runtime at all — nothing plays, and nothing is downloaded.",
      "animation-addons-for-elementor",
    ),
  },
];

/**
 * What each panel does, keyed by its schema group.
 *
 * Rendered behind the ⓘ on the panel's own title rather than as a paragraph
 * under it: the point of the screen is seeing what is and isn't switched on, and
 * three explanations of that length between the panels bury it.
 *
 * Keyed, not a lazy_scripts/else ternary — that shape silently gave
 * `cache_compat` the reduced-motion text when the third group was added, and
 * would do it again for the fourth.
 */
const PANEL_NOTES = {
  lazy_scripts: __(
    "GSAP and ScrollTrigger alone are about 114 KB. With this on they are not fetched until an animated element is close to the viewport, so a page whose animations are all below the fold starts with none of it. An element whose script has not arrived yet simply renders without animating — it is never left invisible.",
    "animation-addons-for-elementor",
  ),
  reduced_motion: __(
    "Visitors who have asked their operating system to reduce motion get no animation runtime at all — nothing plays, and nothing is downloaded. Everyone else is unaffected.",
    "animation-addons-for-elementor",
  ),
  cache_compat: __(
    "Marks every image inside an animated element so cache plugins leave it alone. Those images then load normally instead of lazily, so keep the conditions narrow — the runtime already waits for a lazy image before animating it, and this is for the site that still has a problem.",
    "animation-addons-for-elementor",
  ),
  theme_assets: __(
    "On a page Elementor paints end to end, the theme's own CSS, fonts and jQuery render nothing — this stops them being loaded there. Only offered while the companion theme is active, and only where you allow it: exclude anything the theme still lays out itself, such as the blog, archives and search.",
    "animation-addons-for-elementor",
  ),
};

/**
 * Server × cache-plugin advice, from Pro's `Cache_Advisor`.
 *
 * Every sentence and the state word are decided server-side — the matrix is
 * server AND plugin, and splitting a matrix across PHP and React is how the two
 * halves end up disagreeing. This component renders; it decides nothing.
 *
 * It sits ABOVE the tabs rather than inside one, because it explains which tabs
 * are there: Cache Compatibility silently disappears without a cache plugin, and
 * a panel that vanishes with no explanation is the thing this answers.
 */
const STATE_STYLES = {
  good: "bg-[#eaf8ef] border-[#b7e4c7] text-[#0d5b2b]",
  warn: "bg-[#fff6e5] border-[#ffe0a3] text-[#7a4a00]",
  info: "bg-[#f4f6ff] border-[#ccd4ff] text-[#2c2c3a]",
};

const CacheAdvice = ({ cache }) => {
  if (!cache?.headline) return null;

  const tone = STATE_STYLES[cache.state] || STATE_STYLES.info;

  return (
    <div
      className={`rounded-lg border p-4 mt-4 ${tone}`}
      data-aae-cache-advice={cache.state}
    >
      <p className="text-[13px] font-semibold leading-snug">{cache.headline}</p>
      <p className="text-[12px] leading-relaxed mt-1.5 opacity-90">
        {cache.body}
      </p>

      {cache.action && (
        <a
          href={cache.action.url}
          className="inline-block text-[12px] font-medium underline mt-2.5"
          data-aae-cache-action={cache.action.kind}
        >
          {cache.action.label}
        </a>
      )}

      {/*
        What is running, and how well we handle each one. Only rendered when
        something IS running — an empty list under "No cache plugin detected"
        would be a heading over nothing.
      */}
      {!!cache.active?.length && (
        <ul className="mt-3 space-y-1.5 list-none">
          {cache.active.map((plugin) => (
            <li key={plugin.name} className="text-[12px] leading-relaxed">
              <span className="font-medium">{plugin.name}</span>
              {" — "}
              <span className="opacity-90">{plugin.note}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};

/**
 * Shown when Pro is absent. Deliberately describes the real features rather
 * than an empty frame: the reason to install Pro is the only useful thing this
 * screen can say without it.
 */
const LockedNotice = () => (
  <div className="flex flex-wrap gap-6 mt-6 items-start">
    {LOCKED_FEATURES.map((feature) => (
      <div
        key={feature.label}
        className="bg-background rounded-lg p-6 max-w-[420px]"
        data-aae-locked={feature.label}
      >
        <h3 className="flex items-center gap-2">
          <span className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {feature.label}
          </span>
          <Badge
            className="bg-[linear-gradient(109deg,#ffab472e_0%,#ffab472e_100%)] text-[#717784]"
            variant="pro"
          >
            {__("PRO", "animation-addons-for-elementor")}
          </Badge>
        </h3>

        <p className="text-[12px] text-[var(--600,#525866)] mt-3">
          {feature.description}
        </p>

        <p className="text-[12px] text-[var(--600,#525866)] mt-3">
          {__(
            "Available with Animation Addons Pro.",
            "animation-addons-for-elementor",
          )}
        </p>
      </div>
    ))}
  </div>
);
/**
 * @param {boolean} embedded Rendered inside another screen's tab, which already
 *                           has a page heading — so this one drops its own
 *                           rather than stacking two titles.
 */
const Performance = ({ embedded = false }) => {
  const boot = WCF_ADDONS_ADMIN?.performance || {};

  const [settings, setSettings] = useState(boot.settings || {});
  const [saving, setSaving] = useState(false);

  // Which group's panel is showing. Resolved against the schema below, so a
  // stale key can never blank the screen.
  const [active, setActive] = useState(null);
  const [introOpen, setIntroOpen] = useState(false);

  // The guided setup. Offered only on a v4 site (the payload decides), and
  // launched from the card below — see the wizard component.
  const wizard = boot.wizard || {};
  const [wizardOpen, setWizardOpen] = useState(false);
  const [wizardDone, setWizardDone] = useState(!!wizard.done);

  // The payload is Pro's to supply. No schema means no Pro, and the panels
  // below simply don't render — there is nothing to configure.
  const schema = boot.schema || {};
  const hasPro = Object.keys(schema).length > 0;

  /**
   * Commit a patch. Posts the WHOLE settings object built from the currently
   * committed state, so saving one panel can never write back a stale copy of
   * the other.
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
          action: "aae_save_performance_settings",
          settings: JSON.stringify(next),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });

      const body = await response.json();

      if (!body?.success) {
        throw new Error(body?.data || "save failed");
      }

      // Adopt the SERVER's copy — the sanitiser clamps the load-ahead distance
      // to its own range, and the panel should show what was actually stored.
      setSettings(body.data.settings);
      WCF_ADDONS_ADMIN.performance.settings = body.data.settings;

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

  /**
   * Save a WHOLE settings object (the wizard builds one and hands it over),
   * adopt the server's sanitised copy, and return it so the wizard can too.
   */
  const savePerfFull = async (nextAll) => {
    if (!WCF_ADDONS_ADMIN?.nonce || !WCF_ADDONS_ADMIN?.ajaxurl) return null;
    const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json" },
      credentials: "same-origin",
      body: new URLSearchParams({
        action: "aae_save_performance_settings",
        settings: JSON.stringify(nextAll),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    });
    const body = await response.json();
    if (!body?.success) throw new Error(body?.data || "save failed");
    setSettings(body.data.settings);
    WCF_ADDONS_ADMIN.performance.settings = body.data.settings;
    return body.data.settings;
  };

  /*
    The intro is split into its icon and its paragraph rather than handed to
    InfoNote whole, because the two do not sit together: embedded there is no
    page heading to put the ⓘ beside, so it joins the tab strip — while the
    paragraph it opens still belongs on its own full-width line under the row.
    A lone icon floating above the panel is the alternative, and it reads as a
    stray glyph rather than a control.
  */
  const introIcon = (
    <InfoToggle
      open={introOpen}
      onToggle={setIntroOpen}
      controls="aae-perf-intro"
      name={__("Performance", "animation-addons-for-elementor")}
      testid="performance.intro"
    />
  );

  const intro = introOpen && (
    <p
      id="aae-perf-intro"
      className="text-[12px] text-[var(--600,#525866)] mt-2 max-w-[720px]"
    >
      {__(
        "Everything here is off by default, because it changes when scripts run on a live site. Nothing removes an animation you have set up — it only changes when the code behind it is fetched.",
        "animation-addons-for-elementor",
      )}
    </p>
  );

  const title = !embedded && (
    <div className="flex items-center gap-1.5">
      <h2 className="text-[20px] font-medium text-[var(--900,#181B25)]">
        {__("Performance", "animation-addons-for-elementor")}
      </h2>
      {introIcon}
    </div>
  );

  if (!hasPro) {
    return (
      <div>
        {/* No panels to tab between, so the icon has nowhere else to go. */}
        {title || <div className="flex items-center">{introIcon}</div>}
        {intro}
        <LockedNotice />
      </div>
    );
  }

  // The wizard is offered only when the payload says the site is on v4. When
  // open it takes over the screen; the tabs are still there underneath on exit.
  const showWizardEntry = !!wizard.v4_active;

  if (wizardOpen) {
    return (
      <div>
        {title}
        <div className="mt-6">
          <PerformanceWizard
            wizard={{ ...wizard, cache: boot.cache }}
            perfSettings={settings}
            onSavePerf={savePerfFull}
            onDone={() => {
              setWizardDone(true);
              if (WCF_ADDONS_ADMIN?.performance?.wizard) {
                WCF_ADDONS_ADMIN.performance.wizard.done = true;
              }
              setWizardOpen(false);
            }}
            onExit={() => setWizardOpen(false)}
          />
        </div>
      </div>
    );
  }

  const groups = Object.entries(schema);

  // Fall back to the first group rather than trusting the stored key: the
  // schema is the server's and a group can disappear from it between renders
  // (Theme Assets is only offered while the companion theme is active), which
  // would otherwise leave the strip with nothing selected.
  const current = schema[active] ? active : groups[0][0];

  return (
    <div>
      <Tabs value={current} onValueChange={setActive}>
        <div className="flex flex-wrap items-center gap-4 justify-between">
          {title}

          <div className="flex items-center gap-2 ml-auto">
            {embedded && introIcon}

            <TabsList className="gap-1 h-11 flex-wrap">
              {groups.map(([key, group]) => (
                <TabsTrigger
                  key={key}
                  value={key}
                  className="data-[state=active]:bg-[#E1E4EA] bg-[#F5F7FA] text-[12px]"
                  sx={{ boxShadow: "none" }}
                  data-aae-perf-tab={key}
                >
                  {group?.label || key}
                </TabsTrigger>
              ))}
            </TabsList>
          </div>
        </div>

        {intro}

        {showWizardEntry && (
          <div
            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#E1E4EA] bg-[#FAFBFC] p-4 mt-4"
            data-aae-perf-wizard-entry
          >
            <div className="flex-1 min-w-[240px]">
              <p className="text-[13px] font-medium text-[var(--900,#181B25)]">
                {wizardDone
                  ? __("Performance setup", "animation-addons-for-elementor")
                  : __("Set up performance in a few steps", "animation-addons-for-elementor")}
              </p>
              <p className="text-[12px] text-[var(--600,#525866)] mt-1">
                {__(
                  "A guided walk-through: turn off the legacy layer, silence the theme's assets, trim WordPress, and set up caching. Every change is reversible.",
                  "animation-addons-for-elementor",
                )}
              </p>
            </div>
            <Button
              className="rounded-[8px]"
              variant={wizardDone ? "secondary" : "default"}
              onClick={() => setWizardOpen(true)}
              data-aae-perf-wizard-launch
            >
              {wizardDone
                ? __("Re-run setup", "animation-addons-for-elementor")
                : __("Start setup", "animation-addons-for-elementor")}
            </Button>
          </div>
        )}

        <CacheAdvice cache={boot.cache} />

        {groups.map(([key, group]) => (
          <TabsContent key={key} value={key} className="mt-6">
            <FeaturePanel
              feature={key}
              schema={group}
              value={settings[key] || {}}
              hasPro
              saving={saving}
              onSave={save}
              note={PANEL_NOTES[key]}
            />
          </TabsContent>
        ))}
      </Tabs>
    </div>
  );
};

export default Performance;
