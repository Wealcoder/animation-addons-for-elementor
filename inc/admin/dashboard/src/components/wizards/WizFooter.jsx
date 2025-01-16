import { useEffect, useState } from "react";
import { Button } from "../ui/button";
import CredentialAlert from "./CredentialAlert";
import { useExtensions, useWidgets } from "@/hooks/app.hooks";

const NavList = [
  {
    serial: 1,
    title: "Getting Started",
    path: "getting-started",
  },
  {
    serial: 2,
    title: "Widgets",
    path: "widgets",
  },
  {
    serial: 3,
    title: "Extensions",
    path: "extensions",
  },
  {
    serial: 4,
    title: "Templates",
    path: "templates",
  },
  {
    serial: 5,
    title: "Go Pro",
    path: "go-pro",
  },
  {
    serial: 6,
    title: "Congratulations",
    path: "congratulations",
  },
];

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

  if (!(NavList && NavList.length)) return;

  const getSerial = (path) => {
    const result = NavList.find((item) => item.path === path);
    return result ? result.serial : 1;
  };

  const saveWidget = async () => {
    console.log(allWidgets);
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
    console.log(allExtensions);
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

    const value = NavList[getSerial(currentPath)].path;
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

    const value = NavList[getSerial(currentPath) - 2].path;
    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    setCurrentPath(value);
  };

  return (
    <div className="px-6 py-[18px] bg-white flex justify-between items-center gap-11 shadow-[0px_-2px_8px_0px_rgba(10,13,20,0.06)] z-20 relative">
      <div>
        {getSerial(currentPath) === 1 && (
          <div className="flex items-center gap-2.5 bg-[#F5F7FA] ps-3 pe-4 pt-[11px] pb-3 rounded-[10px]">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 20 20"
              fill="none"
            >
              <g clip-path="url(#clip0_4477_529)">
                <path
                  d="M10.0001 19.091C4.97932 19.091 0.90918 15.0208 0.90918 10.0001C0.90918 4.97932 4.97932 0.90918 10.0001 0.90918C15.0208 0.90918 19.091 4.97932 19.091 10.0001C19.091 15.0208 15.0208 19.091 10.0001 19.091ZM10.0001 7.72736C10.7532 7.72736 11.3637 7.11684 11.3637 6.36372C11.3637 5.61061 10.7532 5.00009 10.0001 5.00009C9.247 5.00009 8.63645 5.61061 8.63645 6.36372C8.63645 7.11684 9.247 7.72736 10.0001 7.72736ZM11.8183 12.7274H10.9092V8.63645H8.18191V10.4546H9.091V12.7274H8.18191V14.5455H11.8183V12.7274Z"
                  fill="#717784"
                />
              </g>
              <defs>
                <clipPath id="clip0_4477_529">
                  <rect width="20" height="20" fill="white" />
                </clipPath>
              </defs>
            </svg>
            <p>By continuing, you allow this plugin to collect your data.</p>
            <CredentialAlert />
          </div>
        )}
      </div>

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
