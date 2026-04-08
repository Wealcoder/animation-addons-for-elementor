import { __ } from "@wordpress/i18n";
import {
  RiArrowRightUpLine,
  RiCustomerServiceLine,
  RiGroup3Line,
  RiStarLine,
} from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { cn } from "@/lib/utils";

const ConnectWithUs = () => {
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 h-full">
      <div
        className={cn(
          "border rounded-2xl p-5 flex flex-col gap-[18px]",
          hashValue === "wcf-help-and-support"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "shadow-common",
        )}
        id="wcf-help-and-support"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiCustomerServiceLine size={20} color="#1FC16B" />
          <p className="text-[13px]">
            {__("Help & Support", "animation-addons-for-elementor")}
          </p>
        </div>
        <div id="help">
          <h3 className="text-lg font-medium text-start">
            <span dir="ltr">
              {__("Need Any Help?", "animation-addons-for-elementor")}
            </span>
          </h3>

          <p className="text-sm text-text-secondary mt-2">
            <span dir="ltr">
              {__(
                "Feel like you want to consult with an expert? Take live chat support immediately from our",
                "animation-addons-for-elementor",
              )}{" "}
              {
                <a
                  href="https://animation-addons.com"
                  target="_blank"
                  className="text-[#2587EC] underline underline-offset-2"
                >
                  {__("Website", "animation-addons-for-elementor")}
                </a>
              }
              .
            </span>
          </p>
        </div>
        <div>
          <a
            href="https://crowdyflow.ticksy.com/submit"
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            {__("Create a ticket", "animation-addons-for-elementor")}{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px] rtl:rotate-360 rtl:scale-x-[-1]"
              color="#525866"
            />
          </a>
        </div>
      </div>
      <div
        className={cn(
          "border rounded-2xl p-5 flex flex-col gap-[18px]",
          hashValue === "wcf-feedback"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "shadow-common",
        )}
        id="wcf-feedback"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiStarLine size={20} color="#FFA132" />
          <p className="text-[13px]">
            {__("Feedback", "animation-addons-for-elementor")}
          </p>
        </div>
        <div>
          <h3 className="text-lg font-medium">
            {__("Show Your Love", "animation-addons-for-elementor")}
          </h3>
          <p className="text-sm text-text-secondary mt-2">
            <span dir="ltr">
              {__(
                "If you are happy with our product and support, please support us by giving us",
                "animation-addons-for-elementor",
              )}{" "}
              <span className="text-[#FFA132]">★★★★★</span>{" "}
              {__("5 star rating.", "animation-addons-for-elementor")}
            </span>
          </p>
        </div>
        <div>
          <a
            href="https://wordpress.org/plugins/animation-addons-for-elementor/#reviews"
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            {__("Give your feedback", "animation-addons-for-elementor")}{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px] rtl:rotate-360 rtl:scale-x-[-1]"
              color="#525866"
            />
          </a>
        </div>
      </div>
      <div
        className={cn(
          "border rounded-2xl p-5 flex flex-col gap-[18px]",
          hashValue === "wcf-community"
            ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
            : "shadow-common",
        )}
        id="wcf-community"
      >
        <div className="inline-flex items-center gap-2 bg-background-secondary self-start py-1 ps-1.5 pe-2 rounded">
          <RiGroup3Line size={20} color="#7D52F4" />
          <p className="text-[13px]">
            {__("Join Community", "animation-addons-for-elementor")}
          </p>
        </div>
        <div>
          <h3 className="text-lg font-medium">
            {__("Contribute to Us", "animation-addons-for-elementor")}
          </h3>
          <p className="text-sm text-text-secondary mt-2">
            <span dir="ltr">
              {__(
                "Join our community of developers and designers and help us by recommending features.",
                "animation-addons-for-elementor",
              )}
            </span>
          </p>
        </div>
        <div>
          <a
            href="https://www.facebook.com/groups/animationaddons"
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary" }), "w-full")}
          >
            {__("Join Our Community", "animation-addons-for-elementor")}{" "}
            <RiArrowRightUpLine
              size={20}
              className="ml-[6px] rtl:rotate-360 rtl:scale-x-[-1]"
              color="#525866"
            />
          </a>
        </div>
      </div>
    </div>
  );
};

export default ConnectWithUs;
