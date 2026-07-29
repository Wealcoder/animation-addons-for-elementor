import { __ } from "@wordpress/i18n";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch, filterWidgets } from "@/lib/utils";
import {
  countAtomicExtensions,
  flattenAtomicExtensions,
} from "@/lib/atomicExtensionService";
import { useActiveItem, useAtomicExtensions } from "@/hooks/app.hooks";
import { toast } from "sonner";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";

const ShowAtomicExtensions = ({ filterKey, setExtensionCount }) => {
  const { allAtomicExtensions } = useAtomicExtensions();
  const { updateActiveAtomicExtension, updateActiveAtomicGroupExtension } =
    useActiveItem();

  const [tabValue, setTabValue] = useState("all");
  const [catExtensions, setCatExtensions] = useState({});

  const [extensionTabList, setExtensionTabList] = useState([]);

  useEffect(() => {
    if (allAtomicExtensions) {
      const result = [];
      for (let el in allAtomicExtensions.elements) {
        let data = {
          title: allAtomicExtensions.elements[el].title?.replace(
            "Extensions",
            ""
          ),
          value: el,
        };
        result.push(data);
      }

      setExtensionTabList(result);
    }
  }, [allAtomicExtensions]);

  useEffect(() => {
    if (allAtomicExtensions) {
      const result = filterWidgets(allAtomicExtensions.elements, filterKey);
      setCatExtensions(result);
    }
  }, [allAtomicExtensions, filterKey]);

  const setCheck = (data) => {
    updateActiveAtomicGroupExtension(data);
  };

  const saveExtension = async () => {
    const fields = flattenAtomicExtensions(allAtomicExtensions);

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "aae_save_atomic_extensions",
        fields: JSON.stringify(fields),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => response.json())
      .then(() => {
        setExtensionCount((prev) => ({
          ...prev,
          active: countAtomicExtensions(allAtomicExtensions).active,
        }));
        toast.success(__("Save Successful", "animation-addons-for-elementor"), {
          position: "top-right",
        });
      });
  };

  return (
    <Tabs defaultValue={"all"} value={tabValue} onValueChange={setTabValue}>
      <div className="flex justify-between items-center">
        <ScrollArea className="max-w-[500px] lg:max-w-[565px] xl:max-w-[900px] rounded-lg bg-background-secondary">
          <TabsList className="h-11">
            <TabsTrigger key={"all-atomic-extensions_tab"} value={"all"} className="px-4">
              {__("All", "animation-addons-for-elementor")}
            </TabsTrigger>

            {extensionTabList?.map((tab) => (
              <TabsTrigger key={tab.value} value={tab.value}>
                {tab.title}
              </TabsTrigger>
            ))}
          </TabsList>
          <ScrollBar orientation="horizontal" />
        </ScrollArea>

        <div className="flex gap-2.5 items-center justify-end">
          <Button onClick={() => saveExtension()}>{__("Save Settings", "animation-addons-for-elementor")}</Button>
        </div>
      </div>
      <TabsContent
        key={"all-atomic-extensions_content"}
        value={"all"}
        className="bg-background-secondary p-3 rounded-lg"
      >
        {Object.keys(catExtensions)?.map((tab) => (
            <div className="mt-3 first:mt-0" key={`all_group-${tab}`}>
              <div className="bg-background flex justify-between items-center p-5 rounded">
                <h3 className="text-base font-medium">
                  {catExtensions[tab].title}
                </h3>
                <div className="flex items-center space-x-2">
                  <Switch
                    id={tab}
                    checked={catExtensions[tab].is_active}
                    onCheckedChange={(value) => setCheck({ value, slug: tab })}
                  />
                  <Label htmlFor={tab}>{__("Enable All", "animation-addons-for-elementor")}</Label>
                </div>
              </div>
              <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
                {Object.keys(catExtensions[tab].elements)?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard
                      widget={catExtensions[tab].elements[content]}
                      slug={content}
                      updateActiveItem={updateActiveAtomicExtension}
                      className="rounded p-5"
                    />
                  </React.Fragment>
                ))}
                {Array.from({
                  length:
                    deviceMediaMatch() -
                    (Object.keys(catExtensions[tab].elements)?.length %
                      deviceMediaMatch() ===
                    0
                      ? deviceMediaMatch()
                      : Object.keys(catExtensions[tab].elements)?.length %
                        deviceMediaMatch()),
                }).map((_, index) => (
                  <WidgetCard
                    key={`tab_content_empty-${index}`}
                    className="rounded"
                  />
                ))}
              </div>
            </div>
          ))}
      </TabsContent>
      {Object.keys(catExtensions)?.map((tab) => (
        <TabsContent
          key={tab}
          value={tab}
          className="bg-background-secondary p-3 rounded-lg"
        >
          <div>
            <div className="bg-background flex justify-between items-center p-5 rounded">
              <h3 className="text-base font-medium">{catExtensions[tab].title}</h3>
              <div className="flex items-center space-x-2">
                <Switch
                  id={tab}
                  checked={catExtensions[tab].is_active}
                  onCheckedChange={(value) => setCheck({ value, slug: tab })}
                />
                <Label htmlFor={tab}>{__("Enable All", "animation-addons-for-elementor")}</Label>
              </div>
            </div>
            <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
              {Object.keys(catExtensions[tab].elements)?.map((content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={catExtensions[tab].elements[content]}
                    slug={content}
                    updateActiveItem={updateActiveAtomicExtension}
                    className="rounded p-5"
                  />
                </React.Fragment>
              ))}
              {Array.from({
                length:
                  deviceMediaMatch() -
                  (Object.keys(catExtensions[tab].elements)?.length %
                    deviceMediaMatch() ===
                  0
                    ? deviceMediaMatch()
                    : Object.keys(catExtensions[tab].elements)?.length %
                      deviceMediaMatch()),
              }).map((_, index) => (
                <WidgetCard
                  key={`tab_content_empty-${index}`}
                  className="rounded"
                />
              ))}
            </div>
          </div>
        </TabsContent>
      ))}
    </Tabs>
  );
};

export default ShowAtomicExtensions;
