import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch } from "@/lib/utils";
import { useActiveItem, useSetup, useWidgets } from "@/hooks/app.hooks";
import { activeFullSetupWidgetFn } from "@/lib/widgetService";

const ShowWizWidgets = () => {
  const { allWidgets } = useWidgets();
  const { updateActiveWidget, updateActiveGroupWidget } = useActiveItem();
  const { setupType } = useSetup();

  const [catWidgets, setCatWidgets] = useState({});

  useEffect(() => {
    if (allWidgets) {
      setCatWidgets(allWidgets.elements);
    }
  }, [allWidgets]);

  useEffect(() => {
    if (allWidgets) {
      const result = activeFullSetupWidgetFn(allWidgets, setupType);
      setCatWidgets(result);
    }
  }, []);

  const setCheck = (data) => {
    updateActiveGroupWidget(data);
  };

  return (
    <div className="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-background-secondary p-3 rounded-lg">
      {Object.keys(catWidgets)?.map((tab) => (
        <div className="mt-3 first:mt-0">
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
      ))}
    </div>
  );
};

export default ShowWizWidgets;
