import ComparisonTable from "@/components/freePro/ComparisonTable";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { RiArrowRightUpLine, RiVipCrown2Line } from "react-icons/ri";
import { Link } from "react-router-dom";

const FreePro = () => {
  return (
    <div className="min-h-screen py-6">
      <div className="flex justify-between gap-11 items-end">
        <div className="flex items-center gap-3">
          <div className="border border-solid border-border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
            <RiVipCrown2Line size={24} color="#FC6848" />
          </div>
          <div className="flex flex-col gap-1">
            <div className="text-[18px] font-medium flex items-center">
              <h2>Free vs Pro </h2>
            </div>
            <div className="text-sm text-label">
              <p>Compare our free vs pro features</p>
            </div>
          </div>
        </div>
        <div>
          <Link
            to={"#"}
            className={cn(buttonVariants({ variant: "secondary" }))}
          >
            Learn more details{" "}
            <RiArrowRightUpLine size={18} className="ml-1.5" />
          </Link>
        </div>
      </div>
      <div className="mt-8">
        <ComparisonTable />
      </div>
    </div>
  );
};

export default FreePro;
