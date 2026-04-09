import { __ } from "@wordpress/i18n";
import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../../../public/images/hero-banner.jpg";
import QuickAccess from "@/components/dashboard/QuickAccess";
import { RiH2 } from "react-icons/ri";

function isInOfferPeriod() {
  const today = new Date();
  const currentYear = today.getFullYear();

  // Note: Months are 0-indexed in JavaScript (0 = January, 10 = November, 11 = December)
  const offerStart = new Date(2025, 11, 1); // 1 December 2025
  const offerEnd = new Date(2025, 11, 7); // 7 December 2025

  return today >= offerStart && today <= offerEnd;
}

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      {WCF_ADDONS_ADMIN.hero !== "no" ? (
        <div className="relative">
          <img
            src={WCF_ADDONS_ADMIN.hero}
            className="w-full h-full rounded-[10px]"
            alt={__("Banner", "animation-addons-for-elementor")}
          />
          <Badge
            className="absolute bottom-[34px] right-[20px] bg-white"
            variant="version"
          >
            {__("Ver", "animation-addons-for-elementor") + "."}{" "}
            {WCF_ADDONS_ADMIN?.version}
          </Badge>
        </div>
      ) : isInOfferPeriod() ? (
        <a href="https://animation-addons.com/pricing" target="_blank">
          <div className="relative">
            {/* <img
              src={WCF_ADDONS_ADMIN.hero_offer}
              className="w-full h-full rounded-[10px]"
              alt={__("Banner", "animation-addons-for-elementor")}
            /> */}
            <video
              src={WCF_ADDONS_ADMIN.hero_offer}
              autoPlay
              loop
              muted
              playsInline
              controls={false}
              className="w-full"
            />
            <Badge
              className="absolute bottom-[34px] right-[20px] bg-white"
              variant="version"
            >
              {__("Ver.", "animation-addons-for-elementor")}{" "}
              {WCF_ADDONS_ADMIN?.version}
            </Badge>
          </div>
        </a>
      ) : (
        <div className="relative">
          <img
            src={HeroBanner}
            className="w-full h-full rounded-[10px]"
            alt={__("Banner", "animation-addons-for-elementor")}
          />
          <Badge
            className="absolute bottom-[34px] right-[20px] bg-white"
            variant="version"
          >
            {__("Ver.", "animation-addons-for-elementor")}{" "}
            {WCF_ADDONS_ADMIN?.version}
          </Badge>
        </div>
      )}

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
