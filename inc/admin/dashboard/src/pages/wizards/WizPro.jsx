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
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[50px] max-w-[1248px] mx-auto">
            <div className="mx-5">
              <div className="flex justify-between items-center gap-20">
                <div>
                  <h1 className="text-[40px] font-medium">
                    Access All Pro Features!
                  </h1>
                  <p className="text-lg text-[#525866] mt-2">
                    Enjoy unlimited use of all the premium widgets, extensions,
                    and templates.
                  </p>
                </div>
                <div>
                  <a
                    href="https://animation-addons.com/"
                    target="_blank"
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
                  className="bg-no-repeat h-[395px] xl:h-[458px] rounded-2xl border-[10px] border-white flex flex-col justify-between"
                  style={{ backgroundImage: `url(${ProWiz})` }}
                >
                  <div className="px-5 pt-6 xl:ps-[32px] xl:pe-[41px] xl:pt-[32px]">
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
                      70+ Pro Widgets
                    </span>
                    <h2 className="mt-6 text-[24px] font-medium">
                      Achieve maximum functionality of your websites with 70+
                      premium widgets.
                    </h2>
                  </div>
                  <div className="w-full">
                    <img src={ProWizItem} alt="Widget item" />
                  </div>
                </div>
                <div
                  className="bg-no-repeat h-[395px] xl:h-[458px] rounded-2xl border-[10px] border-white flex flex-col justify-between"
                  style={{ backgroundImage: `url(${ProExt})` }}
                >
                  <div className="px-5 pt-6 xl:ps-[32px] xl:pe-[41px] xl:pt-[32px]">
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
                          d="M3.60059 7.57856C3.60059 9.77607 5.38202 11.5575 7.57953 11.5575C9.77704 11.5575 11.5585 9.77607 11.5585 7.57856C11.5585 5.38105 9.77704 3.59961 7.57953 3.59961C5.38202 3.59961 3.60059 5.38105 3.60059 7.57856ZM3.60059 16.4207C3.60059 18.6182 5.38202 20.3996 7.57953 20.3996C9.77704 20.3996 11.5585 18.6182 11.5585 16.4207C11.5585 14.2231 9.77704 12.4417 7.57953 12.4417C5.38202 12.4417 3.60059 14.2231 3.60059 16.4207ZM12.4427 16.4207C12.4427 18.6182 14.2241 20.3996 16.4216 20.3996C18.6192 20.3996 20.4006 18.6182 20.4006 16.4207C20.4006 14.2231 18.6192 12.4417 16.4216 12.4417C14.2241 12.4417 12.4427 14.2231 12.4427 16.4207ZM9.79006 7.57856C9.79006 8.79939 8.80037 9.78908 7.57953 9.78908C6.35869 9.78908 5.36901 8.79939 5.36901 7.57856C5.36901 6.35772 6.35869 5.36803 7.57953 5.36803C8.80037 5.36803 9.79006 6.35772 9.79006 7.57856ZM9.79006 16.4207C9.79006 17.6415 8.80037 18.6312 7.57953 18.6312C6.35869 18.6312 5.36901 17.6415 5.36901 16.4207C5.36901 15.1998 6.35869 14.2101 7.57953 14.2101C8.80037 14.2101 9.79006 15.1998 9.79006 16.4207ZM18.6322 16.4207C18.6322 17.6415 17.6425 18.6312 16.4216 18.6312C15.2008 18.6312 14.2111 17.6415 14.2111 16.4207C14.2111 15.1998 15.2008 14.2101 16.4216 14.2101C17.6425 14.2101 18.6322 15.1998 18.6322 16.4207ZM15.5374 11.1154V8.46277H12.8848V6.69435H15.5374V4.04171H17.3058V6.69435H19.9585V8.46277H17.3058V11.1154H15.5374Z"
                          fill="#FC6848"
                        />
                      </svg>
                      20+ Pro GSAP Extensions
                    </span>
                    <h2 className="mt-6 text-[24px] font-medium">
                      Boost your website's advanced animation capabilities with
                      20+ Pro GSAP extensions.
                    </h2>
                  </div>
                  <div className="xl:px-[50px]">
                    <img src={ProExtItem} alt="Extension item" />
                  </div>
                </div>
              </div>
              <div className="mt-1">
                <div
                  className="border-[10px] border-white rounded-2xl bg-no-repeat px-[70px] pt-[80px] pb-[64px] overflow-hidden"
                  style={{ backgroundImage: `url(${ProBg})` }}
                >
                  <div className="max-w-[472px]">
                    <h2 className="text-[42px] font-medium">
                      Explore Diverse Pre-built Websites.
                    </h2>
                    <p className="text-base text-text-secondary w-[436px] mt-4">
                      Find a wide collection of pre-designed websites that offer
                      seamless functionality, modern designs, and full
                      customization.
                    </p>
                    <div className="mt-10 flex items-center gap-9">
                      <div>
                        <h3 className="text-[42px] text-text">100+</h3>
                        <p className="text-xs text-text-secondary uppercase mt-2.5">
                          Pre-built Websites
                        </p>
                      </div>
                      <div>
                        <h3 className="text-[42px] text-text">20+</h3>
                        <p className="text-xs text-text-secondary uppercase mt-2.5">
                          Different Categories
                        </p>
                      </div>
                    </div>
                  </div>
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
