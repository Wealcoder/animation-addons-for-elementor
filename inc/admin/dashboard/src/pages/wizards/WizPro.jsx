import { cn } from "@/lib/utils";
import TemplateTopBg from "../../../public/images/wizard/template-top-bg.png";
import ProWiz from "../../../public/images/wizard/pro-wiz.png";
import ProExt from "../../../public/images/wizard/pro-ext.png";
import ProBg from "../../../public/images/wizard/pro-bg.png";
import ProWizItem from "../../../public/images/wizard/pro-wiz-item.png";
import ProExtItem from "../../../public/images/wizard/pro-ext-item.png";
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
          <div className="pt-[50px] px-[60px] w-[1248px] mx-auto">
            <div className="flex justify-between items-center">
              <div>
                <h1 className="text-[40px] font-medium">
                  Unlock Every Pro Features!
                </h1>
                <p className="text-lg text-[#525866] mt-2">
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
            <div className="mt-[54px] grid grid-cols-2 gap-1">
              <div
                className="bg-no-repeat h-[458px] rounded-2xl border-[10px] border-white flex flex-col justify-between"
                style={{ backgroundImage: `url(${ProWiz})` }}
              >
                <div className="ps-[32px] pe-[41px] pt-[32px]">
                  <span className="flex items-center  text-base text-text">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      className="mr-2"
                      viewBox="0 0 24 24"
                      fill="none"
                    >
                      <path
                        d="M10 8H14V6.5C14 4.567 15.567 3 17.5 3C19.433 3 21 4.567 21 6.5C21 8.433 19.433 10 17.5 10H16V14H17.5C19.433 14 21 15.567 21 17.5C21 19.433 19.433 21 17.5 21C15.567 21 14 19.433 14 17.5V16H10V17.5C10 19.433 8.433 21 6.5 21C4.567 21 3 19.433 3 17.5C3 15.567 4.567 14 6.5 14H8V10H6.5C4.567 10 3 8.433 3 6.5C3 4.567 4.567 3 6.5 3C8.433 3 10 4.567 10 6.5V8ZM8 8V6.5C8 5.67157 7.32843 5 6.5 5C5.67157 5 5 5.67157 5 6.5C5 7.32843 5.67157 8 6.5 8H8ZM8 16H6.5C5.67157 16 5 16.6716 5 17.5C5 18.3284 5.67157 19 6.5 19C7.32843 19 8 18.3284 8 17.5V16ZM16 8H17.5C18.3284 8 19 7.32843 19 6.5C19 5.67157 18.3284 5 17.5 5C16.6716 5 16 5.67157 16 6.5V8ZM16 16V17.5C16 18.3284 16.6716 19 17.5 19C18.3284 19 19 18.3284 19 17.5C19 16.6716 18.3284 16 17.5 16H16ZM10 10V14H14V10H10Z"
                        fill="#FC6848"
                      />
                    </svg>
                    60+ Pro Widgets
                  </span>
                  <h2>
                    Unlock full potential of your website with 60+ Premium
                    Widgets by enhancing interactivity.
                  </h2>
                </div>
                <div>
                  <img src={ProWizItem} alt="Widget item" />
                </div>
              </div>
              <div
                className="bg-no-repeat h-[458px] rounded-2xl border-[10px] border-white"
                style={{ backgroundImage: `url(${ProExt})` }}
              ></div>
            </div>
            <div className="mt-1">
              <div
                className="border-[10px] border-white rounded-lg bg-no-repeat px-[70px] pt-[80px] pb-[64px]"
                style={{ backgroundImage: `url(${ProBg})` }}
              >
                <div className="max-w-[472px]">
                  <h2 className="text-[42px] font-medium">
                    Explore Wide Collection of Stunning Templates.
                  </h2>
                  <p className="text-base text-text-secondary w-[436px] mt-4">
                    Find the perfect design for your website with our diverse
                    range of customizable, high-quality templates that fit any
                    style or purpose.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizPro;
