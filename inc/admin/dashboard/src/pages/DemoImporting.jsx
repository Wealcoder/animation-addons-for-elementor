import { LoadingSpinner } from "@/components/shared/LoadingSpinner";
import { Progress } from "@/components/ui/progress";
import DemoImportingBG from "../../public/images/demo-importing-bg.png";

const DemoImporting = () => {
  return (
    <div className="bg-background w-[680px] rounded-2xl p-1.5 shadow-auth-card">
      <div className="border border-border-secondary rounded-xl p-8">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">Creating your website...</h3>
          <p className="mt-1.5 text-text-secondary">
            Please wait, your website is being created.
          </p>
        </div>
        <div className="mb-8">
          <img
            src={DemoImportingBG}
            className="w-[616px] h-[258px]"
            alt="demo importing"
          />
        </div>
        <div>
          <p className="text-text-secondary">
            <span className="text-text">Step 1 :</span> Installing the theme,
            plugins, extensions e.t.c
          </p>
          <div className="grid grid-cols-4 items-center gap-1 mt-4">
            <span>
              <Progress value={100} />
            </span>
            <span>
              <Progress value={0} />
            </span>
            <span>
              <Progress value={0} />
            </span>
            <span>
              <Progress value={0} />
            </span>
          </div>
          <div className="flex items-center gap-1.5 mt-3">
            <LoadingSpinner className={"text-[#07B22B]"} />{" "}
            <p className="text-sm">
              <span>25%</span> Completed
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DemoImporting;
