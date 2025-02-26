import { useEffect, useState } from "react";
import { Button } from "../ui/button";
import { useExtensions, useWidgets } from "@/hooks/app.hooks";
import { WizNavList } from "@/config/nav/wiz-nav";

const WizFooter = ({ NavigateComponent }) => {
  const { allExtensions } = useExtensions();
  const { allWidgets } = useWidgets();

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

  const getSerial = (path) => {
    const result = WizNavList.find((item) => item.path === path);
    return result ? result.serial : 1;
  };

  const saveWidget = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_settings_with_ajax",
        fields: JSON.stringify(allWidgets),
        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_widgets",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {});
  };

  const saveExtension = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_settings_with_ajax",
        fields: JSON.stringify(allExtensions),
        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_extensions",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {});
  };

  const goToContinue = (currentPath) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;
    if (currentPath === "templates") {
      try {
        saveWidget();
        saveExtension();
      } catch (error) {
        console.log(error);
      }
    }

    const value = WizNavList[getSerial(currentPath)].path;
    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
  };
  const goToBack = (currentPath) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    const value = WizNavList[getSerial(currentPath) - 2].path;
    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
  };

  return (
    <div className="px-6 py-[18px] bg-white flex justify-end items-center gap-11 shadow-[0px_-2px_8px_0px_rgba(10,13,20,0.06)] z-20 relative">
      <div className="flex items-center gap-3">
        {getSerial(currentPath) > 1 && (
          <Button
            variant="secondary"
            className="ps-[14px] pe-[18px]"
            onClick={() => goToBack(currentPath)}
          >
            <span className="flex items-center justify-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 20 20"
                fill="none"
              >
                <path
                  d="M8.87518 10.0006L13 5.87577L11.8215 4.69727L6.51818 10.0006L11.8215 15.3038L13 14.1253L8.87518 10.0006Z"
                  fill="#525866"
                />
              </svg>
            </span>
            Go back
          </Button>
        )}

        <Button
          className="ps-[18px] pe-[14px]"
          onClick={() => goToContinue(currentPath)}
        >
          Continue{" "}
          <span className="flex items-center justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 20 20"
              fill="none"
            >
              <path
                d="M11.1248 10.0006L7 5.87577L8.17852 4.69727L13.4818 10.0006L8.17852 15.3038L7 14.1253L11.1248 10.0006Z"
                fill="white"
              />
            </svg>
          </span>
        </Button>
      </div>
    </div>
  );
};

export default WizFooter;
