import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "C/components/ui/dialog";
import ProDialog from "../../../../../public/images/pro-dialog.png";

const ProConfirmDialog = ({ open, setOpen }) => {
  return (
    <>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="w-[380px] bg-background pr-0 gap-0 !rounded-2xl overflow-hidden [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
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
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default ProConfirmDialog;
