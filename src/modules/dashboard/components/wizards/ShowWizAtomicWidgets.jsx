import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch } from "@/lib/utils";
import { useActiveItem, useAtomicWidgets, useSetup } from "@/hooks/app.hooks";

/**
 * The wizard's widget step, on the V4 atomic registry.
 *
 * Deliberately NOT ShowAtomicWidgets (the dashboard's version): that one owns
 * search, category tabs, a filter and its own Save button, none of which belong
 * in a wizard where the footer drives navigation and saving. This mirrors
 * ShowWizWidgets — its v3 twin — so the two steps look identical to the user.
 *
 * The selection is seeded from the setup type chosen on step 1 (see
 * lib/setupPresets.js): Basic pre-selects the free, non-animation widgets;
 * Custom pre-selects everything the licence can actually register. A new
 * install still SAVES nothing until the user finishes the wizard, so the
 * all-off default for a fresh site is untouched.
 */
const ShowWizAtomicWidgets = () => {
  const { allAtomicWidgets } = useAtomicWidgets();
  const { updateActiveAtomicWidget, updateActiveAtomicGroupWidget } =
    useActiveItem();
  const { setupType, applyAtomicWidgetSetup } = useSetup();

  const [catWidgets, setCatWidgets] = useState({});

  useEffect(() => {
    if (allAtomicWidgets) {
      setCatWidgets(allAtomicWidgets.elements || {});
    }
  }, [allAtomicWidgets]);

  // Seed once per setup-type choice, not on every render: re-running this
  // would wipe whatever the user has toggled by hand on this very step.
  useEffect(() => {
    applyAtomicWidgetSetup(setupType);
  }, [setupType]);

  const setCheck = (data) => {
    updateActiveAtomicGroupWidget(data);
  };

  return (
    <div className="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-background-secondary p-3 rounded-lg">
      {Object.keys(catWidgets)?.map((tab) => (
        <div className="mt-3 first:mt-0" key={`atomic-widget-cat-${tab}`}>
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
              <React.Fragment key={`atomic_widget-${tab}-${i}`}>
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
                key={`atomic_widget_empty-${tab}-${index}`}
                className="rounded"
              />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};

export default ShowWizAtomicWidgets;
