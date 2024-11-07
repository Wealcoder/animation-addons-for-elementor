import { RiInformation2Fill, RiVipCrown2Line } from "react-icons/ri";
import { Button } from "../ui/button";
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
    <div className="rounded-lg border border-border">
      <div className="flex border-b border-border">
        <div className="w-[464px] p-6 border-r border-border">
          <h3 className="text-lg font-medium">Features</h3>
          <p className="text-[15px] text-text-secondary leading-6 mt-2">
            Take our generous demo for a spin with up to 12 people in a group.
          </p>
        </div>
        <div className="w-[392px] p-6 border-r border-border">
          <h3 className="text-lg font-medium">Free Version</h3>
          <p className="text-[15px] text-text-secondary leading-6 mt-2">
            Take our generous demo for a spin with up to 12 people in a group.
          </p>
        </div>
        <div className="w-[392px] p-6">
          <h3 className="text-lg font-medium flex gap-2.5 items-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="22"
              height="22"
              viewBox="0 0 20 20"
              fill="none"
            >
              <path
                d="M2.91376 6.72057L3.98581 15.833H16.0217L17.0937 6.72057L13.7522 8.94826L10.0038 3.70046L6.2553 8.94826L2.91376 6.72057ZM2.33766 4.33342L5.83706 6.66635L9.3256 1.78238C9.5931 1.40787 10.1136 1.32113 10.4881 1.58864C10.5629 1.64209 10.6283 1.70755 10.6818 1.78238L14.1704 6.66635L17.6698 4.33342C18.0528 4.07813 18.5701 4.1816 18.8254 4.56455C18.9352 4.72923 18.9828 4.9276 18.9597 5.12416L17.5903 16.7638C17.5409 17.1834 17.1853 17.4997 16.7627 17.4997H3.24476C2.8222 17.4997 2.46651 17.1834 2.41714 16.7638L1.04778 5.12416C0.994005 4.66708 1.32096 4.25295 1.77804 4.19917C1.97461 4.17605 2.17298 4.22364 2.33766 4.33342ZM10.0038 12.4997C9.08326 12.4997 8.3371 11.7535 8.3371 10.833C8.3371 9.91251 9.08326 9.16635 10.0038 9.16635C10.9242 9.16635 11.6704 9.91251 11.6704 10.833C11.6704 11.7535 10.9242 12.4997 10.0038 12.4997Z"
                fill="url(#paint0_linear_2250_1075)"
              />
              <defs>
                <linearGradient
                  id="paint0_linear_2250_1075"
                  x1="1.04171"
                  y1="17.4996"
                  x2="17.0125"
                  y2="-0.317217"
                  gradientUnits="userSpaceOnUse"
                >
                  <stop stopColor="#FF7A00" />
                  <stop offset="1" stopColor="#FFD439" />
                </linearGradient>
              </defs>
            </svg>
            Pro Version
          </h3>
          <p className="text-[15px] text-text-secondary leading-6 mt-2">
            Take our generous demo for a spin with up to 12 people in a group.
          </p>
        </div>
      </div>

      {/* content start */}
      {comparisons?.map((item, i) => (
        <div
          key={`comparison_item-${i}`}
          className={cn(
            "flex border-b border-border hover:bg-border-secondary",
            (i + 1) % 2 === 0 ? "bg-[#F9FAFC]" : ""
          )}
        >
          <div className="w-[464px] px-6 py-[18px] border-r border-border flex items-center gap-1.5">
            <h3 className="text-[15px] leading-6 font-medium">{item?.name}</h3>
            {item?.helpText ? (
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger>
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
          <div className="w-[392px] px-6 py-[18px] border-r border-border">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.free?.icon || ""}
              {item?.free?.text}
            </p>
          </div>
          <div className="w-[392px] px-6 py-[18px]">
            <p className="text-[15px] text-text-secondary leading-6 flex items-center gap-2.5">
              {item?.pro?.icon || ""}
              {item?.pro?.text}
            </p>
          </div>
        </div>
      ))}

      {/* content end  */}
      <div className="flex">
        <div className="w-[464px] px-6 py-4 border-r border-border"></div>
        <div className="w-[392px] px-6 py-4 border-r border-border"></div>
        <div className="w-[392px] px-6 py-4">
          <Button variant="pro" className="w-full">
            <span className="me-2 flex">
              <RiVipCrown2Line size={20} />
            </span>{" "}
            Get Pro Version
          </Button>
        </div>
      </div>
    </div>
  );
};

export default ComparisonTable;
