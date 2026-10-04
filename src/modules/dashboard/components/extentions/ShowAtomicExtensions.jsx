import { __ } from "@wordpress/i18n";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import React, { useEffect, useState } from "react";
import { filterWidgets } from "@/lib/utils";
import {
  countAtomicExtensions,
  flattenAtomicExtensions,
} from "@/lib/atomicExtensionService";
import { useActiveItem, useAtomicExtensions } from "@/hooks/app.hooks";
import { toast } from "sonner";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";
import { ExtensionSettingConfig } from "@/config/extensionSettingConfig";
import WidgetCategoryGrid from "../shared/WidgetCategoryGrid";
import { filterUsedWidgets } from "@/lib/usedWidgets";

const ShowAtomicExtensions = ({
  filterKey,
  setExtensionCount,
  // { slug: pages }, or null until the Usage scan has run. Only the extensions
  // that declare a `usage_prop` in the registry appear here — the site-level
  // ones (Custom Fonts, Code Snippet, …) are absent and their cards show no
  // count, which is the honest answer rather than a zero.
  usage = null,
}) => {
  // Same registry the v3 list uses (ShowExtensions.jsx). Keyed by extension
  // slug, and the slugs of the shared admin features (custom-fonts, …) are
  // identical on both sides on purpose — so Custom Fonts gets the same gear
  // here that it has on the v3 screen, from one source of truth. An extension
  // with no entry simply renders no gear.
  const exSettings = ExtensionSettingConfig;

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

  // Mirrors the widget lists — see the notes in ShowWidgets.
  const usedExtensions = filterUsedWidgets(catExtensions, usage);

  useEffect(() => {
    if (tabValue === "used" && (!usage || !Object.keys(usedExtensions).length)) {
      setTabValue("all");
    }
  }, [usage, tabValue, usedExtensions]);

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
            <TabsTrigger
              key={"all-atomic-extensions_tab"}
              value={"all"}
              className="px-4"
            >
              {__("All", "animation-addons-for-elementor")}
            </TabsTrigger>

            {extensionTabList?.map((tab) => (
              <TabsTrigger key={tab.value} value={tab.value}>
                {tab.title}
              </TabsTrigger>
            ))}

            {/* Appears only after a Usage scan — see usedExtensions above. */}
            {usage && (
              <TabsTrigger
                key="used-extensions_tab"
                value="used"
                data-aae-used-tab
              >
                {__("Used", "animation-addons-for-elementor")}
              </TabsTrigger>
            )}
          </TabsList>
          <ScrollBar orientation="horizontal" />
        </ScrollArea>

        <div className="flex gap-2.5 items-center justify-end">
          <Button onClick={() => saveExtension()}>
            {__("Save Settings", "animation-addons-for-elementor")}
          </Button>
        </div>
      </div>

      <TabsContent
        key={"all-atomic-extensions_content"}
        value={"all"}
        className="bg-background-secondary p-3 rounded-lg"
      >
        {Object.keys(catExtensions)?.map((tab) => (
          <WidgetCategoryGrid
            key={`all_group-${tab}`}
            category={catExtensions[tab]}
            categorySlug={tab}
            onToggleCategory={(value) => setCheck({ value, slug: tab })}
            updateActiveItem={updateActiveAtomicExtension}
            usage={usage}
            widgetSettings={exSettings}
          />
        ))}
      </TabsContent>

      {/* Used — see the note in ShowWidgets. */}
      {usage && (
        <TabsContent
          key="used-extensions_content"
          value="used"
          className="bg-background-secondary p-3 rounded-lg"
        >
          {Object.keys(usedExtensions).length ? (
            Object.keys(usedExtensions).map((tab) => (
              <WidgetCategoryGrid
                key={`used_group-${tab}`}
                category={usedExtensions[tab]}
                categorySlug={`used-${tab}`}
                showGroupToggle={false}
                updateActiveItem={updateActiveAtomicExtension}
                usage={usage}
                widgetSettings={exSettings}
              />
            ))
          ) : (
            <div className="bg-background flex justify-center items-center p-5 rounded">
              <h3 className="text-base font-medium">
                {__(
                  "No extensions are used on any page.",
                  "animation-addons-for-elementor"
                )}
              </h3>
            </div>
          )}
        </TabsContent>
      )}

      {Object.keys(catExtensions)?.map((tab) => (
        <TabsContent
          key={tab}
          value={tab}
          className="bg-background-secondary p-3 rounded-lg"
        >
          <WidgetCategoryGrid
            category={catExtensions[tab]}
            categorySlug={tab}
            onToggleCategory={(value) => setCheck({ value, slug: tab })}
            updateActiveItem={updateActiveAtomicExtension}
            usage={usage}
            widgetSettings={exSettings}
          />
        </TabsContent>
      ))}
    </Tabs>
  );
};

export default ShowAtomicExtensions;
