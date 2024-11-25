import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { filterWidgets } from "@/lib/utils";
import { useActiveItem, useWidgets } from "@/hooks/app.hooks";
import { toast } from "sonner";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";

const ShowWidgets = ({
  searchKey,
  filterKey,
  searchParam,
  urlParams,
  setWidgetCount,
}) => {
  const { allWidgets } = useWidgets();
  const { updateActiveWidget, updateActiveGroupWidget } = useActiveItem();

  const [tabValue, setTabValue] = useState("all");
  const [catWidgets, setCatWidgets] = useState({});
  const [norResult, setNoResult] = useState(false);

  const [widgetTabList, setWidgetTabList] = useState([]);

  useEffect(() => {
    if (allWidgets) {
      const result = [];
      for (let el in allWidgets.elements) {
        let data = {
          title: allWidgets.elements[el].title?.replace("Widgets", ""),
          value: el,
        };
        result.push(data);
      }

      setWidgetTabList(result);
    }
  }, [allWidgets]);

  useEffect(() => {
    if (allWidgets) {
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
        const result = filterWidgets(allWidgets.elements, filterKey);
        setCatWidgets(result);
      }
    }
  }, [allWidgets, filterKey, searchKey]);

  useEffect(() => {
    if (searchKey) {
      setTabValue("all");
    }
  }, [searchKey]);

  useEffect(() => {
    if (searchParam) {
      setTabValue(searchParam);
    }
  }, [searchParam, urlParams]);

  const findSearchResult = () => {
    const result = Object.fromEntries(
      Object.entries(allWidgets.elements)
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
    updateActiveGroupWidget(data);
  };

  const saveWidget = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_settings_with_ajax",
        fields: JSON.stringify(allWidgets),
        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_widgets",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        setWidgetCount((prev) => ({ ...prev, active: return_content.total }));
        toast.success("Save Successful", {
          position: "top-right",
        });
      });
  };

  return (
    <Tabs defaultValue={"all"} value={tabValue} onValueChange={setTabValue}>
      <div className="flex justify-between items-center">
        <ScrollArea className="max-w-[465px] xl:max-w-[750px] rounded-lg bg-background-secondary">
          <TabsList className="gap-1 h-11">
            <TabsTrigger key={"all-widgets_tab"} value={"all"}>
              All Widgets
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
          {/* <Button variant="secondary">Reset</Button> */}
          <Button onClick={() => saveWidget()}>Save Settings</Button>
        </div>
      </div>
      <TabsContent
        key={"all-widgets_content"}
        value={"all"}
        className="bg-background-secondary p-3 rounded-lg"
      >
        {norResult ? (
          <div className="bg-background flex justify-center items-center p-5 rounded">
            <h3 className="text-base font-medium">No Result Found</h3>
          </div>
        ) : (
          Object.keys(catWidgets)?.map((tab) => (
            <div className="mt-3 first:mt-0">
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
                  <Label htmlFor={tab}>Enable All</Label>
                </div>
              </div>
              <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
                {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard
                      widget={catWidgets[tab].elements[content]}
                      slug={content}
                      updateActiveItem={updateActiveWidget}
                      className="rounded p-5"
                    />
                  </React.Fragment>
                ))}
                {Array.from({
                  length:
                    3 -
                    (Object.keys(catWidgets[tab].elements)?.length % 3 === 0
                      ? 3
                      : Object.keys(catWidgets[tab].elements)?.length % 3),
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
                <Label htmlFor={tab}>Enable All</Label>
              </div>
            </div>
            <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
              {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={catWidgets[tab].elements[content]}
                    slug={content}
                    updateActiveItem={updateActiveWidget}
                    className="rounded p-5"
                  />
                </React.Fragment>
              ))}
              {Array.from({
                length:
                  3 -
                  (Object.keys(catWidgets[tab].elements)?.length % 3 === 0
                    ? 3
                    : Object.keys(catWidgets[tab].elements)?.length % 3),
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

export default ShowWidgets;
