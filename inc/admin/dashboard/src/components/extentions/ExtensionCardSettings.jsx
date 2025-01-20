import { Button } from "../ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";
import { RiSettings2Line } from "react-icons/ri";

const ExtensionCardSettings = ({ children }) => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button
          variant="link"
          className={cn(
            "group disabled:opacity-100 p-0",
            children ? "text-icon" : "text-[#CACFD8] hover:text-[#CACFD8]"
          )}
          disabled={!children}
        >
          <RiSettings2Line
            className={cn(
              children
                ? "text-icon group-hover:text-brand"
                : "text-[#CACFD8] group-hover:text-[#CACFD8]"
            )}
            size={20}
          />
        </Button>
      </DialogTrigger>
      <DialogContent className="w-[428px] max-w-[428px] rounded-xl bg-background pr-0 [&>.wcf-dialog-close-button>svg]:text-[#99A0AE] [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4">
        <DialogHeader className={"hidden"}>
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div>{children}</div>
      </DialogContent>
    </Dialog>
  );
};

export default ExtensionCardSettings;
