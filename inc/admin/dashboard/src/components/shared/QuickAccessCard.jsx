import { cn } from "@/lib/utils";
import { RiArrowRightLine } from "react-icons/ri";
import { Badge } from "../ui/badge";

const QuickAccessCard = ({ item, className }) => {
  if (!item) return;
  return (
    <div className={cn("group bg-background box-border", className)}>
      <a
        href={item.url}
        aria-disabled={item.url ? false : true}
        className={cn(item.url ? "" : "pointer-events-none")}
      >
        <div className="flex items-center justify-between gap-3 ">
          <div className="flex items-center gap-3">
            <div
              className={cn(
                "border rounded-full h-11 w-11 flex justify-center items-center shadow-common text-[20px]",
                item.url
                  ? ""
                  : "border-border-secondary [&>svg]:!text-[#99A0AE]"
              )}
            >
              {item.icon}
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center">
                <h2
                  className={cn(
                    "text-base font-medium",
                    item.url ? "" : "text-[#99A0AE]"
                  )}
                >
                  {item.title}
                </h2>
              </div>
              <div className="flex items-center">
                <p
                  className={cn(
                    "text-sm text-label",
                    item.url ? "" : "text-[#CACFD8]"
                  )}
                >
                  {item.subTitle}
                </p>
              </div>
            </div>
          </div>
          {item.url ? (
            <div>
              <RiArrowRightLine
                size={24}
                className="text-icon-secondary group-hover:text-brand"
              />
            </div>
          ) : (
            <div>
              <Badge
                variant="pro"
                className="px-2.5 py-1.5 h-7 bg-[linear-gradient(180deg,#FFA184_0%,#F2754F_100%)] mr-1"
              >
                COMING SOON!
              </Badge>
            </div>
          )}
        </div>
      </a>
    </div>
  );
};

export default QuickAccessCard;
