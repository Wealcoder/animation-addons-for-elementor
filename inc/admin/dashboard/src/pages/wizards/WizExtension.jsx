import ExtensionTopBg from "../../../public/images/wizard/extension-top-bg.png";
import ShowWizExtensions from "@/components/wizards/ShowWizExtension";

const WizExtension = () => {
  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="bg-[linear-gradient(0deg,rgba(245,246,248,0.50)_0%,rgba(245,246,248,0.50)_100%)] rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${ExtensionTopBg})` }}
        >
          <div className="pt-[120px] max-w-[730px] mx-auto text-center flex flex-col gap-3">
            <h1 className="text-[44px] font-medium leading-[1.36] tracking-[-0.44px] p-0">
              Activate Extensions That Your Need
            </h1>
            <p className="text-lg text-text-secondary">
              Customize your site by activating Extensions that enhance your
              needs.
            </p>
          </div>
          <div className="mt-[56px] max-w-[1184px] mx-auto border-[10px] border-white rounded-lg">
            <ShowWizExtensions />
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizExtension;
