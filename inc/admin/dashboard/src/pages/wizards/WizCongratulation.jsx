import TemplateTopBg from "../../../public/images/wizard/template-top-bg.png";
import CongratulationBanner from "../../../public/images/wizard/congratulation-banner.png";
import { Button } from "@/components/ui/button";

const WizCongratulation = () => {
  const gotoDashboard = () => {
    setTimeout(() => {
      window.location.href = `${WCF_ADDONS_ADMIN.adminURL}/admin.php?page=wcf_addons_settings&tab=dashboard`;
    }, 100);
  };

  return (
    <div className="rounded-lg overflow-hidden mx-2.5">
      <div className="rounded-lg">
        <div
          className="min-h-[65vh] bg-no-repeat bg-contain pb-6"
          style={{ backgroundImage: `url(${TemplateTopBg})` }}
        >
          <div className="pt-[90px] flex flex-col items-center gap-[42px]">
            <div className="w-[539px] h-[289px]">
              <img src={CongratulationBanner} alt="Congratulation Banner" />
            </div>
            <div className="flex flex-col items-center">
              <h2 className="text-[40px] font-medium">Congratulations!</h2>
              <p className="mt-3 text-lg text-text-secondary">
                Your setup’s complete! You're ready to use our addons.
              </p>
              <div className="mt-8">
                <Button
                  className="w-[249px] h-[46px] gap-1.5"
                  onClick={gotoDashboard}
                >
                  Go to Dashboard{" "}
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                  >
                    <path
                      d="M11.1248 10.3033L7 6.17851L8.17852 5L13.4818 10.3033L8.17852 15.6066L7 14.4281L11.1248 10.3033Z"
                      fill="white"
                    />
                  </svg>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WizCongratulation;
