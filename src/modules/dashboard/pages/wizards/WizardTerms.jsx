import TemplateTopBg from "../../../../../public/images/wizard/template-top-bg.png";
import AnimationAddonLogo from "../../../../../public/images/Logo-2.png"; 
import Shape1 from "../../../../../public/images/wizard/shape1.png";
import Shape2 from "../../../../../public/images/wizard/shape2.png";
import Shape3 from "../../../../../public/images/wizard/shape3.png";
import Shape4 from "../../../../../public/images/wizard/shape4.png";
import Shape5 from "../../../../../public/images/wizard/shape5.png";
import Shape6 from "../../../../../public/images/wizard/shape6.png";
import CredentialAlert from "@/components/wizards/CredentialAlert";
import  WizShaped  from "@/components/wizards/WizShaped";
import { Checkbox } from "@/components/ui/checkbox";
import { useEffect, useState } from "react";
import { useSkip } from "@/hooks/app.hooks";

import { Button } from "@/components/ui/button";
import { WizNavList } from "@/config/nav/wiz-nav";


const WizardTerms = () => {
  const { isSkipTerms, setIsSkipTerms } = useSkip();
  const [currentPath, setCurrentPath] = useState("getting-started");

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setCurrentPath(tabValue);
    } else {
      setCurrentPath("terms");
    }
  }, [urlParams]);
  
  const gotoDashboard = () => {
    setTimeout(() => {
      window.location.href = `${WCF_ADDONS_ADMIN.adminURL}/admin.php?page=wcf_addons_settings&tab=dashboard`;
    }, 100);
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
  

  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="rounded-lg relative">
        <div className="flex items-center justity-center min-h-[75vh] bg-no-repeat bg-container pb-6">
          <div className="p-8 max-w-[730px] mx-auto text-center flex flex-col gap-3 bg-white ml-[25%] mr-[25%] rounded-[24px] shadow-[0_14px_59px_0_rgba(217,202,180,0.25)]">
            <div className="bg-white rounded-[24px] relative top-[-60px] py-[5px] ps-2 pe-2.5 mx-auto max-w-[180px] flex justify-center items-center gap-1.5 shadow-[0px -25px 59px 0px #D9CAB41A]">
              {/* <span className="flex justify-center items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 16 16"
                  fill="none"
                >
                  <g clip-path="url(#clip0_2780_1024)">
                    <path
                      d="M7.07627 11.8641L7.66133 10.5239C8.18207 9.33139 9.11926 8.38206 10.2883 7.86313L11.8988 7.14826C12.4108 6.92099 12.4108 6.17611 11.8988 5.94884L10.3386 5.25629C9.13946 4.72401 8.18546 3.73953 7.67367 2.50629L7.081 1.07815C6.86106 0.548169 6.12879 0.548171 5.90887 1.07815L5.31618 2.50627C4.80438 3.73953 3.85035 4.72401 2.65123 5.25629L1.09105 5.94884C0.579024 6.17611 0.579024 6.92099 1.09105 7.14826L2.70153 7.86313C3.87059 8.38206 4.80781 9.33139 5.3285 10.5239L5.9136 11.8641C6.13851 12.3791 6.85133 12.3791 7.07627 11.8641ZM12.9343 15.1269L13.0988 14.7498C13.3921 14.0774 13.9205 13.542 14.5797 13.2491L15.0866 13.0239C15.3608 12.9021 15.3608 12.5036 15.0866 12.3818L14.6081 12.1691C13.9319 11.8687 13.3941 11.3135 13.1057 10.6183L12.9368 10.2107C12.819 9.92673 12.4263 9.92673 12.3085 10.2107L12.1396 10.6183C11.8513 11.3135 11.3135 11.8687 10.6373 12.1691L10.1587 12.3818C9.8846 12.5036 9.8846 12.9021 10.1587 13.0239L10.6657 13.2491C11.3249 13.542 11.8532 14.0774 12.1465 14.7498L12.3111 15.1269C12.4315 15.403 12.8138 15.403 12.9343 15.1269Z"
                      fill="#FC6848"
                    />
                  </g>
                  <defs>
                    <clipPath id="clip0_2780_1024">
                      <rect width="16" height="16" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
              </span> */}
              {/* <p className="text-sm font-medium">
                Version {WCF_ADDONS_ADMIN?.version}
              </p> */}
              <div className="w-[150px]">
                <img src={AnimationAddonLogo} alt="Animation Addon Logo" />
              </div>
            </div>
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Starting Your Animation Journey with the Addon
            </h1>
            <div className="p-6">
              <p className="text-base text-text-secondary text-center mt-[7px] mb-6">
             Thank you for choosing Animation Addons for Elementor. 
             Follow these simple steps of easy setup wizard & enjoy your Elementor web-building experience now!
              </p>
            </div>
          
            <div className="flex items-center justify-center gap-3">
              <a
                href={"#"}
                className="secondary underline underline-offset-4"
              >
                Skip This Step
              </a>
                <Button
                  className="w-[249px] h-[46px] gap-1.5 rounded-full"
                   onClick={() => goToContinue(currentPath)}
                >
                  Proceed To Next Step{" "}
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                  >
                    <path
                      d="M11.1248 10.3033L7 6.17851L8.17852 5L13.4818 10.3033L8.17852 15.6066L7 14.4281L11.1248 10.3033Z"
                      fill="white"
                    />
                  </svg>
                </Button>
            </div>
          </div>
        </div>

        {/* shapes  */}
        <WizShaped/>
      </div>
    </div>
  );
};


export default WizardTerms;
