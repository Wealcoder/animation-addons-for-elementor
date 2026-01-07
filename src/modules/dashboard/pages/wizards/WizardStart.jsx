import TemplateTopBg from "../../../../../public/images/wizard/template-top-bg.png";
import AnimationAddonLogo from "../../../../../public/images/Logo-2.png"; 
import BasicSetting from "../../../../../public/images/wizard/basic-setting.png";
import AdvanceSetting from "../../../../../public/images/wizard/advance-setting.png";
import Shape1 from "../../../../../public/images/wizard/shape1.png";
import Shape2 from "../../../../../public/images/wizard/shape2.png";
import Shape3 from "../../../../../public/images/wizard/shape3.png";
import Shape4 from "../../../../../public/images/wizard/shape4.png";
import Shape5 from "../../../../../public/images/wizard/shape5.png";
import Shape6 from "../../../../../public/images/wizard/shape6.png";
import  WizShaped  from "@/components/wizards/WizShaped";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { useSetup } from "@/hooks/app.hooks";

const WizardStart = () => {
  const { setupType, setSetupType } = useSetup();

  return (
     <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="rounded-lg relative">
        <div className="flex items-center justify-center min-h-[75vh] bg-no-repeat bg-container pb-6">
          <div className="p-8 max-w-[1130px] mx-auto text-center flex flex-col gap-3 bg-white ml-[15%] mr-[15%] rounded-[24px] shadow-[0_14px_59px_0_rgba(217,202,180,0.25)]">
            <div className="bg-white rounded-[24px] relative top-[-60px] py-[5px] ps-2 pe-2.5 mx-auto max-w-[180px] flex justify-center items-center gap-1.5 shadow-[0px -25px 59px 0px #D9CAB41A]">
             
              {/* <p className="text-sm font-medium">
                Version {WCF_ADDONS_ADMIN?.version}
              </p> */}
              <div className="w-[150px]">
                <img src={AnimationAddonLogo} alt="Animation Addon Logo" />
              </div>
            </div>
             <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Create Stunning Animations with AAE Animation Addons
            </h1>
        <div className="bg-[linear-gradient(180deg,#F0F4F8_0%,#FEF3EC_100%)] p-6">
              <h2 className="text-base text-text-secondary text-center mt-[7px] mb-6">
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
                            We provide all the essential settings for the basic
                            plan to ensure a ready-to-use experience. This
                            allows users a quick setup and seamless operation.
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
                            Custom Configuration
                          </h2>
                          <p className="mt-2.5 text-text-secondary">
                            Users need to modify and personalize settings as per
                            their unique requirements for the custom
                            configuration. This offers optimal flexibility for
                            users to apply their specific preferences.
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

          {/* shapes  */}
          <WizShaped/>
      </div>
    </div>
  );
};

export default WizardStart;
