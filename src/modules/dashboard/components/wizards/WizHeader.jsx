import { cn } from "@/lib/utils";
import { Separator } from "../ui/separator";
import { useEffect, useState } from "react";
import { WizNavList } from "@/config/nav/wiz-nav";
import { ScrollArea, ScrollBar } from "../ui/scroll-area";

export const WizHeader = ({ NavigateComponent }) => {
  const [currentPath, setCurrentPath] = useState("");

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setCurrentPath(tabValue);
    } else {
      setCurrentPath("terms");
    }
  }, [urlParams]);

  if (!(WizNavList && WizNavList.length)) return;

  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
  };

  const getSerial = (path) => {
    const result = WizNavList.find((item) => item.path === path);
    return result ? result.serial : 1;
  };

  return (
    <div className="header_section flex justify-center items-center font-figtree">
      <ScrollArea className="w-[1033px] px-4 xl:px-8 py-6 bg-[#FFF7EC] m-[30px_50px] rounded-[50px]">
        <div className="w-max flex justify-center items-center gap-2 xl:gap-3">
          {WizNavList.map((item, i) => (
            <React.Fragment key={item.serial + i}>
              <div
                className={cn(
                  "cursor-pointer flex justify-center items-center gap-2 text-sm font-medium"
                )}
                onClick={() => changeRoute(item.path)}
              >
                <p
                  className={cn(
                    "menu_item_round w-7 h-7 rounded-full border flex justify-center items-center bg-white",
                    getSerial(currentPath) >= item.serial
                      ? "text-button-secondary border-brand bg-brand"
                      : "text-text-secondary border-border"
                  )}
                >
                  {item.serial}
                </p>
                <p
                  className={cn("font-Figtree" + 
                    getSerial(currentPath) >= item.serial
                      ? "text-text font-figtree"
                      : "text-text-secondary font-Figtree"
                  )}
                >
                  {item.title}
                </p>
              </div>
              {WizNavList.length - 1 !== i && (
                <Separator className="w-[13px] xl:w-[18px] bg-border-tertiary" />
              )}
            </React.Fragment>
          ))}
        </div>
        <ScrollBar orientation="horizontal" />
      </ScrollArea>
    </div>
  );
};
