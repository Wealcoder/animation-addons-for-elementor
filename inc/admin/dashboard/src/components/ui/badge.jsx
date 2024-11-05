import { cva } from "class-variance-authority";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors",
  {
    variants: {
      variant: {
        default: "bg-background text-text-primary rounded-full border",
        filter:
          "bg-background text-text-primary rounded-lg border px-3 py-1.5 text-sm hover:bg-[#FFF3F0] hover:border-[#FFE5DF] hover:text-[#ED745A] hover:font-medium",
        pro: "border-0 px-[7px] py-[3px] h-[18px] text-[11px] leading-[0.91] mt font-medium bg-[linear-gradient(109deg,#FFC47D_7.79%,#FFA132_92.21%)] text-white rounded-full",
        tPro: "border-0 pe-2.5 h-[26px] text-sm font-medium bg-[linear-gradient(45deg,#FF7A00_0%,#FFD439_100%)] text-white rounded-full",
        version: "bg-white text-text h-[27px] rounded-full",
        solid:
          "bg-[#E7350F] border-2 border-solid border-background rounded-full p-0 w-2.5 h-2.5",
        installed:
          "bg-[#E0FAEC] border-0 rounded-md px-2 py-1 h-6 text-[#1A7544]",
        inProgress:
          "bg-[#EBF1FF] border-0 rounded-md px-2 py-1 h-6 text-[#335CFF]",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

function Badge({ className, variant, ...props }) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

export { Badge, badgeVariants };
