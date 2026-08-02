import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch } from "@/lib/utils";
import {
  useActiveItem,
  useAtomicExtensions,
  useSetup,
} from "@/hooks/app.hooks";

/**
 * The wizard's extension step, on the V4 atomic registry.
 *
 * groupAtomicExtensionsByCategory() returns the same
 * { title, elements: { <category>: { title, is_active, elements } } } shape as
 * the widget side, so this is the widget step with different data rather than
 * ShowWizExtension's accordion — v3 needs that accordion because its GSAP
 * extensions nest a second level and carry per-extension settings dialogs.
 * Atomic extensions are flat, so a nested control would be empty chrome.
 *
 * Seeded from the setup type the same way as ShowWizAtomicWidgets — see
 * lib/setupPresets.js for the rule. Note what that means here: every animation
 * extension in the registry is PRO, so a Basic setup pre-selects only the free
 * utility ones. That is intended, not a gap.
 */
const ShowWizAtomicExtensions = () => {
  const { allAtomicExtensions } = useAtomicExtensions();
  const { updateActiveAtomicExtension, updateActiveAtomicGroupExtension } =
    useActiveItem();
  const { setupType, applyAtomicExtensionSetup } = useSetup();

  const [catExtensions, setCatExtensions] = useState({});

  useEffect(() => {
    if (allAtomicExtensions) {
      setCatExtensions(allAtomicExtensions.elements || {});
    }
  }, [allAtomicExtensions]);

  // Seed once per setup-type choice — see the twin comment in
  // ShowWizAtomicWidgets.
  useEffect(() => {
    applyAtomicExtensionSetup(setupType);
  }, [setupType]);

  const setCheck = (data) => {
    updateActiveAtomicGroupExtension(data);
  };

  return (
    <div className="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-background-secondary p-3 rounded-lg">
      {Object.keys(catExtensions)?.map((tab) => (
        <div className="mt-3 first:mt-0" key={`atomic-ext-cat-${tab}`}>
          <div className="bg-background flex justify-between items-center p-5 rounded">
            <h3 className="text-base font-medium">{catExtensions[tab].title}</h3>
            <div className="flex items-center space-x-2">
              <Switch
                id={`atomic-ext-${tab}`}
                checked={catExtensions[tab].is_active}
                onCheckedChange={(value) => setCheck({ value, slug: tab })}
              />
              <Label htmlFor={`atomic-ext-${tab}`}>Enable All</Label>
            </div>
          </div>
          <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
            {Object.keys(catExtensions[tab].elements)?.map((content, i) => (
              <React.Fragment key={`atomic_ext-${tab}-${i}`}>
                <WidgetCard
                  widget={catExtensions[tab].elements[content]}
                  slug={content}
                  updateActiveItem={updateActiveAtomicExtension}
                  className="rounded p-5"
                />
              </React.Fragment>
            ))}
            {Array.from({
              length:
                deviceMediaMatch() -
                (Object.keys(catExtensions[tab].elements)?.length %
                  deviceMediaMatch() ===
                0
                  ? deviceMediaMatch()
                  : Object.keys(catExtensions[tab].elements)?.length %
                    deviceMediaMatch()),
            }).map((_, index) => (
              <WidgetCard
                key={`atomic_ext_empty-${tab}-${index}`}
                className="rounded"
              />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};

export default ShowWizAtomicExtensions;
