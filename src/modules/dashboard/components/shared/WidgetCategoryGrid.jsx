import React from "react";
import WidgetCard from "./WidgetCard";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import { deviceMediaMatch } from "@/lib/utils";
import { __ } from "@wordpress/i18n";

/**
 * One category heading plus its grid of widget cards.
 *
 * Extracted because ShowWidgets and ShowAtomicWidgets each carried this markup
 * TWICE already (the "All" tab and the per-category tab), and the Used tab
 * would have made it six copies across the two files. Six places to keep a card
 * grid in step is how a prop like `usage` ends up wired into some tabs and not
 * others — visibly wrong only on the tab nobody opened while testing.
 *
 * @param {Object}   category        `{ title, is_active, elements }`.
 * @param {string}   categorySlug    Its key; also the group switch's DOM id.
 * @param {Function} onToggleCategory Group Enable All handler.
 * @param {boolean}  showGroupToggle  See the note below — false on filtered views.
 * @param {Function} updateActiveItem Per-card toggle handler.
 * @param {Object}   usage           `{ slug: pages }` or null (never scanned).
 * @param {string}   settingOpen     Slug whose settings panel opens on load.
 * @param {Array}    widgetSettings  V3 per-widget settings components; atomic
 *                                   has none and passes nothing.
 */
const WidgetCategoryGrid = ({
  category,
  categorySlug,
  onToggleCategory,
  showGroupToggle = true,
  updateActiveItem,
  usage = null,
  settingOpen = null,
  widgetSettings = null,
}) => {
  const slugs = Object.keys(category?.elements || {});

  // Pads the last row so the grid keeps its columns. Same arithmetic the two
  // list screens used inline.
  const columns = deviceMediaMatch();
  const remainder = slugs.length % columns;
  const fillers = remainder === 0 ? 0 : columns - remainder;

  return (
    <div className="mt-3 first:mt-0">
      <div className="bg-background flex justify-between items-center p-5 rounded">
        <h3 className="text-base font-medium">{category?.title}</h3>

        {/*
          Hidden on a FILTERED view. "Enable All" acts on the whole category,
          not on the cards currently shown — offering it above a list of four
          used widgets, where it would silently switch on the other thirty,
          is a control that lies about its own scope.
        */}
        {showGroupToggle && (
          <div className="flex items-center space-x-2">
            <Switch
              id={categorySlug}
              checked={category?.is_active}
              onCheckedChange={(value) => onToggleCategory(value)}
            />
            <Label htmlFor={categorySlug}>
              {__("Enable All", "animation-addons-for-elementor")}
            </Label>
          </div>
        )}
      </div>

      <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
        {slugs.map((slug, i) => (
          <React.Fragment key={`${categorySlug}_card-${i}`}>
            <WidgetCard
              widget={category.elements[slug]}
              slug={slug}
              usage={usage ? usage[slug] ?? 0 : undefined}
              updateActiveItem={updateActiveItem}
              className="rounded p-5"
              settingOpen={settingOpen}
              exSettings={
                widgetSettings?.find((item) => item.key === slug)?.component
              }
            />
          </React.Fragment>
        ))}

        {Array.from({ length: fillers }).map((_, index) => (
          <WidgetCard key={`${categorySlug}_filler-${index}`} className="rounded" />
        ))}
      </div>
    </div>
  );
};

export default WidgetCategoryGrid;
