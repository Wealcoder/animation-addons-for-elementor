import { RiPuzzle2Line } from "react-icons/ri";
import { Button } from "../ui/button";
import { toast } from "sonner";
import { useLibrary } from "@/hooks/app.hooks";

const IntegrationTopBar = () => {
  const { allLibrary } = useLibrary();

  const saveIntegration = async () => {
    saveLibrary();
  };

  const saveLibrary = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "save_settings_dashboard_library_ajax",
        fields: JSON.stringify(allLibrary),
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        toast.success("Save Successful", {
          position: "top-right",
        });
      });
  };

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
      <div className="flex gap-2.5 items-center justify-end">
        {/* <Button variant="secondary">Reset</Button> */}
        <Button onClick={() => saveIntegration()}>Save Settings</Button>
      </div>
    </div>
  );
};

export default IntegrationTopBar;
