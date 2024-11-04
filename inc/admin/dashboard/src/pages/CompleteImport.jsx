import { buttonVariants } from "@/components/ui/button";
import { ConfettiAnimation } from "@/lib/confettiAnimation";
import { cn } from "@/lib/utils";
import { useEffect } from "react";

const CompleteImport = () => {
  useEffect(() => {
    ConfettiAnimation();
  }, []);
  return (
    <div className="bg-background w-[680px] rounded-2xl p-1.5 shadow-auth-card">
      <div className="border border-border-secondary rounded-xl p-8 pb-3.5">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">Congratulations!!! 🎉</h3>
          <p className="mt-1.5 text-text-secondary">
            Your website is now imported and ready to use.
          </p>
        </div>
        <div className="mb-6">
          <img
            src="/images/complete-bg.png"
            className="w-[616px] h-[258px]"
            alt="demo importing"
          />
        </div>
        <div className="flex flex-col gap-1.5">
          <a href={"/"} className={cn(buttonVariants(), "w-full h-11")}>
            Visit your website
          </a>
          <a
            href={"/"}
            className={cn(buttonVariants({ variant: "link" }), "w-full")}
          >
            Go to dashboard
          </a>
        </div>
      </div>
    </div>
  );
};

export default CompleteImport;
