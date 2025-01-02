import TemplateTopBg from "../../../public/images/wizard/template-top-bg.png";
import TemplateBg1 from "../../../public/images/wizard/template-bg-1.png";
import TemplateBg2 from "../../../public/images/wizard/template-bg-2.png";
import TLogo from "../../../public/images/wizard/t-logo.png";
import TLogo2 from "../../../public/images/wizard/t-logo-2.png";
import TempImg1 from "../../../public/images/wizard/temp-img-1.png";
import { Button } from "@/components/ui/button";
import { useState } from "react";

const WizTemplate = () => {
  const [themelabel , setThemelabel] =  useState('Install Theme');
  const themeInstller = async (slug) => {
    setThemelabel('Installing .... ');
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action    : "wcf_installer_theme",
        nonce     : WCF_ADDONS_ADMIN.nonce,
        theme_slug: slug
      }),
    })
      .then((response) => {
        console.log(response);
        return response.text();
      })
      .then((return_content) => {
        setThemelabel('Theme Active');        
        
      });
  };
  
  const installTheme = (slug) => {
    themeInstller(slug);
  };
  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[50px] max-w-[1268px] mx-auto space-y-[22px]">
            <div
              className="border-[10px] border-white rounded-lg bg-no-repeat px-[80px] mx-5 pt-[64px] pb-[74px]"
              style={{ backgroundImage: `url(${TemplateBg1})` }}
            >
              <div className="max-w-[470px] space-y-6">
                <img src={TLogo} alt="TLogo" className="w-[36px] h-[36px]" />
                <h2 className="text-[40px] font-medium">
                  Explore Over 40+ Stunning Templates.
                </h2>
                <p className="text-base text-text-secondary">
                  Explore 40+ customizable templates of various niches designed
                  to fit any website, helping you build.
                </p>
              </div>
              <div className="max-w-[470px] space-y-3 mt-7">
                <div className="flex gap-4 items-center bg-white px-5 py-4 rounded-xl">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                  >
                    <path
                      d="M7.7476 6.79221L22.7476 12.0422C22.8895 12.0918 23.0133 12.1829 23.1028 12.3037C23.1923 12.4245 23.2435 12.5694 23.2496 12.7196C23.2558 12.8698 23.2166 13.0184 23.1373 13.1461C23.0579 13.2738 22.942 13.3747 22.8046 13.4357L16.3186 16.3187L13.4356 22.8047C13.3767 22.9373 13.2806 23.05 13.1589 23.1291C13.0372 23.2082 12.8952 23.2502 12.7501 23.2502H12.7194C12.5696 23.2436 12.4252 23.1923 12.3049 23.1029C12.1846 23.0134 12.0938 22.89 12.0444 22.7485L6.79435 7.74846C6.74785 7.61542 6.73977 7.47197 6.77105 7.33454C6.80232 7.19712 6.87168 7.07129 6.97118 6.97148C7.07068 6.87167 7.19629 6.80191 7.33362 6.7702C7.47094 6.7385 7.61442 6.74612 7.7476 6.79221Z"
                      fill="#312749"
                    />
                    <path
                      opacity="0.5"
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M7.5 0.75C7.69891 0.75 7.88968 0.829018 8.03033 0.96967C8.17098 1.11032 8.25 1.30109 8.25 1.5V4.5C8.25 4.69891 8.17098 4.88968 8.03033 5.03033C7.88968 5.17098 7.69891 5.25 7.5 5.25C7.30109 5.25 7.11032 5.17098 6.96967 5.03033C6.82902 4.88968 6.75 4.69891 6.75 4.5V1.5C6.75 1.30109 6.82902 1.11032 6.96967 0.96967C7.11032 0.829018 7.30109 0.75 7.5 0.75ZM1.5 6.75H4.5C4.69891 6.75 4.88968 6.82902 5.03033 6.96967C5.17098 7.11032 5.25 7.30109 5.25 7.5C5.25 7.69891 5.17098 7.88968 5.03033 8.03033C4.88968 8.17098 4.69891 8.25 4.5 8.25H1.5C1.30109 8.25 1.11032 8.17098 0.969669 8.03033C0.829018 7.88968 0.75 7.69891 0.75 7.5C0.75 7.30109 0.829018 7.11032 0.969669 6.96967C1.11032 6.82902 1.30109 6.75 1.5 6.75ZM5.24724 9.00882C5.05059 9.00711 4.86114 9.0827 4.71969 9.21932L2.84469 11.0943C2.70807 11.2358 2.63247 11.4252 2.63418 11.6219C2.63589 11.8185 2.71477 12.0066 2.85382 12.1457C2.99288 12.2847 3.18099 12.3636 3.37764 12.3653C3.57429 12.367 3.76374 12.2914 3.90519 12.1548L5.78019 10.2798C5.91681 10.1384 5.9924 9.94892 5.99069 9.75227C5.98899 9.55562 5.91011 9.36751 5.77105 9.22846C5.632 9.0894 5.44389 9.01053 5.24724 9.00882ZM10.2802 5.77982C10.1387 5.91644 9.94929 5.99204 9.75264 5.99033C9.55599 5.98862 9.36788 5.90974 9.22883 5.77069C9.08977 5.63163 9.01089 5.44352 9.00918 5.24687C9.00747 5.05023 9.08307 4.86077 9.21969 4.71932L11.0947 2.84432C11.2361 2.7077 11.4256 2.63211 11.6222 2.63382C11.8189 2.63553 12.007 2.7144 12.1461 2.85346C12.2851 2.99252 12.364 3.18062 12.3657 3.37727C12.3674 3.57392 12.2918 3.76337 12.1552 3.90482L10.2802 5.77982ZM4.71991 5.78068C4.86053 5.92134 5.05126 6.00038 5.25016 6.00043C5.39847 6.0004 5.54345 5.95639 5.66675 5.87398C5.79006 5.79156 5.88617 5.67444 5.94292 5.53742C5.99967 5.40039 6.01452 5.24962 5.9856 5.10415C5.95667 4.95869 5.88527 4.82507 5.78041 4.72018L3.90541 2.84518C3.83623 2.77354 3.75347 2.71641 3.66196 2.6771C3.57046 2.63779 3.47205 2.6171 3.37246 2.61624C3.27288 2.61537 3.17412 2.63435 3.08195 2.67206C2.98977 2.70977 2.90603 2.76546 2.83562 2.83588C2.76519 2.9063 2.7095 2.99004 2.67179 3.08221C2.63408 3.17438 2.61511 3.27314 2.61597 3.37273C2.61684 3.47231 2.63753 3.57073 2.67684 3.66223C2.71614 3.75373 2.77328 3.83649 2.84491 3.90568L4.71991 5.78068Z"
                      fill="#312749"
                    />
                  </svg>
                  <p className="text-base text-text">
                    Instant setup with One Click Demo Import.
                  </p>
                </div>
                <div className="flex gap-4 items-center bg-white px-5 py-4 rounded-xl">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                  >
                    <path
                      d="M19.65 16.2752H17.625V14.2502C17.625 13.8812 17.319 13.5752 16.95 13.5752C16.581 13.5752 16.275 13.8812 16.275 14.2502V16.2752H14.25C13.881 16.2752 13.575 16.5812 13.575 16.9502C13.575 17.3192 13.881 17.6252 14.25 17.6252H16.275V19.6502C16.275 20.0192 16.581 20.3252 16.95 20.3252C17.319 20.3252 17.625 20.0192 17.625 19.6502V17.6252H19.65C20.019 17.6252 20.325 17.3192 20.325 16.9502C20.325 16.5812 20.019 16.2752 19.65 16.2752Z"
                      fill="#312749"
                    />
                    <path
                      d="M21.0001 8.86801V4.782C21.0001 3.513 20.4241 3 18.9931 3H15.3571C13.9261 3 13.3501 3.513 13.3501 4.782V8.85901C13.3501 10.137 13.9261 10.641 15.3571 10.641H18.9931C20.4241 10.65 21.0001 10.137 21.0001 8.86801Z"
                      fill="#312749"
                    />
                    <path
                      d="M10.65 8.86801V4.782C10.65 3.513 10.074 3 8.64301 3H5.007C3.576 3 3 3.513 3 4.782V8.85901C3 10.137 3.576 10.641 5.007 10.641H8.64301C10.074 10.65 10.65 10.137 10.65 8.86801Z"
                      fill="#312749"
                    />
                    <path
                      d="M10.65 18.9926V15.3566C10.65 13.9256 10.074 13.3496 8.64301 13.3496H5.007C3.576 13.3496 3 13.9256 3 15.3566V18.9926C3 20.4236 3.576 20.9996 5.007 20.9996H8.64301C10.074 20.9996 10.65 20.4236 10.65 18.9926Z"
                      fill="#312749"
                    />
                  </svg>
                  <p className="text-base text-text">
                    Templates includes Over 20+ Categories.
                  </p>
                </div>
                <div className="flex gap-4 items-center bg-white px-5 py-4 rounded-xl">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                  >
                    <path
                      d="M2.00073 3.11202C2.00073 2.49841 2.49817 2.00098 3.11178 2.00098H5.33386V4.22306H4.22282V5.33411H2.00073V3.11202Z"
                      fill="#312749"
                    />
                    <path
                      d="M20.8886 2.00098C21.5022 2.00098 21.9996 2.49841 21.9996 3.11202V5.33411H19.7775V4.22306H18.6665V2.00098H20.8886Z"
                      fill="#312749"
                    />
                    <path
                      d="M21.9995 20.3341V13.1123H2.00073V20.3341C2.00073 21.2545 2.74688 22.0007 3.6673 22.0007H20.3329C21.2533 22.0007 21.9995 21.2545 21.9995 20.3341Z"
                      fill="#312749"
                    />
                    <path
                      d="M21.9997 7.55566V10.8888H19.7776V7.55566H21.9997Z"
                      fill="#312749"
                    />
                    <path
                      d="M4.22282 10.8888V7.55566H2.00073V10.8888H4.22282Z"
                      fill="#312749"
                    />
                    <path
                      d="M16.444 2.00098V4.22306H13.1108V2.00098H16.444Z"
                      fill="#312749"
                    />
                    <path
                      d="M10.8895 4.22306V2.00098H7.5564V4.22306H10.8895Z"
                      fill="#312749"
                    />
                  </svg>
                  <p className="text-base text-text">
                    Ready Made Header & Footer Sections.
                  </p>
                </div>
              </div>
            </div>
            <div className="border-[10px] border-white rounded-lg bg-[#F7F9FC] overflow-hidden">
              <div className="grid grid-cols-1 xl:grid-cols-2 gap-4 px-[50px] xl:px-0">
                <div
                  className="-ml-[69px] order-1 hidden xl:flex justify-start bg-no-repeat pt-[55px] pb-[50px] "
                  style={{ backgroundImage: `url(${TemplateBg2})` }}
                >
                  <img
                    src={TempImg1}
                    alt="Template"
                    className="w-[677px] xl:w-[599px] object-cover"
                  />
                </div>
                <div className="-mb-4  order-2 flex xl:hidden justify-center bg-no-repeat pt-[55px]">
                  <img
                    src={TempImg1}
                    alt="Template"
                    className="w-[677px] xl:w-[599px] object-cover"
                  />
                </div>
                <div className="flex justify-start xl:justify-end items-center pe-[80px] order-1 xl:order-2 pt-[55px] xl:pb-[50px]">
                  <div className="xl:max-w-[431px] space-y-6">
                    <img
                      src={TLogo2}
                      alt="TLogo"
                      className="w-[60px] h-[60px]"
                    />
                    <h2 className="text-[40px] font-medium xl:max-w-[380px]">
                      Hello Animation Starter Theme.
                    </h2>
                    <p className="text-base text-text-secondary !mt-5">
                      Hello Animation Starter Theme is a lightweight, responsive
                      theme designed for seamless integration with perfect
                      dynamic animation features.
                    </p>
                    {
                      WCF_ADDONS_ADMIN.theme_status && WCF_ADDONS_ADMIN.theme_status =='installnow' &&                     
                        <Button
                          variant="secondary"
                          className="rounded-lg text-text"
                          onClick={() => installTheme("hello-animation")}
                        >
                          {themelabel}
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            className="ml-1.5"
                            viewBox="0 0 18 18"
                            fill="none"
                          >
                            <path
                              d="M12.0026 7.05623L5.55882 13.5L4.5 12.4412L10.9438 5.9974H5.26429V4.5H13.5V12.7357H12.0026V7.05623Z"
                              fill="#181B25"
                            />
                          </svg>
                        </Button>
                    }
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

export default WizTemplate;
