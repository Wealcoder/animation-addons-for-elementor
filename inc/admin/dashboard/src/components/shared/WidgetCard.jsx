import { Dot } from "lucide-react";
import { Badge } from "../ui/badge";
import { Switch } from "../ui/switch";
import { useState } from "react";
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
}) => {
  const { activated } = useActivate();

  const [open, setOpen] = useState(false);
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  const setCheck = (value, slug) => {
    if (widget?.is_pro && widget?.pro_only) {
      if (activated?.product_status?.item_id === 13) {
        if (updateActiveItem) {
          updateActiveItem({ value, slug });
        }
      } else {
        setOpen(value);
      }
    } else if (widget?.is_pro) {
      if (activated.wcf_valid) {
        if (updateActiveItem) {
          updateActiveItem({ value, slug });
        }
      } else {
        setOpen(value);
      }
    } else {
      if (updateActiveItem) {
        updateActiveItem({ value, slug });
      }
    }
  };

  return (
    <>
      <div
        className={cn(
          "flex items-center justify-between gap-3 px-4 py-[15px] bg-background rounded-lg  box-border",
          hashValue === slug
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "shadow-common-2",
          className
        )}
        id={slug || ""}
      >
        {widget ? (
          <>
            <div
              className={cn(
                "flex items-center gap-3",
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
                      <Badge variant="pro">COMING</Badge>
                    </>
                  ) : widget?.is_pro ? (
                    <>
                      <Dot
                        className="w-3.5 h-3.5 text-icon-secondary"
                        strokeWidth={2}
                      />
                      <Badge variant="pro">PRO</Badge>
                    </>
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
                    Documentation
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
                        Preview
                      </a>
                    </>
                  )}
                </div>
              </div>
            </div>
            <div className="flex justify-end items-center gap-2">
              {exSettings && (
                <div>
                  <ExtensionCardSettings>{exSettings}</ExtensionCardSettings>
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
          </>
        ) : (
          ""
        )}
      </div>
      <ProConfirmDialog open={open} setOpen={setOpen} />
    </>
  );
};

export default WidgetCard;
