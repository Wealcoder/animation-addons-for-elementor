import { SquareArrowUp } from "lucide-react";
import {
  RiImageCircleLine,
  RiLayout3Line,
  RiSettings2Line,
  RiTextSnippet,
  RiVipCrown2Line,
} from "react-icons/ri";
import QuickAccessCard from "../shared/QuickAccessCard";
import { cn } from "@/lib/utils";

const AccessData = [
  {
    title: "Global Settings",
    subTitle: "Customize global settings",
    url: WCF_ADDONS_ADMIN.global_settings_url,
    icon: <RiSettings2Line size={22} className="text-[#46A1FF]" />,
  },
  {
    title: "Theme Builder",
    subTitle: "Customize theme builder",
    url: WCF_ADDONS_ADMIN.theme_builder_url,
    icon: <RiLayout3Line size={22} className="text-[#7772FC]" />,
  },
  {
    title: "Pro Widget",
    subTitle: "Customize pro widgets",
    url: "",
    icon: <RiVipCrown2Line size={22} className="text-[#FFA132]" />,
  },
  {
    title: "Popup",
    subTitle: "Customize popups",
    url: "",
    icon: <SquareArrowUp size={22} className="text-[#A281FF]" />,
  },
  {
    title: "Custom Icons",
    subTitle: "Customize custom icons",
    url: "",
    icon: <RiImageCircleLine size={22} />,
  },
  {
    title: "Custom Fonts",
    subTitle: "Customize custom fonts",
    url: "",
    icon: <RiTextSnippet size={22} />,
  },
];

const QuickAccess = () => {
  return (
    <div className="border rounded-2xl p-5">
      <div className="grid grid-cols-3">
        {AccessData.map((item, i) => (
          <div
            key={`quick_access-${i}`}
            className={cn(
              "px-4 border-r [&:nth-child(3n)]:border-r-0 border-border-secondary [&>div]:border-t [&>div]:pb-4 [&:nth-child(-n+3)>div]:pt-1 [&:nth-child(-n+3)>div]:border-t-0"
            )}
          >
            <QuickAccessCard
              item={item}
              className={"border-border-secondary pt-4"}
            />
          </div>
        ))}
      </div>
    </div>
  );
};

export default QuickAccess;
