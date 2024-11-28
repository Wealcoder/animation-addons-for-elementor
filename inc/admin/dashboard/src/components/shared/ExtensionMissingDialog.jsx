import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button, buttonVariants } from "../ui/button";
import ProDialog from "../../../public/images/pro-dialog.png";
import { cn } from "@/lib/utils";

const ExtensionMissingDialog = ({ open, setOpen }) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="w-[380px] bg-background pr-0 gap-0 !rounded-2xl [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
        <DialogHeader className={"hidden"}>
          <DialogTitle className={"hidden"}></DialogTitle>
          <DialogDescription className={"hidden"}></DialogDescription>
        </DialogHeader>
        <div>
          <img src={ProDialog} className="w-full h-[174px]" alt="pro dialog" />
          <div className="p-6 pt-2">
            <h2 className="text-xl text-center font-medium">
              Please Activate Extension Plugin
            </h2>

            <a
              href={`${window.location.origin}/wp-admin/admin.php?page=wcf_addons_settings&tab=integrations`}
              className={cn(
                buttonVariants({ variant: "pro" }),
                "w-full mt-6 no-underline"
              )}
            >
              Activate Extension
            </a>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default ExtensionMissingDialog;
