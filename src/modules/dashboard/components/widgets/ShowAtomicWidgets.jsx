import { __ } from "@wordpress/i18n";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch, filterWidgets } from "@/lib/utils";
import {
  countAtomicWidgets,
  flattenAtomicWidgets,
} from "@/lib/atomicWidgetService";
import { useActiveItem, useAtomicWidgets } from "@/hooks/app.hooks";
import { toast } from "sonner";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";

const ShowAtomicWidgets = ({ searchKey, filterKey, setWidgetCount }) => {
  const { allAtomicWidgets } = useAtomicWidgets();
  const { updateActiveAtomicWidget, updateActiveAtomicGroupWidget } =
    useActiveItem();

  const [tabValue, setTabValue] = useState("all");
  const [catWidgets, setCatWidgets] = useState({});
  const [norResult, setNoResult] = useState(false);

  const [widgetTabList, setWidgetTabList] = useState([]);

  useEffect(() => {
    if (allAtomicWidgets) {
      const result = [];
      for (let el in allAtomicWidgets.elements) {
        let data = {
          title: allAtomicWidgets.elements[el].title?.replace("Widgets", ""),
          value: el,
        };
        result.push(data);
      }

      setWidgetTabList(result);
    }
  }, [allAtomicWidgets]);

  useEffect(() => {
    if (allAtomicWidgets) {
      if (searchKey) {
        const searchResult = findSearchResult();
        if (!(searchResult && Object.keys(searchResult).length)) {
          setNoResult(true);
        } else {
          setNoResult(false);
        }
        const result = filterWidgets(searchResult, filterKey);
        setCatWidgets(result);
      } else {
        setNoResult(false);
        const result = filterWidgets(allAtomicWidgets.elements, filterKey);
        setCatWidgets(result);
      }
    }
  }, [allAtomicWidgets, filterKey, searchKey]);

  useEffect(() => {
    if (searchKey) {
      setTabValue("all");
    }
  }, [searchKey]);

  const findSearchResult = () => {
    const result = Object.fromEntries(
      Object.entries(allAtomicWidgets.elements)
        .map(([key, value]) => {
          const filteredElements = Object.fromEntries(
            Object.entries(value.elements || {}).filter(([key2, value2]) =>
              value2.label.toLowerCase().includes(searchKey.toLowerCase())
            )
          );

          return [key, { ...value, elements: filteredElements }];
        })
        .filter(([key, value]) => Object.keys(value.elements).length > 0)
    );

    return result;
  };

  const setCheck = (data) => {
    updateActiveAtomicGroupWidget(data);
  };

  const saveWidget = async () => {
    const fields = flattenAtomicWidgets(allAtomicWidgets);

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "aae_save_atomic_widgets",
        fields: JSON.stringify(fields),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => response.json())
      .then(() => {
        // The server total also counts "Main"/internal widgets hidden from
        // this list (see get_internal_widget_slugs()), so derive the visible
        // active count from what's actually rendered instead of trusting it.
        setWidgetCount((prev) => ({
          ...prev,
          active: countAtomicWidgets(allAtomicWidgets).active,
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
            <TabsTrigger key={"all-atomic-widgets_tab"} value={"all"} className="px-4">
              {__("All", "animation-addons-for-elementor")}
            </TabsTrigger>

            {widgetTabList?.map((tab) => (
              <TabsTrigger key={tab.value} value={tab.value}>
                {tab.title}
              </TabsTrigger>
            ))}
          </TabsList>
          <ScrollBar orientation="horizontal" />
        </ScrollArea>

        <div className="flex gap-2.5 items-center justify-end">
          <Button onClick={() => saveWidget()}>{__("Save Settings", "animation-addons-for-elementor")}</Button>
        </div>
      </div>
      <TabsContent
        key={"all-atomic-widgets_content"}
        value={"all"}
        className="bg-background-secondary p-3 rounded-lg"
      >
        {norResult ? (
          <div className="bg-background flex justify-center items-center p-5 rounded">
            <h3 className="text-base font-medium">{__("No Result Found", "animation-addons-for-elementor")}</h3>
          </div>
        ) : (
          Object.keys(catWidgets)?.map((tab) => (
            <div className="mt-3 first:mt-0" key={`all_group-${tab}`}>
              <div className="bg-background flex justify-between items-center p-5 rounded">
                <h3 className="text-base font-medium">
                  {catWidgets[tab].title}
                </h3>
                <div className="flex items-center space-x-2">
                  <Switch
                    id={tab}
                    checked={catWidgets[tab].is_active}
                    onCheckedChange={(value) => setCheck({ value, slug: tab })}
                  />
                  <Label htmlFor={tab}>{__("Enable All", "animation-addons-for-elementor")}</Label>
                </div>
              </div>
              <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
                {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard
                      widget={catWidgets[tab].elements[content]}
                      slug={content}
                      updateActiveItem={updateActiveAtomicWidget}
                      className="rounded p-5"
                    />
                  </React.Fragment>
                ))}
                {Array.from({
                  length:
                    deviceMediaMatch() -
                    (Object.keys(catWidgets[tab].elements)?.length %
                      deviceMediaMatch() ===
                    0
                      ? deviceMediaMatch()
                      : Object.keys(catWidgets[tab].elements)?.length %
                        deviceMediaMatch()),
                }).map((_, index) => (
                  <WidgetCard
                    key={`tab_content_empty-${index}`}
                    className="rounded"
                  />
                ))}
              </div>
            </div>
          ))
        )}
      </TabsContent>
      {Object.keys(catWidgets)?.map((tab) => (
        <TabsContent
          key={tab}
          value={tab}
          className="bg-background-secondary p-3 rounded-lg"
        >
          <div>
            <div className="bg-background flex justify-between items-center p-5 rounded">
              <h3 className="text-base font-medium">{catWidgets[tab].title}</h3>
              <div className="flex items-center space-x-2">
                <Switch
                  id={tab}
                  checked={catWidgets[tab].is_active}
                  onCheckedChange={(value) => setCheck({ value, slug: tab })}
                />
                <Label htmlFor={tab}>{__("Enable All", "animation-addons-for-elementor")}</Label>
              </div>
            </div>
            <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
              {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={catWidgets[tab].elements[content]}
                    slug={content}
                    updateActiveItem={updateActiveAtomicWidget}
                    className="rounded p-5"
                  />
                </React.Fragment>
              ))}
              {Array.from({
                length:
                  deviceMediaMatch() -
                  (Object.keys(catWidgets[tab].elements)?.length %
                    deviceMediaMatch() ===
                  0
                    ? deviceMediaMatch()
                    : Object.keys(catWidgets[tab].elements)?.length %
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

export default ShowAtomicWidgets;
