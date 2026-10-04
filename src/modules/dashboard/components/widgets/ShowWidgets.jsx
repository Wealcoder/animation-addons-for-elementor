import { __ } from "@wordpress/i18n";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch, filterWidgets, isEqual } from "@/lib/utils";
import { useActiveItem, useNotification, useWidgets } from "@/hooks/app.hooks";
import { toast } from "sonner";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";
import { WidgetSettingConfig } from "@/config/widgetSettingConfig";
import WidgetCategoryGrid from "../shared/WidgetCategoryGrid";
import { filterUsedWidgets } from "@/lib/usedWidgets";

const ShowWidgets = ({
  searchKey,
  filterKey,
  searchParam,
  urlParams,
  setWidgetCount,
  settingOpen,
  // { slug: pages }, or null until the Usage scan has run.
  usage = null,
}) => {
  const widgetSettings = WidgetSettingConfig;
  const { allWidgets } = useWidgets();
  const { updateNotice } = useNotification();
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

  // The Used tab exists only once a scan has run — before that the dashboard
  // has no idea what is used, and a tab that silently means "nothing" would be
  // a claim it has not checked.
  const usedWidgets = filterUsedWidgets(catWidgets, usage);

  // Leaving the user staring at an empty filtered view after a rescan is worse
  // than moving them; if their tab just lost its last card, fall back to All.
  useEffect(() => {
    if (tabValue === "used" && (!usage || !Object.keys(usedWidgets).length)) {
      setTabValue("all");
    }
  }, [usage, tabValue, usedWidgets]);

  const saveWidget = async () => {
    const isChanged = isEqual(
      allWidgets,
      JSON.parse(JSON.stringify(WCF_ADDONS_ADMIN?.addons_config?.widgets)) || {}
    );

    if (isChanged && Object.keys(isChanged).length) {
      const date = new Date();
      const utcDate = date.toISOString();

      const sampleData = {
        type: "notice",
        title: __("Widgets Activity Log", "animation-addons-for-elementor"),
        description:
          __("Your widget settings have been successfully updated in the following time period.", "animation-addons-for-elementor"),
        date: utcDate,
      };

      updateNotice(sampleData);
    }

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
            <TabsTrigger key={"all-widgets_tab"} value={"all"} className="px-4">
              {__("All", "animation-addons-for-elementor")}
            </TabsTrigger>

            {widgetTabList?.map((tab) => (
              <TabsTrigger key={tab.value} value={tab.value}>
                {tab.title}
              </TabsTrigger>
            ))}

            {/* Appears only after a Usage scan — see usedWidgets above. */}
            {usage && (
              <TabsTrigger key="used-widgets_tab" value="used" data-aae-used-tab>
                {__("Used", "animation-addons-for-elementor")}
              </TabsTrigger>
            )}
          </TabsList>
          <ScrollBar orientation="horizontal" />
        </ScrollArea>

        <div className="flex gap-2.5 items-center justify-end">
          {/* <Button variant="secondary">Reset</Button> */}
          <Button onClick={() => saveWidget()}>{__("Save Settings", "animation-addons-for-elementor")}</Button>
        </div>
      </div>
      <TabsContent
        key={"all-widgets_content"}
        value={"all"}
        className="bg-background-secondary p-3 rounded-lg"
      >
        {norResult ? (
          <div className="bg-background flex justify-center items-center p-5 rounded">
            <h3 className="text-base font-medium">{__("No Result Found", "animation-addons-for-elementor")}</h3>
          </div>
        ) : (
          Object.keys(catWidgets)?.map((tab) => (
            <WidgetCategoryGrid
              key={`all_group-${tab}`}
              category={catWidgets[tab]}
              categorySlug={tab}
              onToggleCategory={(value) => setCheck({ value, slug: tab })}
              updateActiveItem={updateActiveWidget}
              usage={usage}
              settingOpen={settingOpen}
              widgetSettings={widgetSettings}
            />
          ))
        )}
      </TabsContent>

      {/*
        Used — every widget the scan found on at least one page. No group
        Enable All here: it would act on the whole category, not on the four
        cards this view is showing.
      */}
      {usage && (
        <TabsContent
          key="used-widgets_content"
          value="used"
          className="bg-background-secondary p-3 rounded-lg"
        >
          {Object.keys(usedWidgets).length ? (
            Object.keys(usedWidgets).map((tab) => (
              <WidgetCategoryGrid
                key={`used_group-${tab}`}
                category={usedWidgets[tab]}
                categorySlug={`used-${tab}`}
                showGroupToggle={false}
                updateActiveItem={updateActiveWidget}
                usage={usage}
                settingOpen={settingOpen}
                widgetSettings={widgetSettings}
              />
            ))
          ) : (
            <div className="bg-background flex justify-center items-center p-5 rounded">
              <h3 className="text-base font-medium">
                {__(
                  "No widgets are used on any page.",
                  "animation-addons-for-elementor"
                )}
              </h3>
            </div>
          )}
        </TabsContent>
      )}
      {Object.keys(catWidgets)?.map((tab) => (
        <TabsContent
          key={tab}
          value={tab}
          className="bg-background-secondary p-3 rounded-lg"
        >
          {/*
            `settingOpen` is deliberately NOT passed here — it never was. The
            per-widget settings panel auto-opens from `?wiz_setting=` on the All
            tab only. Pre-existing asymmetry, preserved rather than quietly
            changed while extracting this grid.
          */}
          <WidgetCategoryGrid
            category={catWidgets[tab]}
            categorySlug={tab}
            onToggleCategory={(value) => setCheck({ value, slug: tab })}
            updateActiveItem={updateActiveWidget}
            usage={usage}
            widgetSettings={widgetSettings}
          />
        </TabsContent>
      ))}
    </Tabs>
  );
};

export default ShowWidgets;
