import ShowWizWidgets from "@/components/wizards/ShowWizWidgets";
import WidgetTopBg from "../../../public/images/wizard/widget-top-bg.png";
import { useSkip } from "@/hooks/app.hooks";
import { useEffect } from "react";

const WizWidget = () => {
  const { isSkipTerms } = useSkip();

  const API_ENDPOINT =
    "https://www.themecrowdy.com/wp-json/fluent-crm/v2/subscribers";
  const AUTH_USERNAME = "rayhan";
  const AUTH_PASSWORD = "f1R7mK7WnjK17x7XbcxVH6b5";
  const authHeader = btoa(`${AUTH_USERNAME}:${AUTH_PASSWORD}`);
  const extract_name = extractNameFromEmail(WCF_ADDONS_ADMIN.user.email);
  const subscriberData = {
    first_name:
      WCF_ADDONS_ADMIN.user.f_name == ""
        ? extract_name
        : WCF_ADDONS_ADMIN.user.f_name,
    last_name: "",
    email: WCF_ADDONS_ADMIN.user.email,
    tags: ["animation-addon"],
    lists: [1, 4],
    status: "subscribed",
  };

  function extractNameFromEmail(email) {
    const nameParts = email.split("@")[0].split(".");
    const name = nameParts
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(" ");
    return name;
  }

  // Make the POST request
  async function addSubscriber() {
    try {
      const response = await fetch(API_ENDPOINT, {
        method: "POST",
        headers: {
          Authorization: `Basic ${authHeader}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(subscriberData),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      await response.json();
      localStorage.setItem("wcfanim_addon_subscribe", "yes");
    } catch (error) {
      localStorage.setItem("wcfanim_addon_subscribe", "yes");
    }
  }

  useEffect(() => {
    if (
      !isSkipTerms &&
      localStorage.getItem("wcfanim_addon_subscribe") != "yes"
    ) {
      addSubscriber();
    }
  }, []);
  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${WidgetTopBg})` }}
        >
          <div className="pt-[120px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Activate Widgets You Want to Use
            </h1>
            <p className="text-lg text-text-secondary">
              Enhance your website's functionality by activating widgets that
              suit your needs.
            </p>
          </div>
          <div className="mt-[56px] max-w-[1184px] mx-auto border-[10px] border-white rounded-lg">
            <ShowWizWidgets />
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizWidget;
