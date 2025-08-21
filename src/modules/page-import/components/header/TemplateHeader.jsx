import { cn } from "S/lib/utils";
import LargeLogo from "./LargeLogo";
import { buttonVariants } from "../ui/button";

const TemplateHeader = () => {
  return (
    <div className="bg-background px-8 py-5 flex justify-between gap-11 items-center border-b border-border">
      <div className="flex gap-4 items-center">
        <LargeLogo />
      </div>
      <div className="flex justify-end gap-3 items-center">
        <a
          href={WCF_ADDONS_ADMIN.page_url ?? "#"}
          className={cn(buttonVariants(), "h-11")}
        >
          Back to page
        </a>
      </div>
    </div>
  );
};

export default TemplateHeader;
