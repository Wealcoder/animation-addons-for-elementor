import ExtensionTopBar from "@/components/extentions/ExtensionTopBar";
import ShowExtensions from "@/components/extentions/ShowExtensions";
import { useState } from "react";

const Extensions = () => {
  // let [searchParams] = useSearchParams();

  // const searchParamTab = searchParams.get("tab")?.replace("/", "");
  // const searchParamPluginId = searchParams.get("pluginId")?.replace("/", "");

  const searchParamTab = "";
  const searchParamPluginId = "";

  const [filterKey, setFilterKey] = useState("free-pro");

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <ExtensionTopBar setFilterKey={setFilterKey} filterKey={filterKey} />
      </div>
      <div className="mt-4">
        <ShowExtensions
          filterKey={filterKey}
          tabParam={searchParamTab}
          pluginIdParam={searchParamPluginId}
        />
      </div>
    </div>
  );
};

export default Extensions;
