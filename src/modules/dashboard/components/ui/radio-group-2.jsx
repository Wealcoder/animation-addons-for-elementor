import * as React from "react";
import * as RadioGroupPrimitive from "@radix-ui/react-radio-group";

import { cn } from "@/lib/utils";
import { Circle } from "lucide-react";

const RadioGroup2 = React.forwardRef(({ className, ...props }, ref) => {
  return (
    <RadioGroupPrimitive.Root
      className={cn("grid gap-2.5", className)}
      {...props}
      ref={ref}
    />
  );
});
RadioGroup2.displayName = RadioGroupPrimitive.Root.displayName;

const RadioGroupItem2 = React.forwardRef(({ className, ...props }, ref) => {
  return (
    <RadioGroupPrimitive.Item
      ref={ref}
      className={cn(
        "px-[2px] py-0 aspect-square h-3 w-3 rounded-full text-[#F6502C] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 border-[1.5px] border-[#202020] bg-white [&[data-state=checked]]:border-[#F6502C] cursor-pointer",
        className
      )}
      {...props}
    >
      <RadioGroupPrimitive.Indicator className="flex items-center justify-center">
        <Circle className="h-2.5 w-2.5 fill-current text-current" />
      </RadioGroupPrimitive.Indicator>
    </RadioGroupPrimitive.Item>
  );
});
RadioGroupItem2.displayName = RadioGroupPrimitive.Item.displayName;

export { RadioGroup2, RadioGroupItem2 };
