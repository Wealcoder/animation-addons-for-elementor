import { cn } from "@/lib/utils";

const QuickAccessCard = ({
  isEmpty = false,
  icon = "",
  label,
  lUrl = "#",
  className,
}) => {
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-3 px-4 py-[15px] bg-background rounded-lg shadow-common-2 box-border",
        className
      )}
    >
      {isEmpty ? (
        ""
      ) : (
        <div className="flex items-center gap-3">
          <div
            className={cn(
              "border rounded-full h-11 w-11 flex justify-center items-center shadow-common text-[20px]",
              icon
            )}
          />

          <div className="flex flex-col gap-1">
            <div className="flex items-center">
              <h2 className="text-[15px] leading-6 font-medium">{label}</h2>
            </div>
            <div className="flex items-center">
              <a href={lUrl} className="text-sm text-label hover:text-text">
                Customize
              </a>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default QuickAccessCard;
