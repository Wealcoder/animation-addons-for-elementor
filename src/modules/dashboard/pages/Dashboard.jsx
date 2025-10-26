import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../../../public/images/dashboard-hero-banner.png";
import QuickAccess from "@/components/dashboard/QuickAccess";

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      {WCF_ADDONS_ADMIN.hero !== "no" ? (
        <div className="relative">
          <img
            src={WCF_ADDONS_ADMIN.hero}
            className="w-full h-full rounded-[10px]"
            alt="Banner"
          />
          <Badge
            className="absolute bottom-[34px] right-[20px]"
            variant="version"
          >
            Ver. {WCF_ADDONS_ADMIN?.version}
          </Badge>
        </div>
      ) : (
        <div className="relative">
          <video
            src={
              window.innerWidth < 768
                ? WCF_ADDONS_ADMIN.video_link.mobile
                : WCF_ADDONS_ADMIN.video_link.desktop
            }
            className="w-full h-full rounded-[10px]"
            poster={HeroBanner}
            autoPlay
            loop
            muted
            playsInline
            controls={false}
          />
          <Badge
            className="absolute bottom-[10px] left-[6px]"
            variant="version"
          >
            Ver. {WCF_ADDONS_ADMIN?.version}
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
