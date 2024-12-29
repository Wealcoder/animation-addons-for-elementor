import { cn } from "@/lib/utils";
import { Dot } from "lucide-react";
import { Button, buttonVariants } from "../ui/button";
import { RiCheckLine, RiDownloadLine } from "react-icons/ri";
import { Badge } from "../ui/badge";
import { toast } from "sonner";

const IntegrationCard = ({ item, className }) => {
  const deactivate = async (item) => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_deactive_plugin",
        action_base: item.basename,
        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content?.success) {
          toast.success(return_content?.data, {
            position: "top-right",
          });
        }
      });
  };
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
              <h2 className="text-[15px] leading-6">{item?.label}</h2>
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
                className="text-sm text-label hover:text-text cursor-pointer"
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
                className="text-sm text-[#CACFD8] hover:text-text pointer-events-none "
              >
                Preview
              </a>
            </div>
          </div>
          <div>
            {item?.is_pro ? (
              item.action === "Activated" ? (
                <Button
                  className="h-9 py-2 ps-[10px] pe-3 bg-[#CFFFE6] text-[#108D4B] hover:bg-[#CFFFE6] hover:text-[#108D4B]"
                  onClick={() => deactivate(item)}
                >
                  <RiCheckLine size={18} className="mr-1.5" />
                  Activated
                </Button>
              ) : (
                ""
              )
            ) : item.action === "Activated" ? (
              <Button
                className="h-9 py-2 ps-[10px] pe-3 bg-[#CFFFE6] text-[#108D4B] hover:bg-[#CFFFE6] hover:text-[#108D4B]"
                onClick={() => deactivate(item)}
              >
                <RiCheckLine size={18} className="mr-1.5" />
                Activated
              </Button>
            ) : (
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
            )}
          </div>
        </>
      ) : (
        ""
      )}
    </div>
  );
};

export default IntegrationCard;
