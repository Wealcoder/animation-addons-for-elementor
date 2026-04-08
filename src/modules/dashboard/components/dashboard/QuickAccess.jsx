import { __ } from "@wordpress/i18n";
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
    title: __("Global Settings", "animation-addons-for-elementor"),
    subTitle: __("Customize global settings", "animation-addons-for-elementor"),
    url: WCF_ADDONS_ADMIN.global_settings_url,
    icon: <RiSettings2Line size={22} className="text-[#46A1FF]" />,
  },
  {
    title: __("Theme Builder", "animation-addons-for-elementor"),
    subTitle: __("Customize theme builder", "animation-addons-for-elementor"),
    url: WCF_ADDONS_ADMIN.theme_builder_url,
    icon: <RiLayout3Line size={22} className="text-[#7772FC]" />,
  },
  {
    title: __("Pro Widgets", "animation-addons-for-elementor"),
    subTitle: __("Customize pro widgets", "animation-addons-for-elementor"),
    url: `${WCF_ADDONS_ADMIN.adminURL}/admin.php?page=wcf_addons_settings&tab=widgets&filter=pro`,
    icon: <RiVipCrown2Line size={22} className="text-[#FFA132]" />,
  },
  {
    title: __("Custom Fonts", "animation-addons-for-elementor"),
    slug: "custom-fonts",
    subTitle: __("Upload Custom fonts", "animation-addons-for-elementor"),
    url: `${WCF_ADDONS_ADMIN.adminURL}/edit.php?post_type=wcf-custom-fonts`,
    icon: <RiTextSnippet size={22} className="text-[#A281FF]" />,
  },
  {
    title: __("Popup", "animation-addons-for-elementor"),
    subTitle: __("Customize popups", "animation-addons-for-elementor"),
    url: `${WCF_ADDONS_ADMIN.adminURL}/admin.php?page=wcf_addons_settings&tab=extensions&cTab=general#popup`,
    icon: <SquareArrowUp size={22} className="text-[#A281FF]" />,
  },
  {
    title: __("Custom Icons", "animation-addons-for-elementor"),
    subTitle: __("Upload custom icons", "animation-addons-for-elementor"),
    url: `${WCF_ADDONS_ADMIN.adminURL}/admin.php?page=wcf_addons_settings&tab=extensions&cTab=general#custom-icon`,
    icon: <RiImageCircleLine size={22} className="text-[#A281FF]" />,
  },
];

const QuickAccess = () => {
  return (
    <div className="border rounded-2xl p-5">
      <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
        {AccessData.map((item, i) => (
          <div
            key={`quick_access-${i}`}
            className={cn(
              "px-4 border-0 lg:border-r lg:[&:nth-child(2n)]:border-r-0 xl:[&:nth-child(2n)]:border-r xl:[&:nth-child(3n)]:border-r-0 border-border-secondary [&>div]:border-t [&>div]:pb-4 lg:[&:nth-child(-n+2)>div]:pt-1 xl:[&:nth-child(-n+3)>div]:pt-1 [&:nth-child(-n+1)>div]:border-t-0 lg:[&:nth-child(-n+2)>div]:border-t-0 xl:[&:nth-child(-n+3)>div]:border-t-0"
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
