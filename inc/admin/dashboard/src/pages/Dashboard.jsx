import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../public/images/hero-banner.jpg";
import QuickAccess from "@/components/dashboard/QuickAccess";
import { useEffect } from "react";

const Dashboard = () => {
  const fetchFn = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_get_changelog_data",

        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_widgets",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        console.log(return_content);
      });
  };
  const fetchNotice = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_get_notice_data",

        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        console.log(return_content);
      });
  };

  useEffect(async () => {
    fetchFn();
    fetchNotice();
  }, []);

  return (
    <div className="flex flex-col gap-6">
      <div className="relative">
        <img src={HeroBanner} className="w-full h-full" alt="Banner" />
        <Badge
          className="absolute bottom-[34px] right-[25px]"
          variant="version"
        >
          Ver. {WCF_ADDONS_ADMIN?.version}
        </Badge>
      </div>
      <div className="mt-2">
        <QuickAccess />
      </div>
      <div className="grid grid-cols-2 xl:grid-cols-3 gap-6 h-full">
        <Tutorial />
        <Documentation />
      </div>
      <div className="grid grid-cols-2 xl:grid-cols-3 gap-6 h-full">
        <AddonProWidget />
        <RecoPlugins />
      </div>
      <ConnectWithUs />
      <LatestBlog />
    </div>
  );
};

export default Dashboard;
