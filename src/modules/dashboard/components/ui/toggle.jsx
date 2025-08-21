import * as React from "react";
import * as TogglePrimitive from "@radix-ui/react-toggle";
import { cva } from "class-variance-authority";

import { cn } from "@/lib/utils";

const toggleVariants = cva(
  "inline-flex items-center justify-center rounded-md text-sm font-medium transition ease-in-out duration-0  focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default:
          "bg-transparent data-[state=on]:bg-accent data-[state=on]:text-accent-foreground",
        icon: "bg-transparent",
        outline:
          "bg-background text-text-primary rounded-lg border hover:bg-background-secondary gap-1.5 data-[state=on]:bg-[#FFF3F0] data-[state=on]:text-[#ED745A] data-[state=on]:border-[#FFE5DF] [&[data-state=on]>svg]:text-[#ED745A]",
      },
      size: {
        default: "h-9 px-3",
        sm: "h-8 px-2",
        lg: "h-10 px-3",
        icon: "h-5 px-0",
        outline: "h-8 px-3",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

const Toggle = React.forwardRef(
  ({ className, variant, size, ...props }, ref) => (
    <TogglePrimitive.Root
      ref={ref}
      className={cn(toggleVariants({ variant, size, className }))}
      {...props}
    />
  )
);

Toggle.displayName = TogglePrimitive.Root.displayName;

export { Toggle, toggleVariants };
