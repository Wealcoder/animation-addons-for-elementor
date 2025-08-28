import TemplateHeader from "C/components/header/TemplateHeader";
import { ScrollArea } from "C/components/ui/scroll-area";
import { ShowContent } from "C/config/showFullContent";
import { useTNavigation } from "C/hooks/app.hooks";
import { hideElements } from "C/lib/utils";
import { useEffect, useState, Suspense } from "react";

const MainLayout = () => {
  const { tabKey, setTabKey } = useTNavigation();

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setTabKey(tabValue);
    }
  }, []);

  return (
    <div className="wcf-anim2024-wrapper">
      <div className="wcf-anim2024-style">
        <Suspense
          fallback={
            <div className="flex justify-center items-center h-screen">
              <p className="text-lg font-semibold">Loading...</p>
            </div>
          }
        >
          {ShowContent({ tabKey })}
        </Suspense>
      </div>
    </div>
  );
};

MainLayout.SecondLayout = ({ children }) => {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    hideElements();
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);

    return () => clearTimeout(timer);
  }, []);

  return (
    <>
      {loading ? (
        <div className="flex justify-center items-center h-screen">
          <p className="text-lg font-semibold">Loading...</p>
        </div>
      ) : (
        <div className="bg-background">
          <TemplateHeader />
          <div>{children}</div>
        </div>
      )}
    </>
  );
};

MainLayout.ThirdLayout = ({ children }) => {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    hideElements();
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);

    return () => clearTimeout(timer);
  }, []);

  return (
    <>
      {loading ? (
        <div className="flex justify-center items-center h-screen">
          <p className="text-lg font-semibold">Loading...</p>
        </div>
      ) : (
        <div className="bg-background-secondary">
          <TemplateHeader activeBtn={false} />
          <ScrollArea className="h-[calc(100vh-85px)]">
            <div className="flex justify-center items-center min-h-[calc(100vh-85px)] py-5">
              {children}
            </div>
          </ScrollArea>
        </div>
      )}
    </>
  );
};

export default MainLayout;
