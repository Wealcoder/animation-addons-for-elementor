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

const CredentialAlert = () => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <p className="text-sm text-text-tertiary underline font-medium underline-offset-2 cursor-pointer">
          Learn more
        </p>
      </DialogTrigger>
      <DialogContent className="w-[380px] bg-background pr-0 gap-0 !rounded-2xl [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
        <DialogHeader className={"hidden"}>
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div>dialog content</div>
        <DialogFooter>
          <Button type="submit">Save changes</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default CredentialAlert;
