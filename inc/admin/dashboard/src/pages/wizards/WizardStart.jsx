import GettingStartImage from "../../../public/images/wizard/getting-start-bg.png";
import MessagesIcon from "../../../public/images/wizard/messages-icon.png";
import SecurityIcon from "../../../public/images/wizard/security-icon.png";
import PictureIcon from "../../../public/images/wizard/picture-icon.png";
import CameraIcon from "../../../public/images/wizard/camera-icon.png";
import BasicSetting from "../../../public/images/wizard/basic-setting.png";
import AdvanceSetting from "../../../public/images/wizard/advance-setting.png";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { useState } from "react";
import { cn } from "@/lib/utils";

const WizardStart = () => {
  const [selected, setSelected] = useState("basic");

  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div
        className="min-h-screen bg-no-repeat bg-cover "
        style={{ backgroundImage: `url(${GettingStartImage})` }}
      >
        <div className="pt-[100px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
          <div className="bg-white rounded-full border border-border py-[5px] ps-2 pe-2.5 mx-auto max-w-[120px] flex justify-center items-center gap-1.5">
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
          <div className="mt-[48px] flex justify-center items-center gap-5">
            <div className="bg-white rounded-xl w-[142px] pb-4 pt-1.5 flex flex-col justify-center items-center shadow-[0px_4px-10px-0px-rgba(0,0,0,0.04)]">
              <img
                src={MessagesIcon}
                alt="Messages Icon"
                width={54}
                height={54}
                className="w-[54px] h-[54px]"
              />
              <p className="text-text-tertiary">100+ Widgets</p>
            </div>
            <div className="bg-white rounded-xl w-[142px] pb-4 pt-1.5 flex flex-col justify-center items-center shadow-[0px_4px-10px-0px-rgba(0,0,0,0.04)]">
              <img
                src={SecurityIcon}
                alt="Security Icon"
                width={54}
                height={54}
                className="w-[54px] h-[54px]"
              />
              <p className="text-text-tertiary">50+ Templates</p>
            </div>
            <div className="bg-white rounded-xl w-[142px] pb-4 pt-1.5 flex flex-col justify-center items-center shadow-[0px_4px-10px-0px-rgba(0,0,0,0.04)]">
              <img
                src={PictureIcon}
                alt="Picture Icon"
                width={54}
                height={54}
                className="w-[54px] h-[54px]"
              />
              <p className="text-text-tertiary">30+ Extensions</p>
            </div>
            <div className="bg-white rounded-xl w-[142px] pb-4 pt-1.5 flex flex-col justify-center items-center shadow-[0px_4px-10px-0px-rgba(0,0,0,0.04)]">
              <img
                src={CameraIcon}
                alt="Camera Icon"
                width={54}
                height={54}
                className="w-[54px] h-[54px]"
              />
              <p className="text-text-tertiary">5K+ Installations</p>
            </div>
          </div>
        </div>
        <div className="mt-[110px] rounded-3xl w-[892px] mx-auto border-[10px] border-white overflow-hidden">
          <div className="bg-[linear-gradient(180deg,#F0F4F8_0%,#FEF3EC_100%)] p-6">
            <h2 className="text-sm text-text-secondary text-center mt-[7px] mb-6">
              Choose your preferred configuration
            </h2>
            <RadioGroup
              value={selected}
              onValueChange={(value) => setSelected(value)}
              className="grid grid-cols-2 justify-between items-center gap-6"
            >
              <div
                className={cn(
                  "w-full h-full border border-border shadow-[0px_6px_13px_0px_rgba(0,0,0,0.04)] rounded-[10px]",
                  selected === "basic" && "border-brand"
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
                  selected === "advance" && "border-brand"
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
    </div>
  );
};

export default WizardStart;
