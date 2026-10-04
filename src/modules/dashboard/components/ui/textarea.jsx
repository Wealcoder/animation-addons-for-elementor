import * as React from "react";

import { cn } from "@/lib/utils";

const Textarea = React.forwardRef(({ className, ...props }, ref) => {
  return (
    <textarea
      className={cn(
        "flex w-full rounded-[10px] border border-border bg-transparent px-3 py-2.5 text-sm shadow-sm transition-colors placeholder:text-label/50 focus-visible:outline-none focus-visible:border focus-visible:border-[#99A0AE] disabled:cursor-not-allowed disabled:opacity-50",
        className,
      )}
      ref={ref}
      {...props}
    />
  );
});
Textarea.displayName = "Textarea";

export { Textarea };
