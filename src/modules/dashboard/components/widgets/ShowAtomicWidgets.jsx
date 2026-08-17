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
import WidgetCategoryGrid from "../shared/WidgetCategoryGrid";
import { filterUsedWidgets } from "@/lib/usedWidgets";

const ShowAtomicWidgets = ({
  searchKey,
  filterKey,
  setWidgetCount,
  // { slug: pages }, or null until the Usage scan has run.
  usage = null,
}) => {
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

  // Mirrors ShowWidgets — see the notes there.
  const usedWidgets = filterUsedWidgets(catWidgets, usage);

  useEffect(() => {
    if (tabValue === "used" && (!usage || !Object.keys(usedWidgets).length)) {
      setTabValue("all");
    }
  }, [usage, tabValue, usedWidgets]);

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
            <WidgetCategoryGrid
              key={`all_group-${tab}`}
              category={catWidgets[tab]}
              categorySlug={tab}
              onToggleCategory={(value) => setCheck({ value, slug: tab })}
              updateActiveItem={updateActiveAtomicWidget}
              usage={usage}
            />
          ))
        )}
      </TabsContent>

      {/* Used — see the note in ShowWidgets. */}
      {usage && (
        <TabsContent
          key="used-atomic-widgets_content"
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
                updateActiveItem={updateActiveAtomicWidget}
                usage={usage}
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
          <WidgetCategoryGrid
            category={catWidgets[tab]}
            categorySlug={tab}
            onToggleCategory={(value) => setCheck({ value, slug: tab })}
            updateActiveItem={updateActiveAtomicWidget}
            usage={usage}
          />
        </TabsContent>
      ))}
    </Tabs>
  );
};

export default ShowAtomicWidgets;
