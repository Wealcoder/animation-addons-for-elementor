import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../public/images/hero-banner.jpg";
import QuickAccess from "@/components/dashboard/QuickAccess";

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      <div className="relative">
        <img
          src={HeroBanner}
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
