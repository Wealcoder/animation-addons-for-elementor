import { cn } from "@/lib/utils";
import { RiArrowRightLine } from "react-icons/ri";

const QuickAccessCard = ({ item, className }) => {
  if (!item) return;
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-3 bg-background box-border",
        className
      )}
    >
      <div className="flex items-center gap-3">
        <div
          className={cn(
            "border rounded-full h-11 w-11 flex justify-center items-center shadow-common text-[20px]"
          )}
        >
          {item.icon}
        </div>

        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[15px] leading-6 font-medium">{item.title}</h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label hover:text-text">
              {item.subTitle}
            </p>
          </div>
        </div>
      </div>
      <div>
        <RiArrowRightLine size={24} className="text-icon-secondary" />
      </div>
    </div>
  );
};

export default QuickAccessCard;
