import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { filterGeneralItem, filterWidgets } from "@/lib/utils";

const ShowWidgets = ({ searchKey, filterKey, searchParam, urlParams }) => {
  const fetchWidgets = WCF_ADDONS_ADMIN?.addons_config?.widgets;

  const [tabValue, setTabValue] = useState("all");
  const [catWidgets, setCatWidgets] = useState({});
  const [norResult, setNoResult] = useState(false);

  const [widgetTabList, setWidgetTabList] = useState([]);

  useEffect(() => {
    if (fetchWidgets) {
      const result = [];
      for (let el in fetchWidgets) {
        let data = {
          title: fetchWidgets[el].title?.replace("Widgets", ""),
          value: el,
        };
        result.push(data);
      }

      setWidgetTabList(result);
    }
  }, [fetchWidgets]);

  useEffect(() => {
    if (fetchWidgets) {
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
        const result = filterWidgets(fetchWidgets, filterKey);
        setCatWidgets(result);
      }
    }
  }, [fetchWidgets, filterKey, searchKey]);

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
      Object.entries(fetchWidgets)
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

  return (
    <Tabs defaultValue={"all"} value={tabValue} onValueChange={setTabValue}>
      <div className="flex justify-between items-center">
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
        <div className="flex gap-2.5 items-center justify-end">
          <Button variant="secondary">Reset</Button>
          <Button>Save Settings</Button>
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
                  <Switch id={tab} />
                  <Label htmlFor={tab}>Enable All</Label>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-1 mt-1">
                {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard
                      widget={catWidgets[tab].elements[content]}
                      slug={content}
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
                <Switch id={tab} />
                <Label htmlFor={tab}>Enable All</Label>
              </div>
            </div>
            <div className="grid grid-cols-3 gap-1 mt-1">
              {Object.keys(catWidgets[tab].elements)?.map((content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={catWidgets[tab].elements[content]}
                    slug={content}
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
