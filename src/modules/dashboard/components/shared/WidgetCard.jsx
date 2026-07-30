import { __, _n, sprintf } from "@wordpress/i18n";
import { ChevronDown, Dot } from "lucide-react";
import { RiInformation2Fill } from "react-icons/ri";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Badge } from "../ui/badge";
import { Switch } from "../ui/switch";
import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import ProConfirmDialog from "./ProConfirmDialog";
import { useActivate } from "@/hooks/app.hooks";
import ExtensionCardSettings from "../extentions/ExtensionCardSettings";

const WidgetCard = ({
  widget,
  slug,
  className,
  updateActiveItem,
  isDisable = false,
  exSettings,
  preview = true,
  settingOpen = null,
}) => {
  const { activated } = useActivate();

  const [open, setOpen] = useState(false);
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  // Internal child elements of a composite widget (a Nested Slider's Track,
  // Dots, Progress…). They have no toggle of their own — they follow this
  // widget's state — so they are listed read-only rather than switchable.
  const parts = widget?.parts ?? [];
  const [partsOpen, setPartsOpen] = useState(false);

  const checkStatus = () => {
    if (widget?.is_pro && (widget?.pro_only ?? false)) {
      if (activated?.product_status?.item_id === 13) {
        return true;
      } else {
        return false;
      }
    } else if (widget?.is_pro) {
      if (activated.wcf_valid) {
        return true;
      } else {
        return false;
      }
    } else {
      return true;
    }
  };

  const setCheck = (value, slug) => {
    if (checkStatus()) {
      if (updateActiveItem) {
        updateActiveItem({ value, slug });
      }
    } else {
      setOpen(value);
    }
  };

  useEffect(() => {
    if (hashValue === slug) {
      const element = document.getElementById(hashValue);
      if (element) {
        element.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
      }
    }
  }, [hashValue]);

  return (
    <>
      <div
        className={cn(
          "flex flex-col px-4 py-[15px] bg-background rounded-lg  box-border",
          hashValue === slug
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "shadow-common-2",
          className
        )}
        id={slug || ""}
      >
        {widget ? (
          <div className="flex items-center justify-between gap-3">
            <div
              className={cn(
                "flex items-center gap-3 min-w-0",
                widget?.is_upcoming ? "opacity-50 pointer-events-none" : ""
              )}
            >
              <div
                className={cn(
                  "border rounded-full h-11 w-11 flex justify-center items-center shadow-common text-[20px]",
                  widget?.icon
                )}
              />

              <div className="flex flex-col gap-1">
                <div className="flex items-center">
                  <h2 className="text-[15px] leading-6 font-medium">
                    {widget?.label}
                  </h2>
                  {widget?.is_upcoming ? (
                    <>
                      <Dot
                        className="w-3.5 h-3.5 text-icon-secondary"
                        strokeWidth={2}
                      />
                      <Badge variant="pro">{__("COMING", "animation-addons-for-elementor")}</Badge>
                    </>
                  ) : widget?.is_pro ? (
                    <>
                      <Dot
                        className="w-3.5 h-3.5 text-icon-secondary"
                        strokeWidth={2}
                      />
                      <Badge variant="pro">{__("PRO", "animation-addons-for-elementor")}</Badge>
                    </>
                  ) : (
                    ""
                  )}

                  {/* What this extension depends on. The registry ships the
                      note ready to render (`requires_note`), so nothing is
                      assembled here — extensions that apply to any atomic
                      element simply omit it. */}
                  {widget?.requires_note ? (
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger
                          className="bg-transparent border-0 p-0 ml-1 inline-flex items-center cursor-help"
                          aria-label={widget.requires_note}
                        >
                          <RiInformation2Fill color="#CACFD8" size={16} />
                        </TooltipTrigger>
                        <TooltipContent>
                          <p className="max-w-[220px]">
                            {widget.requires_note}
                          </p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  ) : (
                    ""
                  )}
                </div>
                <div className="flex items-center">
                  <a
                    href={widget?.doc_url}
                    target="_blank"
                    className={cn(
                      "text-sm",
                      widget?.doc_url
                        ? "text-label hover:text-text"
                        : "pointer-events-none text-[#CACFD8]"
                    )}
                  >
                    {__("Documentation", "animation-addons-for-elementor")}
                  </a>

                  {preview && (
                    <>
                      <Dot
                        className="w-3.5 h-3.5 text-icon-secondary"
                        strokeWidth={2}
                      />
                      <a
                        href={widget?.demo_url}
                        target="_blank"
                        className={cn(
                          "text-sm",
                          widget?.demo_url
                            ? "text-label hover:text-text"
                            : "pointer-events-none text-[#CACFD8]"
                        )}
                      >
                        {__("Preview", "animation-addons-for-elementor")}
                      </a>
                    </>
                  )}

                </div>

                {/* Own line, not appended to the Documentation/Preview row —
                    these cards sit in a 3-column grid and the extra item
                    wrapped into the toggle switch at that width. */}
                {parts.length > 0 && (
                  <button
                    type="button"
                    onClick={() => setPartsOpen((prev) => !prev)}
                    aria-expanded={partsOpen}
                    className="text-xs text-label hover:text-text inline-flex items-center gap-0.5 bg-transparent border-0 p-0 cursor-pointer w-fit whitespace-nowrap"
                  >
                    {sprintf(
                      /* translators: %d: number of built-in child elements. */
                      _n(
                        "Includes %d element",
                        "Includes %d elements",
                        parts.length,
                        "animation-addons-for-elementor"
                      ),
                      parts.length
                    )}
                    <ChevronDown
                      className={cn(
                        "w-3.5 h-3.5 transition-transform",
                        partsOpen ? "rotate-180" : ""
                      )}
                      strokeWidth={2}
                    />
                  </button>
                )}
              </div>
            </div>
            <div className="flex justify-end items-center gap-2 shrink-0">
              {exSettings && (
                <div>
                  <ExtensionCardSettings
                    disabled={!checkStatus()}
                    defaultOpen={settingOpen === slug ? true : false}
                  >
                    {exSettings}
                  </ExtensionCardSettings>
                </div>
              )}

              {widget?.is_upcoming ? (
                ""
              ) : (
                <div>
                  <Switch
                    disabled={widget?.is_upcoming || isDisable}
                    checked={widget?.is_active}
                    onCheckedChange={(value) => setCheck(value, slug)}
                  />
                </div>
              )}
            </div>
          </div>
        ) : (
          ""
        )}

        {widget && partsOpen && parts.length > 0 && (
          <div className="mt-3 pt-3 border-t border-[#E6E8EC]">
            <p className="text-xs text-label mb-2">
              {__(
                "Built-in elements of this widget. They turn on and off with it and have no switch of their own.",
                "animation-addons-for-elementor"
              )}
            </p>
            {/* list-none / m-0 / p-0 are deliberate: wp-admin's own `ul li`
                rules add bullets and margins that break the chip layout. */}
            <ul className="flex flex-wrap gap-1.5 list-none m-0 p-0">
              {parts.map((part) => (
                <li
                  key={part.slug}
                  title={part.slug}
                  className="inline-flex items-center text-xs leading-5 px-2 py-0.5 m-0 rounded bg-[#F2F4F7] text-label whitespace-nowrap"
                >
                  {part.label}
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
      <ProConfirmDialog open={open} setOpen={setOpen} />
    </>
  );
};

export default WidgetCard;
