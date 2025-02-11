import { useActivate } from "@/hooks/app.hooks";
import { Button, buttonVariants } from "../ui/button";
import { toast } from "sonner";
import { RiKey2Line, RiVipCrown2Line } from "react-icons/ri";
import { cn } from "@/lib/utils";
import { useState } from "react";
import LicenseDialog from "./LicenseDialog";

const GetProButton = ({ btnClassName }) => {
  const { activated } = useActivate();
  const [openLicense, setOpenLicense] = useState(false);
  const role = WCF_ADDONS_ADMIN.user_role;

  const activePlugin = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_active_plugin",
        action_base:
          "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content?.success) {
          toast.success(return_content?.data?.message, {
            position: "top-right",
          });

          window.location.reload();
        }
      });
  };
  return (
    <div>
      {role.includes("administrator") &&
        (activated.integrations.plugins.elements[
          "animation-addon-for-elementorpro"
        ].action === "Active" ? (
          <Button
            variant="pro"
            onClick={() => activePlugin()}
            className={btnClassName}
          >
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
            target="_blank"
            className={cn(buttonVariants({ variant: "pro" }), btnClassName)}
          >
            <span className="me-2 flex">
              <RiVipCrown2Line size={20} />
            </span>
            Get Pro Version
          </a>
        ) : (
          <Button
            variant="pro"
            onClick={() => {
              setOpenLicense(true);
            }}
            className={btnClassName}
          >
            <span className="me-1.5 flex">
              <RiKey2Line size={20} />
            </span>

            {activated?.wcf_valid ? "Deactivate License" : "Activate License"}
          </Button>
        ))}
      <LicenseDialog open={openLicense} setOpen={setOpenLicense} />
    </div>
  );
};

export default GetProButton;
