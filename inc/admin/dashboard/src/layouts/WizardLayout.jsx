import { ScrollArea } from "@/components/ui/scroll-area";
import WizFooter from "@/components/wizards/WizFooter";
import { WizHeader } from "@/components/wizards/WizHeader";
import WizExtension from "@/pages/wizards/WizExtension";
import WizTemplate from "@/pages/wizards/WizTemplate";
import WizWidget from "@/pages/wizards/WizWidget";
import { useEffect, useState, lazy, Suspense } from "react";

const WizardStart = lazy(() => import("@/pages/wizards/WizardStart"));

const WizardLayout = () => {
  const [tabKey, setTabKey] = useState("");

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setTabKey(tabValue);
    }
  }, [urlParams]);

  const showContent = (tabKey) => {
    switch (tabKey) {
      case "getting-started":
        return <WizardStart />;
      case "widgets":
        return <WizWidget />;
      case "extensions":
        return <WizExtension />;
      case "templates":
        return <WizTemplate />;
      default:
        return <></>;
    }
  };

  const NavigateComponent = (value) => {
    if (value) {
      setTabKey(value);
    }
  };

  return (
    <div className="wcf-anim2024-wrapper">
      <div className="wcf-anim2024-style container overflow-x-hidden bg-background rounded-[10px]">
        <WizHeader NavigateComponent={NavigateComponent} />
        <div className="z-10 relative">
          <ScrollArea className="h-[69vh] w-full">
            <Suspense fallback={<p>Loading...</p>}>
              {showContent(tabKey)}
            </Suspense>
          </ScrollArea>
        </div>
        <WizFooter />
      </div>
    </div>
  );
};

export default WizardLayout;
