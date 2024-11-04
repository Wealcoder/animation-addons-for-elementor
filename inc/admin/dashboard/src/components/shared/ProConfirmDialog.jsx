import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "../ui/button";
import { RiVipCrown2Line } from "react-icons/ri";

const ProConfirmDialog = ({ open, setOpen }) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="w-[380px] bg-background pr-0 gap-0 !rounded-2xl [&>.dialog-close-button]:right-4 [&>.dialog-close-button]:top-4">
        <DialogHeader>
          <DialogTitle className={"hidden"}></DialogTitle>
          <DialogDescription className={"hidden"}></DialogDescription>
        </DialogHeader>
        <div>
          <img
            src="/images/pro-dialog.png"
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
            <Button variant="pro" className="w-full mt-6">
              <span className="me-2">
                <RiVipCrown2Line size={20} />
              </span>{" "}
              Get Pro Version
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default ProConfirmDialog;
