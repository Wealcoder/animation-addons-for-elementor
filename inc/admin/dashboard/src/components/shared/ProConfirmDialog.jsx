import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button, buttonVariants } from "../ui/button";
import { RiKey2Line, RiVipCrown2Line } from "react-icons/ri";
import ProDialog from "../../../public/images/pro-dialog.png";
import { cn } from "@/lib/utils";
import LicenseDialog from "./LicenseDialog";
import { useState } from "react";
import { useActivate } from "@/hooks/app.hooks";
import { toast } from "sonner";

const ProConfirmDialog = ({ open, setOpen }) => {
  const { activated } = useActivate();
  const [openLicense, setOpenLicense] = useState(false);

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
    <>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="w-[380px] bg-background pr-0 gap-0 !rounded-2xl [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
          <DialogHeader className={"hidden"}>
            <DialogTitle className={"hidden"}></DialogTitle>
            <DialogDescription className={"hidden"}></DialogDescription>
          </DialogHeader>
          <div>
            <img
              src={ProDialog}
              className="w-full h-[174px]"
              alt="pro dialog"
            />
            <div className="p-6 pt-2">
              <h2 className="text-xl text-center font-medium">
                Upgrade to premium plan and unlock every features!
              </h2>
              <p className="mt-2.5 text-sm text-text-secondary text-center">
                Upgrade and get access to every feature.
              </p>
              {activated.integrations.plugins.elements[
                "animation-addon-for-elementorpro"
              ].action === "Active" ? (
                <Button
                  variant="pro"
                  onClick={() => activePlugin()}
                  className="w-full mt-6"
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
                  className={cn(
                    buttonVariants({ variant: "pro" }),
                    "w-full mt-6"
                  )}
                >
                  <span className="me-2 flex">
                    <RiVipCrown2Line size={20} />
                  </span>
                  Get Pro Version
                </a>
              ) : (
                <Button
                  variant="pro"
                  className="w-full mt-6"
                  onClick={() => {
                    setOpen(false);
                    setOpenLicense(true);
                  }}
                >
                  <span className="me-1.5 flex">
                    <RiKey2Line size={20} />
                  </span>

                  {activated?.wcf_valid
                    ? "Deactivate License"
                    : "Activate License"}
                </Button>
              )}
            </div>
          </div>
        </DialogContent>
      </Dialog>
      <LicenseDialog open={openLicense} setOpen={setOpenLicense} />
    </>
  );
};

export default ProConfirmDialog;
