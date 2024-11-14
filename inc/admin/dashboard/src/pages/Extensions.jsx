import ExtensionTopBar from "@/components/extentions/ExtensionTopBar";
import ShowExtensions from "@/components/extentions/ShowExtensions";
import { useState } from "react";

const Extensions = () => {
  const urlParams = new URLSearchParams(window.location.search);

  const searchParamTab = urlParams.get("cTab");
  const searchParamPluginId = urlParams.get("pluginId");
  const [extensionCount, setExtensionCount] = useState(
    WCF_ADDONS_ADMIN.extensions
  );

  const [filterKey, setFilterKey] = useState("free-pro");

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <ExtensionTopBar
          setFilterKey={setFilterKey}
          filterKey={filterKey}
          extensionCount={extensionCount}
        />
      </div>
      <div className="mt-4">
        <ShowExtensions
          filterKey={filterKey}
          tabParam={searchParamTab}
          pluginIdParam={searchParamPluginId}
          setExtensionCount={setExtensionCount}
        />
      </div>
    </div>
  );
};

export default Extensions;
