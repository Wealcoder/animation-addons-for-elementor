import { RiArrowRightUpLine, RiVipCrown2Line } from "react-icons/ri";
import { Separator } from "../ui/separator";
import WidgetCard from "../shared/WidgetCard";
import { useState } from "react";
import { toast } from "sonner";
import { useActiveItem } from "@/hooks/app.hooks";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";

const AddonProWidget = () => {
  const [widgets, setWidgets] = useState(
    WCF_ADDONS_ADMIN.addons_config.dashboardProWidget
  );

  const { updateActiveWidget: proWidget } = useActiveItem();

  const updateActiveWidget = async (data) => {
    const result = Object.fromEntries(
      Object.entries(widgets || {}).filter(([key, value]) => {
        if (key === data.slug) {
          value.is_active = data.value;
          return [key, value];
        } else {
          return [key, value];
        }
      })
    );
    setWidgets(result);

    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_settings_with_ajax_dashboard",
        fields: JSON.stringify(result),
        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_widgets",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        proWidget(data);
        toast.success("Save Successful", {
          position: "top-right",
        });
      });
  };

  return (
    <div className="col-span-2 border rounded-2xl p-5 shadow-common">
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center">
          <RiVipCrown2Line size={20} color="#FFA132" />
          <p className="font-medium">AAE Addons Pro Widgets</p>
        </div>
        <div>
          <a
            href={"https://animation-addons.com/docs/changelogs/"}
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            Changelog <RiArrowRightUpLine size={18} className="ml-1" />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div className="grid grid-cols-2 justify-between gap-2.5 p-3 bg-background-secondary rounded-lg">
        {Object.keys(widgets)?.map((content, i) => (
          <React.Fragment key={`tab_content-${i}`}>
            <WidgetCard
              widget={widgets[content]}
              slug={content}
              updateActiveItem={updateActiveWidget}
            />
          </React.Fragment>
        ))}
      </div>
    </div>
  );
};

export default AddonProWidget;
