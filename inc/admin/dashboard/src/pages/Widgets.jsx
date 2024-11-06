import ShowWidgets from "@/components/widgets/ShowWidgets";
import WidgetTopBar from "@/components/widgets/WidgetTopBar";
import { useEffect, useState } from "react";

const Widgets = () => {
  const [searchKey, setSearchKey] = useState("");
  const [searchParam, setSearchParam] = useState("");
  const [filterKey, setFilterKey] = useState("free-pro");

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("cTab");
    if (tabValue) {
      setSearchParam(tabValue);
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
        />
      </div>
      <div className="mt-4">
        <ShowWidgets
          filterKey={filterKey}
          searchKey={searchKey}
          searchParam={searchParam}
          urlParams={urlParams}
        />
      </div>
    </div>
  );
};

export default Widgets;
