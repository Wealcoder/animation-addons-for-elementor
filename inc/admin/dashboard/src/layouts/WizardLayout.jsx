import { ScrollArea } from "@/components/ui/scroll-area";
import WizFooter from "@/components/wizards/WizFooter";
import { WizHeader } from "@/components/wizards/WizHeader";
import { useEffect, useState, lazy, Suspense } from "react";

const WizardStart = lazy(() => import("@/pages/wizards/WizardStart"));
const WizWidget = lazy(() => import("@/pages/wizards/WizWidget"));
const WizExtension = lazy(() => import("@/pages/wizards/WizExtension"));
const WizTemplate = lazy(() => import("@/pages/wizards/WizTemplate"));
const WizPro = lazy(() => import("@/pages/wizards/WizPro"));

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
      case "go-pro":
        return <WizPro />;
      default:
        return <WizardStart />;
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
          <ScrollArea className="h-[75vh] w-full">
            <Suspense fallback={<p>Loading...</p>}>
              {showContent(tabKey)}
            </Suspense>
          </ScrollArea>
        </div>
        <WizFooter NavigateComponent={NavigateComponent} />
      </div>
    </div>
  );
};

export default WizardLayout;
