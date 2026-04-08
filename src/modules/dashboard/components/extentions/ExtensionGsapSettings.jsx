import { __ } from "@wordpress/i18n";
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

const ExtensionGsapSettings = ({ extension, children, contentSx }) => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button
          variant="link"
          className={cn(
            "group disabled:opacity-100",
            Object.keys(extension?.elements)?.length && children
              ? "text-icon"
              : "text-[#CACFD8] hover:text-[#CACFD8]"
          )}
          disabled={!(Object.keys(extension?.elements)?.length && children)}
        >
          <RiSettings2Line
            className={cn(
              "me-1.5",
              Object.keys(extension?.elements)?.length && children
                ? "text-icon group-hover:text-brand"
                : "text-[#CACFD8] group-hover:text-[#CACFD8]"
            )}
            size={20}
          />
          {__("Settings", "animation-addons-for-elementor")}
        </Button>
      </DialogTrigger>
      <DialogContent
        style={{ width: "428px", maxWidth: "428px", ...contentSx }}
        className="rounded-xl bg-background pr-0 [&>.wcf-dialog-close-button>svg]:text-[#99A0AE] [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4"
        closeBtnCls="opacity-100 !top-[1.5rem] ltr:!right-[1.5rem] rtl:!right-auto rtl:!left-[1.5rem]"
        closeBtnIconCls="w-[1.4rem] h-[1.4rem] !text-[#525866]"
      >
        <DialogHeader className={"hidden"}>
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <div>{children}</div>
      </DialogContent>
    </Dialog>
  );
};

export default ExtensionGsapSettings;
