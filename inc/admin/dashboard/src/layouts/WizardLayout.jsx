import { WizHeader } from "@/components/wizards/WizHeader";
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
      case "get-started":
        return <WizardStart />;
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
        <div>
          <Suspense fallback={<p>Loading...</p>}>
            {showContent(tabKey)}
          </Suspense>
        </div>
      </div>
    </div>
  );
};

export default WizardLayout;
