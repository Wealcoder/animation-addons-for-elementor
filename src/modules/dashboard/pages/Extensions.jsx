import ExtensionTopBar from "@/components/extentions/ExtensionTopBar";
import ShowExtensions from "@/components/extentions/ShowExtensions";
import ShowAtomicExtensions from "@/components/extentions/ShowAtomicExtensions";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { useAtomicExtensions } from "@/hooks/app.hooks";
import { countAtomicExtensions } from "@/lib/atomicExtensionService";
import { resolveSystems } from "@/lib/systemVisibility";
import LegacyRevealLink from "@/components/shared/LegacyRevealLink";
import SettingsQuickLink from "@/components/shared/SettingsQuickLink";
import BackToV3Link from "@/components/shared/BackToV3Link";
import TryAtomicLink from "@/components/shared/TryAtomicLink";
import { SHOW_TRY_ATOMIC_LINK } from "@/lib/systemVisibility";
import UsageScanButton from "@/components/shared/UsageScanButton";
import AtomicOptInNotice from "@/components/shared/AtomicOptInNotice";
import AtomicUndoNotice from "@/components/shared/AtomicUndoNotice";
import { fetchWidgetUsage } from "@/lib/widgetUsage";
import { toast } from "sonner";

const Extensions = () => {
  const urlParams = new URLSearchParams(window.location.search);

  // Which of the two eras this site is offered — same snapshot the Widgets
  // page uses, so the two screens can never disagree. See lib/systemVisibility.js.
  const { showV3, showV4, system } = resolveSystems(urlParams.get("system"));

  const [extensionSystem, setExtensionSystem] = useState(system);

  // Mirrors the Widgets page — see the notes there.
  const [v3Revealed, setV3Revealed] = useState(false);
  const v3TabVisible = showV3 || v3Revealed;
  const showTabs = v3TabVisible && showV4;

  const revealV3 = () => {
    const url = new URL(window.location.href);
    url.searchParams.set("system", "v3");
    window.history.replaceState({}, "", url);

    setV3Revealed(true);
    setExtensionSystem("v3");
  };

  const searchParamTab = urlParams.get("cTab");
  const searchParamPluginId = urlParams.get("pluginId");
  const [extensionCount, setExtensionCount] = useState(
    WCF_ADDONS_ADMIN.extensions,
  );

  const [filterKey, setFilterKey] = useState("free-pro");

  const [atomicFilterKey, setAtomicFilterKey] = useState("free-pro");
  const { allAtomicExtensions } = useAtomicExtensions();
  const [atomicExtensionCount, setAtomicExtensionCount] = useState(
    countAtomicExtensions(allAtomicExtensions)
  );

  /*
   * Usage counts. Same scan the Widgets page runs — one request answers for
   * widgets and extensions, both eras — so pressing Usage here is the same
   * work, not extra work. Held per page rather than shared because each screen
   * is mounted on its own.
   *
   * V4 ONLY. A v3 extension leaves no signal a page can be counted by: it
   * writes control keys into some other widget's settings with no declared
   * owner, and the naming does not survive derivation. Rather than a count that
   * silently reads 0 for anything added after someone forgot a map entry, the
   * V3 view offers no button at all.
   */
  const [usage, setUsage] = useState(null);
  const [usageScanning, setUsageScanning] = useState(false);

  const scanUsage = async () => {
    setUsageScanning(true);
    try {
      setUsage(await fetchWidgetUsage());
    } catch (error) {
      // Leave any previous result on screen — a failed rescan should not wipe
      // numbers the user is reading.
      toast.error(
        __("Could not scan extension usage.", "animation-addons-for-elementor"),
        { position: "top-right" }
      );
    } finally {
      setUsageScanning(false);
    }
  };

  const isAtomic = extensionSystem === "atomic";
  const showRevealLink = isAtomic && !v3TabVisible;

  return (
    <>
      {/*
       * Only ever on a site mid-move to Elementor V4 — both render null the
       * rest of the time, and `empty:hidden` drops the wrapper's own margin
       * with them, so this changes nothing for every other site.
       *
       * OUTSIDE the bordered card, on purpose: the offer and its undo are about
       * this screen as a whole, not about the list's filters or tab strip.
       */}
      <div className="flex flex-col gap-4 mb-4 empty:hidden">
        <AtomicOptInNotice />
        <AtomicUndoNotice />
      </div>

      <div
        className="min-h-screen px-8 py-6 border rounded-2xl"
        // Mirrors the Widgets page — see the note there.
        data-aae-system={extensionSystem}
      >
        <div className="pb-6 border-b flex flex-col gap-4">
          {/* Mirrors the Widgets page — see the notes there. */}
          {/*
            SHOW_TRY_ATOMIC_LINK is the third reason this row can exist. A
            dismissed V3-only site has no switcher and is not on the V4 view,
            so without it the row is never built and the way back INTO V4 has
            nowhere to render — which is exactly how it shipped broken once.
          */}
          {(showTabs || isAtomic || SHOW_TRY_ATOMIC_LINK) && (
            <div className="flex items-center gap-4">
              {showTabs && (
                <Tabs value={extensionSystem} onValueChange={setExtensionSystem}>
                  <TabsList className="h-10 w-fit">
                    {showV4 && (
                      <TabsTrigger value="atomic" className="px-4">
                        {__(
                          "Elementor V4 (Atomic)",
                          "animation-addons-for-elementor"
                        )}
                      </TabsTrigger>
                    )}
                    {v3TabVisible && (
                      <TabsTrigger value="v3" className="px-4">
                        {__("Elementor V3", "animation-addons-for-elementor")}
                      </TabsTrigger>
                    )}
                  </TabsList>
                </Tabs>
              )}

              {isAtomic && (
                <div className="ms-auto flex items-center gap-3">
                  {showRevealLink && <LegacyRevealLink onReveal={revealV3} />}
                  <UsageScanButton
                    onScan={scanUsage}
                    scanning={usageScanning}
                    hasResult={!!usage}
                  />
                  {/* Mirrors the Widgets page — see the note there. */}
                  <BackToV3Link />
                  <SettingsQuickLink />
                </div>
              )}

              {/*
                The V3 view owns no link cluster here — the usage scan and the
                Settings shortcut are both V4-only by design — so the way back
                INTO V4 gets a minimal one of its own rather than being folded
                into a block gated on the era it exists to leave. `empty:hidden`
                because TryAtomicLink gates itself and is absent on almost every
                site, which would otherwise leave a bare `ms-auto` div behind.
              */}
              {!isAtomic && (
                <div className="ms-auto flex items-center gap-3 empty:hidden">
                  <TryAtomicLink />
                </div>
              )}
            </div>
          )}

          {isAtomic ? (
            <ExtensionTopBar
              system="atomic"
              filterKey={atomicFilterKey}
              setFilterKey={setAtomicFilterKey}
              extensionCount={atomicExtensionCount}
            />
          ) : (
            <ExtensionTopBar
              filterKey={filterKey}
              setFilterKey={setFilterKey}
              extensionCount={extensionCount}
            />
          )}
        </div>
        <div className="mt-4">
          {isAtomic ? (
            <ShowAtomicExtensions
              filterKey={atomicFilterKey}
              setExtensionCount={setAtomicExtensionCount}
              usage={usage?.extensions?.atomic || null}
            />
          ) : (
            <ShowExtensions
              filterKey={filterKey}
              tabParam={searchParamTab}
              pluginIdParam={searchParamPluginId}
              setExtensionCount={setExtensionCount}
            />
          )}
        </div>
      </div>
    </>
  );
};

export default Extensions;
