import {
  RiNotificationLine,
  RiSearchLine,
  RiVipCrown2Line,
} from "react-icons/ri";
import { Button, buttonVariants } from "../ui/button";
import MainNav from "./MainNav";
import ShortLogo from "./ShortLogo";
import { Badge } from "../ui/badge";
import GlobalSearch from "../shared/GlobalSearch";
import LicenseDialog from "../shared/LicenseDialog";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

const MainHeader = ({ open, setOpen, NavigateComponent }) => {
  const activated = WCF_ADDONS_ADMIN.addons_config;

  const activePlugin = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_active_plugin",
        action_base: "wcf-addons-pro/wcf-addons-pro.php",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        console.log(return_content);
        if (return_content?.success) {
          toast.success(return_content?.message, {
            position: "top-right",
          });

          window.location.reload();
        }
      });
  };
  return (
    <div className="flex justify-between items-center gap-6 py-5 px-8 border-b border-border-secondary">
      <div>
        <ShortLogo />
      </div>
      <div className="flex-1">
        <MainNav NavigateComponent={NavigateComponent} />
      </div>
      <div className="flex gap-2.5 max-w-[400px]">
        <Button variant="secondary" size="icon" onClick={() => setOpen(true)}>
          <RiSearchLine size={20} />
        </Button>
        <Button variant="secondary" size="icon" className="relative">
          <Badge className="absolute top-[9px] right-2" variant="solid" />
          <RiNotificationLine size={20} />
        </Button>
        {activated.integrations.plugins.elements[
          "animation-addon-for-elementorpro"
        ].action === "Active" ? (
          <Button variant="pro" onClick={() => activePlugin()}>
            <span className="me-2 flex">
              <RiVipCrown2Line size={20} />
            </span>
            Active Plugin
          </Button>
        ) : activated.integrations.plugins.elements[
            "animation-addon-for-elementorpro"
          ].action === "Download" ? (
          <a
            href="https://animation-addons.com/"
            className={cn(buttonVariants({ variant: "pro" }))}
          >
            <span className="me-2 flex">
              <RiVipCrown2Line size={20} />
            </span>
            Get Pro Version
          </a>
        ) : (
          <LicenseDialog />
        )}

        {/* <Button variant="pro" disabled>
          <span className="me-2 flex">
            <RiVipCrown2Line size={20} />
          </span>
          Coming Soon
        </Button> */}
      </div>
      <GlobalSearch open={open} setOpen={setOpen} />
    </div>
  );
};

export default MainHeader;
