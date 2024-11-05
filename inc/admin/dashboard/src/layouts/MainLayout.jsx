import MainHeader from "@/components/header/MainHeader";
import { useEffect, useState, lazy, Suspense } from "react";

const Dashboard = lazy(() => import("@/pages/Dashboard"));
const Extensions = lazy(() => import("@/pages/Extensions"));
const FreePro = lazy(() => import("@/pages/FreePro"));
const Widgets = lazy(() => import("@/pages/Widgets"));

const MainLayout = () => {
  const [open, setOpen] = useState(false);
  const [tabKey, setTabKey] = useState("");
  // const location = useLocation();

  // useEffect(() => {
  //   if (location.hash) {
  //     setTimeout(() => {
  //       const element = document.getElementById(location.hash.substring(1));
  //       if (element) {
  //         element.scrollIntoView({
  //           behavior: "smooth",
  //           block: "center",
  //         });

  //         const observer = new IntersectionObserver(
  //           (entries, observerInstance) => {
  //             entries.forEach((entry) => {
  //               if (entry.isIntersecting) {
  //                 setOpen(false);
  //                 observerInstance.unobserve(entry.target);
  //               }
  //             });
  //           },
  //           {
  //             root: null,
  //             threshold: 0.5,
  //           }
  //         );

  //         observer.observe(element);
  //       }
  //     }, 100);
  //   }
  // }, [location]);

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setTabKey(tabValue);
    }
  }, [urlParams]);

  const showContent = (tabKey) => {
    switch (tabKey) {
      case "dashboard":
        return <Dashboard />;
      case "widgets":
        return <Widgets />;
      case "extensions":
        return <Extensions />;
      case "free-pro":
        return <FreePro />;
      default:
        return <Dashboard />;
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
        <MainHeader
          open={open}
          setOpen={setOpen}
          NavigateComponent={NavigateComponent}
        />
        <div className="px-24 py-8">
          <Suspense fallback={<p>Loading...</p>}>
            {showContent(tabKey)}
          </Suspense>
        </div>
      </div>
    </div>
  );
};

export default MainLayout;
