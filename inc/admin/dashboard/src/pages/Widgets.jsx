import ShowWidgets from "@/components/widgets/ShowWidgets";
import WidgetTopBar from "@/components/widgets/WidgetTopBar";
import { useEffect, useState } from "react";

const Widgets = () => {
  const [searchKey, setSearchKey] = useState("");
  const [searchParam, setSearchParam] = useState("");
  const [filterKey, setFilterKey] = useState("free-pro");
  const [settingOpen, setSettingOpen] = useState(null);

  const [widgetCount, setWidgetCount] = useState(WCF_ADDONS_ADMIN.widgets);

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
  }, [urlParams]);

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <WidgetTopBar
          filterKey={filterKey}
          setFilterKey={setFilterKey}
          searchKey={searchKey}
          setSearchKey={setSearchKey}
          widgetCount={widgetCount}
        />
      </div>
      <div className="mt-4">
        <ShowWidgets
          filterKey={filterKey}
          searchKey={searchKey}
          searchParam={searchParam}
          urlParams={urlParams}
          setWidgetCount={setWidgetCount}
          settingOpen={settingOpen}
        />
      </div>
    </div>
  );
};

export default Widgets;
