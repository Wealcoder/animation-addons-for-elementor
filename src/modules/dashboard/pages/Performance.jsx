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
 * It lives in the Diagnostics tab alongside ServerHealth. It used to sit above
 * the tab strip — it explains why Cache Compatibility disappears without a cache
 * plugin, and a panel that vanishes unexplained is what this answers — but a
 * report that pushes every actual setting below the fold costs more than that
 * explanation is worth. The dot on the Diagnostics trigger is what carries the
 * "something needs your attention" signal now.
 */
const STATE_STYLES = {
  good: "bg-[#eaf8ef] border-[#b7e4c7] text-[#0d5b2b]",
  warn: "bg-[#fff6e5] border-[#ffe0a3] text-[#7a4a00]",
  info: "bg-[#f4f6ff] border-[#ccd4ff] text-[#2c2c3a]",
};

/** Per-row dot colour. The card's own tone is the worst row's tone (PHP decides). */
const ROW_DOT = {
  good: "bg-[#2f9e5e]",
  warn: "bg-[#d98324]",
  info: "bg-[#5566d6]",
};

/**
 * PHP-runtime health, from Pro's `Server_Advisor`.
 *
 * Sits under CacheAdvice because it answers the layer below it: a page cache
 * removes most server time, and this is what is left once PHP has to run.
 *
 * EVERYTHING here is decided server-side — the state word, the copy, and the
 * per-host instructions. This component renders and decides nothing, for the
 * same reason CacheAdvice does: the rules are a matrix (host × setting × what we
 * can actually read), and splitting a matrix across PHP and React is how the two
 * halves end up contradicting each other on the same screen.
 *
 * There is deliberately NO "enable OPcache" button. OPcache is a zend_extension
 * loaded before WordPress exists; no plugin can switch it on, and a button that
 * pretended otherwise would be a lie. The one thing we can genuinely do — flush
 * already-compiled code — is offered separately and only when the server allows
 * it.
 */
const ServerHealth = ({ server, onReset, resetting, resetNote }) => {
  const [openRow, setOpenRow] = useState(null);

  if (!server?.rows?.length) return null;

  const tone = STATE_STYLES[server.state] || STATE_STYLES.info;

  return (
    <div
      className={`rounded-lg border p-4 mt-4 ${tone}`}
      data-aae-server-advice={server.state}
    >
      <p className="text-[13px] font-semibold leading-snug">{server.headline}</p>
      <p className="text-[12px] leading-relaxed mt-1.5 opacity-90">{server.body}</p>

      <ul className="mt-3 space-y-1 list-none">
        {server.rows.map((row) => {
          const expandable = !!(row.why || row.steps?.length || row.snippet?.length);
          const open = openRow === row.id;

          return (
            <li key={row.id} data-aae-server-row={row.id} data-state={row.state}>
              <button
                type="button"
                disabled={!expandable}
                onClick={() => setOpenRow(open ? null : row.id)}
                className={`w-full flex items-center gap-2 text-left text-[12px] py-1 ${
                  expandable ? "cursor-pointer" : "cursor-default"
                }`}
                aria-expanded={expandable ? open : undefined}
              >
                <span
                  className={`w-1.5 h-1.5 rounded-full shrink-0 ${
                    ROW_DOT[row.state] || ROW_DOT.info
                  }`}
                  aria-hidden="true"
                />
                <span className="font-medium">{row.label}</span>
                <span className="opacity-80">{row.value}</span>
                {expandable && (
                  <span className="ml-auto opacity-60" aria-hidden="true">
                    {open ? "−" : "+"}
                  </span>
                )}
              </button>

              {open && (
                <div className="pl-3.5 pb-2 text-[12px] leading-relaxed">
                  {row.why && <p className="opacity-90">{row.why}</p>}

                  {!!row.steps?.length && (
                    <ol className="list-decimal ml-4 mt-1.5 space-y-0.5 opacity-90">
                      {row.steps.map((step, i) => (
                        <li key={i}>{step}</li>
                      ))}
                    </ol>
                  )}

                  {/*
                    The settings to paste. Shown only where they mean something —
                    a snippet under "PHP version" would be noise.
                  */}
                  {!!row.snippet?.length && (
                    <pre className="mt-2 p-2 rounded bg-black/5 overflow-x-auto text-[11px] leading-relaxed whitespace-pre">
                      {row.snippet.join("\n")}
                    </pre>
                  )}
                </div>
              )}
            </li>
          );
        })}
      </ul>

      {/*
        Which php.ini this PHP actually read. "Add this to php.ini" is useless
        advice when a server has several and the obvious one is not the live one,
        so the path is stated rather than implied.
      */}
      {server.ini?.loaded && (
        <p className="text-[11px] mt-3 opacity-70 break-all">
          Loaded php.ini: <code>{server.ini.loaded}</code>
          {!!server.ini.scanned?.length && (
            <>
              {" "}
              (+{server.ini.scanned.length} more scanned)
            </>
          )}
        </p>
      )}

      {server.canReset && (
        <div className="mt-3">
          <button
            type="button"
            onClick={onReset}
            disabled={resetting}
            className="text-[12px] font-medium underline disabled:opacity-60"
            data-aae-server-action="opcache-reset"
          >
            {resetting ? "Flushing…" : "Flush compiled code cache"}
          </button>
          <p className="text-[11px] mt-1 opacity-70">
            Only useful right after deploying code. On shared PHP-FPM this clears
            the cache for every site in the pool, and they all recompile.
          </p>
          {resetNote && <p className="text-[11px] mt-1 font-medium">{resetNote}</p>}
        </div>
      )}
    </div>
  );
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

  /**
   * Flush OPcache on request.
   *
   * Same contract as every other call on this screen (nonce + admin-ajax). The
   * outcome is reported inline rather than as a toast because it is a statement
   * about the server that the user may want to read twice — and because a
   * refusal here is usually opcache.restrict_api, which needs explaining, not a
   * disappearing "failed".
   */
  const [opcacheResetting, setOpcacheResetting] = useState(false);
  const [opcacheNote, setOpcacheNote] = useState("");

  const resetOpcache = async () => {
    if (!WCF_ADDONS_ADMIN?.nonce || !WCF_ADDONS_ADMIN?.ajaxurl) return;

    setOpcacheResetting(true);
    setOpcacheNote("");

    try {
      const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json" },
        credentials: "same-origin",
        body: new URLSearchParams({
          action: "aae_server_opcache_reset",
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });
      const body = await response.json();
      setOpcacheNote(body?.data?.message || (body?.success ? "Done." : "Could not flush."));
    } catch (e) {
      setOpcacheNote("Could not reach the server.");
    } finally {
      setOpcacheResetting(false);
    }
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

  /*
    Diagnostics gets its OWN tab rather than sitting above the strip.

    Floating above, the two cards pushed every actual setting below the fold —
    and they are a report, not a control, so they do not need to be on screen
    while someone is toggling Lazy Animation. Its key is deliberately prefixed
    so it can never collide with a group name coming from the server schema.

    It is NOT the default tab. This screen is where people come to change a
    setting, and opening onto a page of server warnings would put a wall in
    front of that. The dot on the trigger is how a problem still announces
    itself without hijacking the screen.
  */
  const DIAGNOSTICS_TAB = "__diagnostics";

  const diagnosticsState = boot.server?.rows?.length
    ? boot.server.state
    : boot.cache?.state;
  const hasDiagnostics = !!(boot.cache?.headline || boot.server?.rows?.length);

  // Fall back to the first group rather than trusting the stored key: the
  // schema is the server's and a group can disappear from it between renders
  // (Theme Assets is only offered while the companion theme is active), which
  // would otherwise leave the strip with nothing selected.
  const current =
    active === DIAGNOSTICS_TAB && hasDiagnostics ? DIAGNOSTICS_TAB : schema[active] ? active : groups[0][0];

  return (
    <div>
      <Tabs value={current} onValueChange={setActive}>
        <div className="flex flex-wrap items-center gap-4 justify-between">
          {title}

          <div className="flex items-center gap-2 ml-auto">
            {embedded && introIcon}

            <TabsList className="gap-1 h-11 flex-wrap">
              {hasDiagnostics && (
                <TabsTrigger
                  value={DIAGNOSTICS_TAB}
                  className="data-[state=active]:bg-[#E1E4EA] bg-[#F5F7FA] text-[12px]"
                  sx={{ boxShadow: "none" }}
                  data-aae-perf-tab={DIAGNOSTICS_TAB}
                  data-aae-diagnostics-state={diagnosticsState}
                >
                  {__("Diagnostics", "animation-addons-for-elementor")}
                  {/*
                    A problem has to be visible from a tab the user is not on,
                    or the report only reaches people who already went looking.
                    A dot does that without a number to argue about.
                  */}
                  {(diagnosticsState === "warn" || diagnosticsState === "info") && (
                    <span
                      className={`inline-block w-1.5 h-1.5 rounded-full ml-1.5 align-middle ${
                        ROW_DOT[diagnosticsState]
                      }`}
                      aria-hidden="true"
                    />
                  )}
                </TabsTrigger>
              )}

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

        {hasDiagnostics && (
          <TabsContent value={DIAGNOSTICS_TAB} className="mt-6" data-aae-diagnostics-panel>
            {/*
              Cache advice first: a page cache removes most server time
              outright, so it is the thing to get right before anything below it
              matters. Server health is what is left once PHP does have to run.
            */}
            <CacheAdvice cache={boot.cache} />

            <ServerHealth
              server={boot.server}
              onReset={resetOpcache}
              resetting={opcacheResetting}
              resetNote={opcacheNote}
            />
          </TabsContent>
        )}

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
