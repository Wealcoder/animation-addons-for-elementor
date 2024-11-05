import { Dot } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { Switch } from "../ui/switch";
import { useState } from "react";
import { RiLandscapeFill } from "react-icons/ri";
import { cn } from "@/lib/utils";
import ProConfirmDialog from "./ProConfirmDialog";

const WidgetCard = ({ widget, className }) => {
  const [isChecked, setIsChecked] = useState(widget?.isActive);
  const [open, setOpen] = useState(false);
  // const location = useLocation();

  // const hashValue = location.hash.replace("#", "");
  const hashValue = "";

  const setCheck = (value) => {
    if (value && widget?.isPro) {
      setOpen(value);
    } else {
      setIsChecked(value);
    }
  };

  return (
    <>
      <div
        className={cn(
          "flex items-center justify-between gap-3 px-4 py-[15px] bg-background rounded-lg shadow-common-2 box-border",
          hashValue === widget?.slug
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "",
          className
        )}
        id={widget?.slug || ""}
      >
        {widget ? (
          <>
            <div className="flex items-center gap-3">
              <div>
                <Avatar className="border rounded-full h-11 w-11 flex justify-center items-center shadow-common">
                  <AvatarImage
                    className="w-5 h-5"
                    src={widget?.logo}
                    alt="Widget Logo"
                  />
                  <AvatarFallback>
                    <RiLandscapeFill size={20} color="#CACFD8" />
                  </AvatarFallback>
                </Avatar>
              </div>
              <div className="flex flex-col gap-1">
                <div className="flex items-center">
                  <h2 className="text-[15px] leading-6 font-medium">
                    {widget?.title}
                  </h2>
                  {widget?.isPro ? (
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
                    href={widget?.docLink}
                    className="text-sm text-label hover:text-text"
                  >
                    Documentation
                  </a>
                  <Dot
                    className="w-3.5 h-3.5 text-icon-secondary"
                    strokeWidth={2}
                  />
                  <a
                    href={widget?.previewLink}
                    className="text-sm text-label hover:text-text"
                  >
                    Preview
                  </a>
                </div>
              </div>
            </div>
            <div>
              <Switch
                checked={isChecked}
                onCheckedChange={(value) => setCheck(value)}
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
