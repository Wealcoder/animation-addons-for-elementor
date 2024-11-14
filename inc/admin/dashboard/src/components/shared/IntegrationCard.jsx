import { cn } from "@/lib/utils";
import { Dot } from "lucide-react";
import { Button, buttonVariants } from "../ui/button";
import { RiDownloadLine } from "react-icons/ri";

const IntegrationCard = ({ item, className }) => {
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-3 px-4 py-[15px] bg-background shadow-common-2 box-border",
        className
      )}
    >
      {item ? (
        <>
          <div className="flex flex-col gap-1">
            <div className="flex items-center">
              <h2 className="text-[15px] leading-6 font-medium">
                {item?.label}
              </h2>
              {item?.is_pro ? (
                <>
                  <Dot
                    className="w-3.5 h-3.5 text-icon-secondary"
                    strokeWidth={2}
                  />
                  <Badge variant="pro">PRO</Badge>
                </>
              ) : (
                ""
              )}
            </div>
            <div className="flex items-center">
              <a
                href={item?.doc_url}
                className="text-sm text-label hover:text-text"
              >
                Documentation
              </a>
              <Dot
                className="w-3.5 h-3.5 text-icon-secondary"
                strokeWidth={2}
              />
              <a
                href={item?.demo_url}
                aria-disabled="true"
                className="text-sm text-label hover:text-text pointer-events-none opacity-50"
              >
                Preview
              </a>
            </div>
          </div>
          <div>
            <a
              href={item.download_url}
              target="_blank"
              className={cn(
                buttonVariants({ variant: "secondary" }),
                "h-9 py-2 ps-[10px] pe-3"
              )}
            >
              <RiDownloadLine size={18} className="mr-1.5" />
              Download
            </a>
            {/* <Button className="h-9 py-2 ps-[10px] pe-3">
              <RiDownloadLine size={18} className="mr-1.5" />
              Activated
            </Button> */}
          </div>
        </>
      ) : (
        ""
      )}
    </div>
  );
};

export default IntegrationCard;
