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

  const isAtomic = extensionSystem === "atomic";
  const showRevealLink = isAtomic && !v3TabVisible;

  return (
    <div
      className="min-h-screen px-8 py-6 border rounded-2xl"
      // Mirrors the Widgets page — see the note there.
      data-aae-system={extensionSystem}
    >
      <div className="pb-6 border-b flex flex-col gap-4">
        {/* Mirrors the Widgets page — see the notes there. */}
        {(showTabs || isAtomic) && (
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
              <div className="ms-auto flex items-center gap-4">
                {showRevealLink && <LegacyRevealLink onReveal={revealV3} />}
                <SettingsQuickLink />
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
  );
};

export default Extensions;
