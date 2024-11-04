import ShowWidgets from "@/components/widgets/ShowWidgets";
import WidgetTopBar from "@/components/widgets/WidgetTopBar";
import { useState } from "react";
import { useSearchParams } from "react-router-dom";

const Widgets = () => {
  let [searchParams] = useSearchParams();

  const [searchKey, setSearchKey] = useState("");
  const [filterKey, setFilterKey] = useState("free-pro");

  return (
    <div className="min-h-screen px-8 py-6 border border-solid border-border rounded-2xl">
      <div className="pb-6 border-b border-solid border-border">
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
          searchParam={searchParams.get("tab")?.replace("/", "")}
        />
      </div>
    </div>
  );
};

export default Widgets;
