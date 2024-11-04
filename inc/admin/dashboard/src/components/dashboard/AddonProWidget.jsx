import { cn } from "@/lib/utils";
import { RiArrowRightUpLine, RiVipCrown2Line } from "react-icons/ri";
import { Link } from "react-router-dom";
import { buttonVariants } from "../ui/button";
import { Separator } from "../ui/separator";
import { FeaturedWidgetProList } from "@/config/data/featuredWidgetProList";
import WidgetCard from "../shared/WidgetCard";

const AddonProWidget = () => {
  const widgets = FeaturedWidgetProList;
  return (
    <div className="col-span-2 border border-solid border-border rounded-2xl p-5 shadow-common">
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center">
          <RiVipCrown2Line size={20} color="#FFA132" />
          <p className="font-medium">WCF Addons Pro Widgets</p>
        </div>
        <div>
          <Link
            to={"#"}
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            All Pro Widgets <RiArrowRightUpLine size={18} className="ml-1" />
          </Link>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div className="grid grid-cols-2 justify-between gap-2.5 p-3 bg-background-secondary rounded-lg">
        {widgets?.map((widget, i) => (
          <div key={`featured_pro-widget-${i}`}>
            <WidgetCard widget={widget} />
          </div>
        ))}
      </div>
    </div>
  );
};

export default AddonProWidget;
