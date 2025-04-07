import { RiInformation2Fill } from "react-icons/ri";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { freeProComparisonList } from "@/config/data/freeProComparisonList";
import { cn } from "@/lib/utils";

const ComparisonTable = () => {
  const comparisons = freeProComparisonList;

  return (
    <div className="rounded-lg border border-border overflow-hidden">
      <div className="flex border-b border-border">
        <div className="w-[464px] p-6 border-r border-border">
          <h3 className="text-xl font-medium">Feature</h3>
        </div>
        <div className="w-[196px] p-6 border-r border-border">
          <h3 className="text-xl font-medium">Free Site</h3>
        </div>
        <div className="w-[196px] p-6 border-r border-border">
          <h3 className="text-xl font-medium flex gap-2.5 items-center">
            1 Sites
          </h3>
        </div>
        <div className="w-[196px] p-6 border-r border-border">
          <h3 className="text-xl font-medium flex gap-2.5 items-center">
            5 Sites
          </h3>
        </div>
        <div className="w-[196px] p-6">
          <h3 className="text-xl font-medium flex gap-2.5 items-center">
            100 Sites
          </h3>
        </div>
      </div>

      {/* content start */}
      {comparisons?.map((item, i) => (
        <div
          key={`comparison_item-${i}`}
          className={cn(
            "flex border-b last:border-b-0 border-border hover:bg-border-secondary",
            (i + 1) % 2 === 0 ? "bg-[#F9FAFC]" : ""
          )}
        >
          <div className="w-[464px] px-6 py-[18px] border-r border-border flex items-center gap-1.5">
            <h3 className="text-[15px] leading-6 font-medium">{item?.name}</h3>
            {item?.helpText ? (
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger className="bg-transparent">
                    <RiInformation2Fill color="#CACFD8" size={20} />
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>{item?.helpText}</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            ) : (
              ""
            )}
          </div>
          <div className="w-[196px] px-6 py-[18px] border-r border-border">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.free?.icon || ""}
              {item?.free?.text}
            </p>
          </div>
          <div className="w-[196px] px-6 py-[18px] border-r border-border">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.pro1?.icon || ""}
              {item?.pro1?.text}
            </p>
          </div>
          <div className="w-[196px] px-6 py-[18px] border-r border-border">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.pro2?.icon || ""}
              {item?.pro2?.text}
            </p>
          </div>
          <div className="w-[196px] px-6 py-[18px]">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.pro3?.icon || ""}
              {item?.pro3?.text}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
};

export default ComparisonTable;
