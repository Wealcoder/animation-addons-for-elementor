import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import { generateWidget } from "@/lib/utils";
import { AllWidgetList } from "@/config/data/allWidgetList";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";

const WidgetTabList = [
  {
    name: "All Widgets",
    value: "all",
  },
  {
    name: "General",
    value: "general",
  },
  {
    name: "Header/Footer",
    value: "heder-footer",
  },
  {
    name: "Dynamic",
    value: "dynamic",
  },
  {
    name: "Form",
    value: "form",
  },
  {
    name: "Video",
    value: "video",
  },
  {
    name: "GSAP",
    value: "gsap",
  },
];

const ShowWidgets = ({ searchKey, filterKey, searchParam, urlParams }) => {
  const allWidgets = AllWidgetList;

  const [tabValue, setTabValue] = useState(WidgetTabList[0]?.value);
  const [catWidgets, setCatWidgets] = useState();
  const [norResult, setNoResult] = useState(false);

  useEffect(() => {
    if (allWidgets && WidgetTabList) {
      if (searchKey) {
        const searchResult = findSearchResult();
        if (!(searchResult && searchResult.length)) {
          setNoResult(true);
        } else {
          setNoResult(false);
        }
        const result = generateWidget(searchResult, WidgetTabList, filterKey);
        setCatWidgets(result);
      } else {
        setNoResult(false);
        const result = generateWidget(allWidgets, WidgetTabList, filterKey);
        setCatWidgets(result);
      }
    }
  }, [allWidgets, filterKey, searchKey]);

  useEffect(() => {
    if (searchKey) {
      setTabValue(WidgetTabList[0]?.value);
    }
  }, [searchKey]);

  useEffect(() => {
    if (searchParam) {
      setTabValue(searchParam);
    }
  }, [searchParam, urlParams]);

  const findSearchResult = () => {
    const result = allWidgets.filter(
      (el) => el.title.toLowerCase().search(searchKey.toLowerCase()) !== -1
    );
    return result;
  };

  return (
    <Tabs
      defaultValue={WidgetTabList[0]?.value}
      value={tabValue}
      onValueChange={setTabValue}
    >
      <div className="flex justify-between items-center">
        <TabsList className="gap-1 h-11">
          {WidgetTabList?.map((tab) => (
            <TabsTrigger key={tab.value} value={tab.value}>
              {tab.name}
            </TabsTrigger>
          ))}
        </TabsList>
        <div className="flex gap-2.5 items-center justify-end">
          <Button variant="secondary">Reset</Button>
          <Button>Save Settings</Button>
        </div>
      </div>
      {WidgetTabList?.map((tab) => (
        <TabsContent
          key={tab.value}
          value={tab.value}
          className="bg-background-secondary p-3 rounded-lg"
        >
          {tab.value === "all" ? (
            norResult ? (
              <div className="bg-background flex justify-center items-center p-5 rounded">
                <h3 className="text-base font-medium">No Result Found</h3>
              </div>
            ) : (
              WidgetTabList.slice(1, WidgetTabList.length)?.map((allTab) =>
                catWidgets?.[allTab.value] &&
                catWidgets?.[allTab.value].length ? (
                  <div
                    className="mt-3 first:mt-0"
                    key={`all_tab-${allTab.value}`}
                  >
                    <div className="bg-background flex justify-between items-center p-5 rounded">
                      <h3 className="text-base font-medium">
                        {allTab.name} Widgets
                      </h3>
                      <div className="flex items-center space-x-2">
                        <Switch id={allTab.value} />
                        <Label htmlFor={allTab.value}>Enable All</Label>
                      </div>
                    </div>
                    <div className="grid grid-cols-3 gap-1 mt-1">
                      {catWidgets?.[allTab.value]?.map((content, i) => (
                        <React.Fragment key={`tab_content-${i}`}>
                          <WidgetCard
                            widget={content}
                            className="rounded p-5"
                          />
                        </React.Fragment>
                      ))}
                      {Array.from({
                        length:
                          3 -
                          (catWidgets?.[allTab.value]?.length % 3 === 0
                            ? 3
                            : catWidgets?.[allTab.value]?.length % 3),
                      }).map((_, index) => (
                        <WidgetCard
                          key={`tab_content_empty-${index}`}
                          className="rounded"
                        />
                      ))}
                    </div>
                  </div>
                ) : (
                  ""
                )
              )
            )
          ) : (
            <div>
              <div className="bg-background flex justify-between items-center p-5 rounded">
                <h3 className="text-base font-medium">{tab.name} Widgets</h3>
                <div className="flex items-center space-x-2">
                  <Switch id={tab.value} />
                  <Label htmlFor={tab.value}>Enable All</Label>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-1 mt-1">
                {catWidgets?.[tab.value]?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard widget={content} className="rounded p-5" />
                  </React.Fragment>
                ))}
                {Array.from({
                  length:
                    3 -
                    (catWidgets?.[tab.value]?.length % 3 === 0
                      ? 3
                      : catWidgets?.[tab.value]?.length % 3),
                }).map((_, index) => (
                  <WidgetCard
                    key={`tab_content_empty-${index}`}
                    className="rounded"
                  />
                ))}
              </div>
            </div>
          )}
        </TabsContent>
      ))}
    </Tabs>
  );
};

export default ShowWidgets;
