import { cn } from "@/lib/utils";
import TemplateTopBg from "../../../public/images/wizard/template-top-bg.png";
import { buttonVariants } from "@/components/ui/button";
import { RiVipCrown2Line } from "react-icons/ri";

const WizPro = () => {
  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat pb-6"
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[50px] px-[60px] w-[1248px] mx-auto space-y-[22px]">
            <div className="flex justify-between items-center">
              <div>
                <h1 className="text-[40px] font-medium">
                  Unlock Every Pro Features!
                </h1>
                <p className="text-lg text-text-secondary mt-2">
                  Gain full access to all widgets, extensions, templates and
                  every features.
                </p>
              </div>
              <div>
                <a
                  href="https://animation-addons.com/"
                  className={cn(buttonVariants({ variant: "pro" }))}
                >
                  <span className="me-2 flex">
                    <RiVipCrown2Line size={20} />
                  </span>
                  Get Pro Version
                </a>
              </div>
            </div>
            <div className="mt-[64px] grid grid-cols-2 gap-1">
              <div></div>
              <div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizPro;
