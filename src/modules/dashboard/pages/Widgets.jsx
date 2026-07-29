import ShowWidgets from "@/components/widgets/ShowWidgets";
import ShowAtomicWidgets from "@/components/widgets/ShowAtomicWidgets";
import WidgetTopBar from "@/components/widgets/WidgetTopBar";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { useAtomicWidgets } from "@/hooks/app.hooks";
import { countAtomicWidgets } from "@/lib/atomicWidgetService";

const Widgets = () => {
  const [widgetSystem, setWidgetSystem] = useState("atomic");

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

  const urlParams = new URLSearchParams(window.location.search);

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

    const systemValue = urlParams.get("system");
    if (systemValue === "v3" || systemValue === "atomic") {
      setWidgetSystem(systemValue);
    }
  }, [urlParams]);

  const isAtomic = widgetSystem === "atomic";

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b flex flex-col gap-4">
        <Tabs value={widgetSystem} onValueChange={setWidgetSystem}>
          <TabsList className="h-10 w-fit">
            <TabsTrigger value="atomic" className="px-4">
              {__("Elementor V4 (Atomic)", "animation-addons-for-elementor")}
            </TabsTrigger>
            <TabsTrigger value="v3" className="px-4">
              {__("Elementor V3", "animation-addons-for-elementor")}
            </TabsTrigger>
          </TabsList>
        </Tabs>

        {isAtomic ? (
          <WidgetTopBar
            system="atomic"
            filterKey={atomicFilterKey}
            setFilterKey={setAtomicFilterKey}
            searchKey={atomicSearchKey}
            setSearchKey={setAtomicSearchKey}
            widgetCount={atomicWidgetCount}
          />
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
