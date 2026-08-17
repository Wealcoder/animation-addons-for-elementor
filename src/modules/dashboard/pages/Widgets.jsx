import ShowWidgets from "@/components/widgets/ShowWidgets";
import ShowAtomicWidgets from "@/components/widgets/ShowAtomicWidgets";
import WidgetTopBar from "@/components/widgets/WidgetTopBar";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { useAtomicWidgets } from "@/hooks/app.hooks";
import { countAtomicWidgets } from "@/lib/atomicWidgetService";
import { resolveSystems } from "@/lib/systemVisibility";
import LegacyRevealLink from "@/components/shared/LegacyRevealLink";
import SettingsQuickLink from "@/components/shared/SettingsQuickLink";
import V3InUseNotice from "@/components/shared/V3InUseNotice";

const Widgets = () => {
  const urlParams = new URLSearchParams(window.location.search);

  // Which of the two eras this site is offered, decided from the SAVED state
  // shipped with the page — see lib/systemVisibility.js for the rules and for
  // why it is a snapshot rather than live reducer state.
  const { showV3, showV4, system } = resolveSystems(urlParams.get("system"));

  const [widgetSystem, setWidgetSystem] = useState(system);

  // The V3 tab can be revealed at runtime by the Legacy (V3) link on the V4
  // view. Kept in state rather than re-resolved from the URL so the reveal is
  // instant; the URL is rewritten too, so a reload keeps it.
  const [v3Revealed, setV3Revealed] = useState(false);
  const v3TabVisible = showV3 || v3Revealed;
  const showTabs = v3TabVisible && showV4;

  const revealV3 = () => {
    const url = new URL(window.location.href);
    url.searchParams.set("system", "v3");
    window.history.replaceState({}, "", url);

    setV3Revealed(true);
    setWidgetSystem("v3");
  };

  const [searchKey, setSearchKey] = useState("");
  const [searchParam, setSearchParam] = useState("");
  const [filterKey, setFilterKey] = useState("free-pro");
  const [settingOpen, setSettingOpen] = useState(null);
  const [widgetCount, setWidgetCount] = useState(WCF_ADDONS_ADMIN.widgets);

  const [atomicSearchKey, setAtomicSearchKey] = useState("");
  const [atomicFilterKey, setAtomicFilterKey] = useState("free-pro");
  const { allAtomicWidgets } = useAtomicWidgets();
  const [atomicWidgetCount, setAtomicWidgetCount] = useState(
    countAtomicWidgets(allAtomicWidgets)
  );

  useEffect(() => {
    const tabValue = urlParams.get("cTab");
    if (tabValue) {
      setSearchParam(tabValue);
    }
    const filterValue = urlParams.get("filter");
    if (filterValue) {
      setFilterKey(filterValue);
    }

    const settingValue = urlParams.get("wiz_setting");
    if (settingValue) {
      setSettingOpen(settingValue);
    }

    // `system` is read once, into the initial state above — resolveSystems()
    // has to see it before the first render so the tab it names is actually
    // rendered rather than hidden by the visibility rules.
  }, [urlParams]);

  const isAtomic = widgetSystem === "atomic";

  // Only on the V4 view, and only while V3 is actually hidden — once the tab is
  // on screen the link has nothing left to do.
  const showRevealLink = isAtomic && !v3TabVisible;

  return (
    <div
      className="min-h-screen px-8 py-6 border rounded-2xl"
      // Which era's list is on screen. With the switcher hidden (one system
      // only) there is nothing else in the markup that says so, and "the right
      // list rendered" is the assertion that actually matters.
      data-aae-system={widgetSystem}
    >
      <div className="pb-6 border-b flex flex-col gap-4">
        {/*
          The row exists only when it has something in it — an empty div would
          still spend the parent's `gap-4` and push the top bar down.
        */}
        {(showTabs || isAtomic) && (
          <div className="flex items-center gap-4">
            {showTabs && (
              <Tabs value={widgetSystem} onValueChange={setWidgetSystem}>
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

            {/* V4 view only: reveal V3 (while it is hidden) and jump to Settings. */}
            {isAtomic && (
              <div className="ms-auto flex items-center gap-4">
                {showRevealLink && <LegacyRevealLink onReveal={revealV3} />}
                <SettingsQuickLink />
              </div>
            )}
          </div>
        )}

        {isAtomic ? (
          <>
            <WidgetTopBar
              system="atomic"
              filterKey={atomicFilterKey}
              setFilterKey={setAtomicFilterKey}
              searchKey={atomicSearchKey}
              setSearchKey={setAtomicSearchKey}
              widgetCount={atomicWidgetCount}
            />
            {/*
              Renders itself away when no v3 content is detected, so this is not
              a conditional the page has to keep in step with the rules.
            */}
            <V3InUseNotice />
          </>
        ) : (
          <WidgetTopBar
            filterKey={filterKey}
            setFilterKey={setFilterKey}
            searchKey={searchKey}
            setSearchKey={setSearchKey}
            widgetCount={widgetCount}
          />
        )}
      </div>
      <div className="mt-4">
        {isAtomic ? (
          <ShowAtomicWidgets
            filterKey={atomicFilterKey}
            searchKey={atomicSearchKey}
            setWidgetCount={setAtomicWidgetCount}
          />
        ) : (
          <ShowWidgets
            filterKey={filterKey}
            searchKey={searchKey}
            searchParam={searchParam}
            urlParams={urlParams}
            setWidgetCount={setWidgetCount}
            settingOpen={settingOpen}
          />
        )}
      </div>
    </div>
  );
};

export default Widgets;
