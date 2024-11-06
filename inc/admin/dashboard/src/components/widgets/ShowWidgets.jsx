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
  const fetchWidgets = WCF_ADDONS_ADMIN?.addons_config?.widgets;

  console.log(fetchWidgets);

  const [tabValue, setTabValue] = useState("all");
  const [catWidgets, setCatWidgets] = useState();
  const [norResult, setNoResult] = useState(false);

  // useEffect(() => {
  //   if (allWidgets && WidgetTabList) {
  //     if (searchKey) {
  //       const searchResult = findSearchResult();
  //       if (!(searchResult && searchResult.length)) {
  //         setNoResult(true);
  //       } else {
  //         setNoResult(false);
  //       }
  //       const result = generateWidget(searchResult, WidgetTabList, filterKey);
  //       setCatWidgets(result);
  //     } else {
  //       setNoResult(false);
  //       const result = generateWidget(allWidgets, WidgetTabList, filterKey);
  //       setCatWidgets(result);
  //     }
  //   }
  // }, [allWidgets, filterKey, searchKey]);

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

  // const findSearchResult = () => {
  //   const result = allWidgets.filter(
  //     (el) => el.title.toLowerCase().search(searchKey.toLowerCase()) !== -1
  //   );
  //   return result;
  // };

  return (
    <Tabs defaultValue={"all"} value={tabValue} onValueChange={setTabValue}>
      <div className="flex justify-between items-center">
        <TabsList className="gap-1 h-11">
          <TabsTrigger key={"all-widgets_tab"} value={"all"}>
            All Widgets
          </TabsTrigger>
          {Object.keys(fetchWidgets)?.map((tab) => (
            <TabsTrigger key={tab} value={tab}>
              {fetchWidgets[tab].title}
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
          Object.keys(fetchWidgets)?.map((tab) => (
            <div className="mt-3 first:mt-0">
              <div className="bg-background flex justify-between items-center p-5 rounded">
                <h3 className="text-base font-medium">
                  {fetchWidgets[tab].title}
                </h3>
                <div className="flex items-center space-x-2">
                  <Switch id={tab} />
                  <Label htmlFor={tab}>Enable All</Label>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-1 mt-1">
                {Object.keys(fetchWidgets[tab].elements)?.map((content, i) => (
                  <React.Fragment key={`tab_content-${i}`}>
                    <WidgetCard
                      widget={fetchWidgets[tab].elements[content]}
                      slug={content}
                      className="rounded p-5"
                    />
                  </React.Fragment>
                ))}
                {Array.from({
                  length:
                    3 -
                    (Object.keys(fetchWidgets[tab].elements)?.length % 3 === 0
                      ? 3
                      : Object.keys(fetchWidgets[tab].elements)?.length % 3),
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
      {Object.keys(fetchWidgets)?.map((tab) => (
        <TabsContent
          key={tab}
          value={tab}
          className="bg-background-secondary p-3 rounded-lg"
        >
          <div>
            <div className="bg-background flex justify-between items-center p-5 rounded">
              <h3 className="text-base font-medium">
                {fetchWidgets[tab].title}
              </h3>
              <div className="flex items-center space-x-2">
                <Switch id={tab} />
                <Label htmlFor={tab}>Enable All</Label>
              </div>
            </div>
            <div className="grid grid-cols-3 gap-1 mt-1">
              {Object.keys(fetchWidgets[tab].elements)?.map((content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={fetchWidgets[tab].elements[content]}
                    slug={content}
                    className="rounded p-5"
                  />
                </React.Fragment>
              ))}
              {Array.from({
                length:
                  3 -
                  (Object.keys(fetchWidgets[tab].elements)?.length % 3 === 0
                    ? 3
                    : Object.keys(fetchWidgets[tab].elements)?.length % 3),
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
