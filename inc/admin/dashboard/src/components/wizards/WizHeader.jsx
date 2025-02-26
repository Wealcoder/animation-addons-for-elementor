import { cn } from "@/lib/utils";
import { Separator } from "../ui/separator";
import { useEffect, useState } from "react";
import { WizNavList } from "@/config/nav/wiz-nav";

export const WizHeader = ({ NavigateComponent }) => {
  const [currentPath, setCurrentPath] = useState("");

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setCurrentPath(tabValue);
    } else {
      setCurrentPath("getting-started");
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
    <div className="px-4 xl:px-8 py-6 flex justify-center items-center">
      <div className="flex justify-center items-center gap-3 xl:gap-4">
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
                  "w-7 h-7 rounded-full border flex justify-center items-center",
                  getSerial(currentPath) >= item.serial
                    ? "text-button-secondary border-brand bg-brand"
                    : "text-text-secondary border-border"
                )}
              >
                {item.serial}
              </p>
              <p
                className={cn(
                  getSerial(currentPath) >= item.serial
                    ? "text-text"
                    : "text-text-secondary"
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
    </div>
  );
};
