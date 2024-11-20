import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { RiKey2Line } from "react-icons/ri";

const LicenseDialog = () => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="pro">
          <span className="me-1.5 flex">
            <RiKey2Line size={20} />
          </span>
          Activate Licence
        </Button>
      </DialogTrigger>
      <DialogContent className="w-[546px] max-w-[546px] rounded-xl bg-background pr-0 [&>.wcf-dialog-close-button>svg]:text-[#99A0AE] [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
        <DialogHeader className="hidden">
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div>
          <p>content</p>
        </div>
        <DialogFooter>
          <Button type="submit">Save changes</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default LicenseDialog;
