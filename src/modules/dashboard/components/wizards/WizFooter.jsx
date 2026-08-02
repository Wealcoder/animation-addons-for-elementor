import { useEffect, useState } from "react";
import { Button } from "../ui/button";
import {
  useAtomicExtensions,
  useAtomicWidgets,
  useExtensions,
  useSkip,
  useWidgets,
} from "@/hooks/app.hooks";
import { WizNavList } from "@/config/nav/wiz-nav";
import { cn } from "@/lib/utils";
import { flattenAtomicWidgets } from "@/lib/atomicWidgetService";
import { flattenAtomicExtensions } from "@/lib/atomicExtensionService";

const WizFooter = ({ NavigateComponent }) => {
  const [currentPath, setCurrentPath] = useState("");

  const { allExtensions } = useExtensions();
  const { allWidgets } = useWidgets();
  const { allAtomicWidgets } = useAtomicWidgets();
  const { allAtomicExtensions } = useAtomicExtensions();
  const { setIsSkipTerms } = useSkip();

  // Which registry the widget/extension steps actually rendered. Must match
  // WizWidget/WizExtension's own test, or the wizard shows one list and saves
  // the other.
  const hasAtomicWidgets =
    Object.keys(allAtomicWidgets?.elements || {}).length > 0;
  const hasAtomicExtensions =
    Object.keys(allAtomicExtensions?.elements || {}).length > 0;

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

  const getSerial = (path) => {
    const result = WizNavList.find((item) => item.path === path);
    return result ? result.serial : 1;
  };

  const post = async (body) =>
    fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams(body),
    }).then((response) => response.json());

  /*
   * The v4 path deliberately does NOT write wcf_save_widgets /
   * wcf_save_extensions — not even as an empty array.
   *
   * maybe_enable_used_v3_widgets() (class-animation-settings.php, admin_init
   * prio 12) only rescues a site whose v3 option has NEVER been written; an
   * empty array reads as "the user turned everything off on purpose" and is
   * left alone. Writing one here would disarm that rescue, and an imported
   * starter template built on v3 widgets would then render COMPLETELY BLANK —
   * an unregistered widget emits no markup at all, not even a wrapper.
   *
   * Leaving the key absent gives the same user-visible result (nothing v3 is
   * active) while keeping the safety net.
   */
  const saveWidget = async () => {
    if (hasAtomicWidgets) {
      return post({
        action: "aae_save_atomic_widgets",
        fields: JSON.stringify(flattenAtomicWidgets(allAtomicWidgets)),
        nonce: WCF_ADDONS_ADMIN.nonce,
      });
    }

    return post({
      action: "save_settings_with_ajax",
      fields: JSON.stringify(allWidgets),
      nonce: WCF_ADDONS_ADMIN.nonce,
      settings: "wcf_save_widgets",
    });
  };

  const saveExtension = async () => {
    if (hasAtomicExtensions) {
      return post({
        action: "aae_save_atomic_extensions",
        fields: JSON.stringify(flattenAtomicExtensions(allAtomicExtensions)),
        nonce: WCF_ADDONS_ADMIN.nonce,
      });
    }

    return post({
      action: "save_settings_with_ajax",
      fields: JSON.stringify(allExtensions),
      nonce: WCF_ADDONS_ADMIN.nonce,
      settings: "wcf_save_extensions",
    });
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

        // save_settings_with_ajax used to flip wcf_addons_setup_wizard as a
        // side effect, so the v3 path got this for free. The atomic handlers
        // don't, and an unflipped flag means class-plugin.php redirects the
        // user straight back into the wizard on every admin page load.
        post({
          action: "aae_complete_setup_wizard",
          nonce: WCF_ADDONS_ADMIN.nonce,
        });
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
        {getSerial(currentPath) > 1 ? (
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
        ) : (
          ""
        )}

        <Button
          className={cn(
            "ps-[18px] pe-[14px]",
            getSerial(currentPath) == 1 &&
              "bg-button-secondary-hover/80 text-button-text-secondary/80 hover:bg-button-secondary-hover/80 hover:text-button-text-secondary/80 border border-button-secondary-hover shadow-none"
          )}
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
                fill={cn(getSerial(currentPath) == 1 ? "#52586666" : "#FFFFFF")}
              />
            </svg>
          </span>
        </Button>
      </div>
    </div>
  );
};

export default WizFooter;
