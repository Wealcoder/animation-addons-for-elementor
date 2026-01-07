import ExtensionTopBg from "../../../../../public/images/wizard/extension-top-bg.png";
import AnimationAddonLogo from "../../../../../public/images/Logo-2.png"; 
import ShowWizExtensions from "@/components/wizards/ShowWizExtension";
import  WizShaped  from "@/components/wizards/WizShaped";

const WizExtension = () => {
  return (

     <div className="rounded-lg overflow-hidden mx-2.5">
          <div className="rounded-lg relative">
            <div className="flex items-center justify-center min-h-[75vh] bg-no-repeat bg-container pb-6 mt-[30px]">
              <div className="p-8 max-w-[1288px] mx-auto mx-auto text-center flex flex-col gap-3 bg-white rounded-[24px] shadow-[0_14px_59px_0_rgba(217,202,180,0.25)]">
                <div className="bg-white rounded-[24px] relative top-[-60px] py-[5px] ps-2 pe-2.5 mx-auto max-w-[180px] flex justify-center items-center gap-1.5 shadow-[0px -25px 59px 0px #D9CAB41A]">
                  <div className="w-[150px]">
                    <img src={AnimationAddonLogo} alt="Animation Addon Logo" />
                  </div>
                </div>
                <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
                       Activate Extensions You Want to Use
                </h1>
                <p className="text-lg text-text-secondary">
                  Customize your website experience by turning on extensions that
                    serve your goals.
                </p>
        
                <div className="mt-[56px] max-w-[1184px] mx-auto border-[10px] border-white rounded-lg">
                 <ShowWizExtensions />
                </div>
              </div>
            </div>
    
              {/* shapes  */}
              <WizShaped/>
          </div>
        </div>
      );


};

export default WizExtension;
