import TemplateTopBg from "../../../public/images/wizard/template-top-bg.png";
import BasicSetting from "../../../public/images/wizard/basic-setting.png";
import AdvanceSetting from "../../../public/images/wizard/advance-setting.png";
import Shape1 from "../../../public/images/wizard/shape1.png";
import Shape2 from "../../../public/images/wizard/shape2.png";
import Shape3 from "../../../public/images/wizard/shape3.png";
import Shape4 from "../../../public/images/wizard/shape4.png";
import Shape5 from "../../../public/images/wizard/shape5.png";
import Shape6 from "../../../public/images/wizard/shape6.png";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { useSetup } from "@/hooks/app.hooks";

const WizardStart = () => {
  const { setupType, setSetupType } = useSetup();

  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg relative">
        <div
          className="min-h-[71vh] bg-no-repeat bg-container pb-6 "
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[80px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
            <div className="bg-white rounded-full py-[5px] ps-2 pe-2.5 mx-auto max-w-[120px] flex justify-center items-center gap-1.5 shadow-[0px_0px_0px_1px_rgba(44,64,94,0.06),0px_1px_1px_0px_rgba(44,64,94,0.04),0px_2px_4px_0px_rgba(44,64,94,0.08)]">
              <span className="flex justify-center items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 16 16"
                  fill="none"
                >
                  <g clip-path="url(#clip0_2780_1024)">
                    <path
                      d="M7.07627 11.8641L7.66133 10.5239C8.18207 9.33139 9.11926 8.38206 10.2883 7.86313L11.8988 7.14826C12.4108 6.92099 12.4108 6.17611 11.8988 5.94884L10.3386 5.25629C9.13946 4.72401 8.18546 3.73953 7.67367 2.50629L7.081 1.07815C6.86106 0.548169 6.12879 0.548171 5.90887 1.07815L5.31618 2.50627C4.80438 3.73953 3.85035 4.72401 2.65123 5.25629L1.09105 5.94884C0.579024 6.17611 0.579024 6.92099 1.09105 7.14826L2.70153 7.86313C3.87059 8.38206 4.80781 9.33139 5.3285 10.5239L5.9136 11.8641C6.13851 12.3791 6.85133 12.3791 7.07627 11.8641ZM12.9343 15.1269L13.0988 14.7498C13.3921 14.0774 13.9205 13.542 14.5797 13.2491L15.0866 13.0239C15.3608 12.9021 15.3608 12.5036 15.0866 12.3818L14.6081 12.1691C13.9319 11.8687 13.3941 11.3135 13.1057 10.6183L12.9368 10.2107C12.819 9.92673 12.4263 9.92673 12.3085 10.2107L12.1396 10.6183C11.8513 11.3135 11.3135 11.8687 10.6373 12.1691L10.1587 12.3818C9.8846 12.5036 9.8846 12.9021 10.1587 13.0239L10.6657 13.2491C11.3249 13.542 11.8532 14.0774 12.1465 14.7498L12.3111 15.1269C12.4315 15.403 12.8138 15.403 12.9343 15.1269Z"
                      fill="#FC6848"
                    />
                  </g>
                  <defs>
                    <clipPath id="clip0_2780_1024">
                      <rect width="16" height="16" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
              </span>
              <p className="text-sm font-medium">Version 1.1</p>
            </div>
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              GSAP Animations at Your Fingertips with WCF Animation Addons
            </h1>
            <p className="text-lg text-text-secondary">
              Bring Your Website to Life with Powerful Animations and Effects.
            </p>
            <div className="mt-[32px] flex justify-center items-center gap-5">
              <div className="bg-white rounded-xl w-[142px] pb-4 pt-[18px] flex flex-col gap-3 justify-center items-center shadow-[0px_4px_10px_0px_rgba(0,0,0,0.04)]">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 30 30"
                  fill="none"
                >
                  <path
                    opacity="0.8"
                    d="M22.8 17.6H20.2V12.4H22.8C25.6717 12.4 28 10.0717 28 7.2C28 4.3283 25.6717 2 22.8 2C19.928 2 17.6 4.3283 17.6 7.2V9.8H12.4V7.2C12.4 4.3283 10.0717 2 7.2 2C4.3283 2 2 4.3283 2 7.2C2 10.0717 4.3283 12.4 7.2 12.4H9.8V17.6H7.2C4.3283 17.6 2 19.9283 2 22.8C2 25.6717 4.3283 28 7.2 28C10.072 28 12.4 25.6717 12.4 22.8V20.2H17.6V22.7935C17.6 22.7961 17.5997 22.798 17.5997 22.8C17.5997 25.6717 19.928 28 22.7997 28C25.6714 28 27.9997 25.6717 27.9997 22.8C27.9997 19.9283 25.6717 17.6006 22.8 17.6ZM22.8 4.6C24.2358 4.6 25.4 5.76415 25.4 7.2C25.4 8.63585 24.2358 9.8 22.8 9.8H20.2V7.2C20.2 5.76415 21.3641 4.6 22.8 4.6ZM4.6 7.2C4.6 5.76415 5.76415 4.6 7.2 4.6C8.63585 4.6 9.8 5.76415 9.8 7.2V9.8H7.2C5.76415 9.8 4.6 8.63552 4.6 7.2ZM7.2 25.4C5.76415 25.4 4.6 24.2358 4.6 22.8C4.6 21.3641 5.76415 20.2 7.2 20.2H9.8V22.8C9.8 24.2358 8.63585 25.4 7.2 25.4ZM12.4 17.6V12.4H17.6V17.6H12.4ZM22.8 25.4C21.3641 25.4 20.2 24.2358 20.2 22.8V20.2H22.7997C24.2355 20.2 25.3997 21.3641 25.3997 22.8C25.3997 24.2358 24.2358 25.4 22.8 25.4Z"
                    fill="url(#paint0_linear_4492_4529)"
                  />
                  <defs>
                    <linearGradient
                      id="paint0_linear_4492_4529"
                      x1="9.5"
                      y1="7.6875"
                      x2="29.4496"
                      y2="14.548"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop stop-color="#529EE3" />
                      <stop offset="1" stop-color="#4088F4" />
                    </linearGradient>
                  </defs>
                </svg>
                <p className="text-text-tertiary">100+ Widgets</p>
              </div>
              <div className="bg-white rounded-xl w-[142px] pb-4 pt-[18px] flex flex-col gap-3 justify-center items-center shadow-[0px_4px_10px_0px_rgba(0,0,0,0.04)]">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 30 30"
                  fill="none"
                >
                  <path
                    d="M6.25 10V25H23.75V10H6.25ZM6.25 7.5H23.75V5H6.25V7.5ZM25 27.5H5C4.30965 27.5 3.75 26.9404 3.75 26.25V3.75C3.75 3.05965 4.30965 2.5 5 2.5H25C25.6904 2.5 26.25 3.05965 26.25 3.75V26.25C26.25 26.9404 25.6904 27.5 25 27.5ZM8.75 12.5H13.75V17.5H8.75V12.5ZM8.75 20H21.25V22.5H8.75V20ZM16.25 13.75H21.25V16.25H16.25V13.75Z"
                    fill="url(#paint0_linear_4495_4537)"
                  />
                  <defs>
                    <linearGradient
                      id="paint0_linear_4495_4537"
                      x1="8.75407"
                      y1="11.3835"
                      x2="32.3295"
                      y2="30.0138"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop stop-color="#75EDA1" />
                      <stop offset="1" stop-color="#58B77A" />
                    </linearGradient>
                  </defs>
                </svg>
                <p className="text-text-tertiary">50+ Templates</p>
              </div>
              <div className="bg-white rounded-xl w-[142px] pb-4 pt-[18px] flex flex-col gap-3 justify-center items-center shadow-[0px_4px_10px_0px_rgba(0,0,0,0.04)]">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 30 30"
                  fill="none"
                >
                  <g filter="url(#filter0_b_4495_4546)">
                    <path
                      d="M4.5 9.47368C4.5 12.2206 6.7268 14.4474 9.47368 14.4474C12.2206 14.4474 14.4474 12.2206 14.4474 9.47368C14.4474 6.7268 12.2206 4.5 9.47368 4.5C6.7268 4.5 4.5 6.7268 4.5 9.47368ZM4.5 20.5263C4.5 23.2732 6.7268 25.5 9.47368 25.5C12.2206 25.5 14.4474 23.2732 14.4474 20.5263C14.4474 17.7794 12.2206 15.5526 9.47368 15.5526C6.7268 15.5526 4.5 17.7794 4.5 20.5263ZM15.5526 20.5263C15.5526 23.2732 17.7794 25.5 20.5263 25.5C23.2732 25.5 25.5 23.2732 25.5 20.5263C25.5 17.7794 23.2732 15.5526 20.5263 15.5526C17.7794 15.5526 15.5526 17.7794 15.5526 20.5263ZM12.2368 9.47368C12.2368 10.9997 10.9997 12.2368 9.47368 12.2368C7.94764 12.2368 6.71053 10.9997 6.71053 9.47368C6.71053 7.94764 7.94764 6.71053 9.47368 6.71053C10.9997 6.71053 12.2368 7.94764 12.2368 9.47368ZM12.2368 20.5263C12.2368 22.0524 10.9997 23.2895 9.47368 23.2895C7.94764 23.2895 6.71053 22.0524 6.71053 20.5263C6.71053 19.0003 7.94764 17.7632 9.47368 17.7632C10.9997 17.7632 12.2368 19.0003 12.2368 20.5263ZM23.2895 20.5263C23.2895 22.0524 22.0524 23.2895 20.5263 23.2895C19.0003 23.2895 17.7632 22.0524 17.7632 20.5263C17.7632 19.0003 19.0003 17.7632 20.5263 17.7632C22.0524 17.7632 23.2895 19.0003 23.2895 20.5263ZM19.4211 13.8947V10.5789H16.1053V8.36842H19.4211V5.05263H21.6316V8.36842H24.9474V10.5789H21.6316V13.8947H19.4211Z"
                      fill="url(#paint0_linear_4495_4546)"
                    />
                  </g>
                  <defs>
                    <filter
                      id="filter0_b_4495_4546"
                      x="-0.30109"
                      y="-0.30109"
                      width="30.6022"
                      height="30.6022"
                      filterUnits="userSpaceOnUse"
                      color-interpolation-filters="sRGB"
                    >
                      <feFlood flood-opacity="0" result="BackgroundImageFix" />
                      <feGaussianBlur
                        in="BackgroundImageFix"
                        stdDeviation="2.40055"
                      />
                      <feComposite
                        in2="SourceAlpha"
                        operator="in"
                        result="effect1_backgroundBlur_4495_4546"
                      />
                      <feBlend
                        mode="normal"
                        in="SourceGraphic"
                        in2="effect1_backgroundBlur_4495_4546"
                        result="shape"
                      />
                    </filter>
                    <linearGradient
                      id="paint0_linear_4495_4546"
                      x1="10.5577"
                      y1="9.09375"
                      x2="26.6708"
                      y2="14.6349"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop stop-color="#917FFB" />
                      <stop offset="1" stop-color="#7765E9" />
                    </linearGradient>
                  </defs>
                </svg>
                <p className="text-text-tertiary">30+ Extensions</p>
              </div>
              <div className="bg-white rounded-xl w-[142px] pb-4 pt-[18px] flex flex-col gap-3 justify-center items-center shadow-[0px_4px_10px_0px_rgba(0,0,0,0.04)]">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 30 30"
                  fill="none"
                >
                  <path
                    d="M16.25 12.5H22.5L15 20L7.5 12.5H13.75V3.75H16.25V12.5ZM5 23.75H25V15H27.5V25C27.5 25.6904 26.9404 26.25 26.25 26.25H3.75C3.05965 26.25 2.5 25.6904 2.5 25V15H5V23.75Z"
                    fill="url(#paint0_linear_4495_4552)"
                  />
                  <defs>
                    <linearGradient
                      id="paint0_linear_4495_4552"
                      x1="9.71154"
                      y1="8.67187"
                      x2="28.4295"
                      y2="15.824"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop stop-color="#FBA47F" />
                      <stop offset="1" stop-color="#EF7F18" />
                    </linearGradient>
                  </defs>
                </svg>
                <p className="text-text-tertiary">5K+ Installations</p>
              </div>
            </div>
          </div>
          <div className="mt-[80px] rounded-3xl max-w-[892px] mx-auto border-[10px] border-white overflow-hidden">
            <div className="bg-[linear-gradient(180deg,#F0F4F8_0%,#FEF3EC_100%)] p-6">
              <h2 className="text-sm text-text-secondary text-center mt-[7px] mb-6">
                Choose your preferred configuration
              </h2>
              <RadioGroup
                value={setupType}
                onValueChange={(value) => setSetupType(value)}
                className="grid grid-cols-2 justify-between items-center gap-6"
              >
                <div
                  className={cn(
                    "w-full h-full border border-border shadow-[0px_6px_13px_0px_rgba(0,0,0,0.04)] rounded-[10px]",
                    setupType === "basic" && "border-brand"
                  )}
                >
                  <div
                    className={cn(
                      "h-full p-[14px] border-[6px] border-white rounded-[10px] bg-[linear-gradient(180deg,#FDF7F4_0%,#FFF_100%)] relative"
                    )}
                  >
                    <Label
                      className="cursor-pointer w-full"
                      htmlFor="wcf-basic-setting"
                    >
                      <div>
                        <img
                          src={BasicSetting}
                          alt="Basic Setting"
                          width={36}
                          height={36}
                          className="w-[36px] h-[36px] shadow-[0px_0px_0px_1px_rgba(44,64,94,0.06),0px_1px_1px_0px_rgba(44,64,94,0.04),0px_2px_4px_0px_rgba(44,64,94,0.08)] rounded-lg"
                        />
                        <div className="mt-4 w-[95%]">
                          <h2 className="text-base font-medium">
                            Basic Configuration{" "}
                            <span className="text-label">(Recommended)</span>
                          </h2>
                          <p className="mt-2.5 text-text-secondary">
                            We'll take care of the entire the configuration
                            process for you ensuring that everything.
                          </p>
                        </div>
                      </div>
                    </Label>
                    <div className="absolute top-[10px] right-[10px]">
                      <RadioGroupItem
                        value="basic"
                        id="wcf-basic-setting"
                        className="w-[18px] h-[18px] border-[1.8px] shadow-[0px_1.2px_2.4px_0px_rgba(10,13,20,0.03)]"
                      />
                    </div>
                  </div>
                </div>
                <div
                  className={cn(
                    "w-full h-full border border-border shadow-[0px_6px_13px_0px_rgba(0,0,0,0.04)] rounded-[10px]",
                    setupType === "advance" && "border-brand"
                  )}
                >
                  <div
                    className={cn(
                      "h-full p-[14px] border-[6px] border-white rounded-[10px] w-full bg-[linear-gradient(180deg,#F5F7FD_0%,#FFF_100%)] relative"
                    )}
                  >
                    <Label
                      className="cursor-pointer w-full"
                      htmlFor="wcf-advance-setting"
                    >
                      <div>
                        <img
                          src={AdvanceSetting}
                          alt="Advance Setting"
                          width={36}
                          height={36}
                          className="w-[36px] h-[36px] shadow-[0px_0px_0px_1px_rgba(44,64,94,0.06),0px_1px_1px_0px_rgba(44,64,94,0.04),0px_2px_4px_0px_rgba(44,64,94,0.08)] rounded-lg"
                        />
                        <div className="mt-4 w-[95%]">
                          <h2 className="text-base font-medium">
                            Advanced Configuration
                          </h2>
                          <p className="mt-2.5 text-text-secondary">
                            We'll take care of the entire the configuration
                            process for you ensuring that everything.
                          </p>
                        </div>
                      </div>
                    </Label>
                    <div className="absolute top-[10px] right-[10px]">
                      <RadioGroupItem
                        value="advance"
                        id="wcf-advance-setting"
                        className="w-[18px] h-[18px] border-[1.8px] shadow-[0px_1.2px_2.4px_0px_rgba(10,13,20,0.03)]"
                      />
                    </div>
                  </div>
                </div>
              </RadioGroup>
            </div>
          </div>
        </div>

        <div className="hidden xl:block absolute top-[114px] left-[69px] w-[54px] h-[54px]">
          <img
            src={Shape1}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[14.4px] shadow-[3.36px_6.72px_16.8px_0px_rgba(17,22,49,0.04),0px_44.52px_26.88px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[310px] left-[137px] w-[56px] h-[56px]">
          <img
            src={Shape2}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[14px] shadow-[3.349px_6.698px_16.745px_0px_rgba(17,22,49,0.04),0px_44.374px_26.792px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[432px] left-[30px] w-[52px] h-[52px]">
          <img
            src={Shape3}
            alt="Shape"
            className="w-full h-full -rotate-[15deg] rounded-[13.87px]"
            style={{
              filter:
                "drop-shadow(0px 36.747px 22.187px rgba(0, 0, 0, 0.02)) drop-shadow(2.773px 5.547px 13.867px rgba(17, 22, 49, 0.04))",
            }}
          />
        </div>
        <div className="hidden xl:block absolute top-[106px] right-[66px] w-[60px] h-[60px]">
          <img
            src={Shape4}
            alt="Shape"
            className="w-full h-full rotate-[15deg] rounded-[14.04px] shadow-[3.2px_6.4px_16px_0px_rgba(17,22,49,0.04),0px_42.4px_25.6px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[313px] right-[149px] w-[52px] h-[52px]">
          <img
            src={Shape5}
            alt="Shape"
            className="w-full h-full rotate-[15deg] rounded-[14.16px] shadow-[2.831px_5.662px_14.155px_0px_rgba(17,22,49,0.04),0px_37.512px_22.649px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
        <div className="hidden xl:block absolute top-[432px] right-[30px] w-[50px] h-[50px]">
          <img
            src={Shape6}
            alt="Shape"
            className="w-full h-full rotate-[18deg] rounded-[13.34px] shadow-[2.831px_5.662px_14.155px_0px_rgba(17,22,49,0.04),0px_37.512px_22.649px_0px_rgba(0,0,0,0.02)]"
          />
        </div>
      </div>
    </div>
  );
};

export default WizardStart;
