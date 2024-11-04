import AddonProWidget from "@/components/dashboard/AddonProWidget";
import ConnectWithUs from "@/components/dashboard/ConnectWithUs";
import Documentation from "@/components/dashboard/Documentation";
import LatestBlog from "@/components/dashboard/LatestBlog";
import RecoPlugins from "@/components/dashboard/RecoPlugins";
import Tutorial from "@/components/dashboard/Tutorial";
import { Badge } from "@/components/ui/badge";
import HeroBanner from "../../public/images/hero-banner.jpg";

const Dashboard = () => {
  return (
    <div className="flex flex-col gap-6">
      <div className="relative">
        <img src={HeroBanner} className="w-full h-full" alt="Banner" />
        <Badge
          className="absolute bottom-[34px] right-[25px]"
          variant="version"
        >
          Ver. 1.1
        </Badge>
      </div>
      <div className="mt-2 grid grid-cols-3 gap-6 h-full">
        <Tutorial />
        <Documentation />
      </div>
      <div className="grid grid-cols-3 gap-6 h-full">
        <AddonProWidget />
        <RecoPlugins />
      </div>
      <ConnectWithUs />
      <LatestBlog />
    </div>
  );
};

export default Dashboard;
