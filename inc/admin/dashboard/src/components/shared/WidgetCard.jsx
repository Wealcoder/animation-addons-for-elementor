import { Dot } from "lucide-react";
import { Badge } from "../ui/badge";
import { Switch } from "../ui/switch";
import { useState } from "react";
import { cn } from "@/lib/utils";
import ProConfirmDialog from "./ProConfirmDialog";

const WidgetCard = ({ widget, slug, className, updateActiveItem }) => {
  const [open, setOpen] = useState(false);

  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;

  const setCheck = (value, slug) => {
    if (widget?.is_pro) {
      if (isValid) {
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
            <div className="flex items-center gap-3">
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
                  {widget?.is_pro ? (
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
                    className="text-sm text-label hover:text-text"
                  >
                    Documentation
                  </a>
                  <Dot
                    className="w-3.5 h-3.5 text-icon-secondary"
                    strokeWidth={2}
                  />
                  <a
                    href={widget?.demo_url}
                    target="_blank"
                    className="text-sm text-label hover:text-text"
                  >
                    Preview
                  </a>
                </div>
              </div>
            </div>
            <div>
              <Switch
                checked={widget?.is_active}
                onCheckedChange={(value) => setCheck(value, slug)}
              />
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
