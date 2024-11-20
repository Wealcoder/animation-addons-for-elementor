import { RiPuzzle2Line } from "react-icons/ri";

const IntegrationTopBar = () => {
  return (
    <div className="grid grid-cols-2 gap-11 justify-between items-center">
      <div className="flex items-center gap-3">
        <div className="border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
          <RiPuzzle2Line size={24} color="#FC6848" />
        </div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">Integrations</h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label">
              {
                Object.keys(
                  WCF_ADDONS_ADMIN.addons_config?.integrations?.plugins
                    ?.elements
                ).length
              }{" "}
              Total Integrations
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default IntegrationTopBar;
