import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";

const CredentialAlert = () => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <p className="min-w-[77px] text-sm text-text-tertiary underline font-medium underline-offset-2 cursor-pointer">
          Learn more
        </p>
      </DialogTrigger>
      <DialogContent className="w-[546px] max-w-[546px] bg-background pr-0 gap-0 !rounded-2xl [&>.wcf-dialog-close-button]:right-6 [&>.wcf-dialog-close-button]:top-6 [&>.wcf-dialog-close-button>svg]:text-border-tertiary">
        <DialogHeader className={"hidden"}>
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div className="p-6">
          <h3 className="text-2xl font-medium">How we collect your data</h3>
          <p className="mt-3 text-base text-text-secondary">
            We collect certain usage data to enhance your experience with our
            plugin, including animation settings, performance metrics, feature
            preferences, and other non-sensitive data. This data helps us
            improve functionality, optimize performance, and ensure better
            updates. We never collect any personal information and sensitive
            data.
          </p>
          <div className="flex justify-end mt-6">
            <DialogClose asChild>
              <Button variant="pro" className="h-11 px-4">
                Ok, I Understand
              </Button>
            </DialogClose>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default CredentialAlert;
