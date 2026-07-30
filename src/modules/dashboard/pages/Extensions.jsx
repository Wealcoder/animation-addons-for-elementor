import ExtensionTopBar from "@/components/extentions/ExtensionTopBar";
import ShowExtensions from "@/components/extentions/ShowExtensions";
import ShowAtomicExtensions from "@/components/extentions/ShowAtomicExtensions";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { useAtomicExtensions } from "@/hooks/app.hooks";
import { countAtomicExtensions } from "@/lib/atomicExtensionService";

const Extensions = () => {
  const [extensionSystem, setExtensionSystem] = useState("atomic");

  const urlParams = new URLSearchParams(window.location.search);

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

  useEffect(() => {
    const systemValue = urlParams.get("system");
    if (systemValue === "v3" || systemValue === "atomic") {
      setExtensionSystem(systemValue);
    }
  }, [urlParams]);

  const isAtomic = extensionSystem === "atomic";

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b flex flex-col gap-4">
        <Tabs value={extensionSystem} onValueChange={setExtensionSystem}>
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
