import MainHeader from "@/components/header/MainHeader";
import TemplateHeader from "@/components/header/TemplateHeader";
import { useNotification } from "@/hooks/app.hooks";
import { hideElements } from "@/lib/utils";
import { useEffect, useState, lazy, Suspense } from "react";

const Dashboard = lazy(() => import("@/pages/Dashboard"));
const Extensions = lazy(() => import("@/pages/Extensions"));
const FreePro = lazy(() => import("@/pages/FreePro"));
const Widgets = lazy(() => import("@/pages/Widgets"));
const Integrations = lazy(() => import("@/pages/Integrations"));
const StaterTemplate = lazy(() => import("@/pages/StaterTemplate"));

const MainLayout = () => {
  const [open, setOpen] = useState(false);
  const [tabKey, setTabKey] = useState("");
  const { setChangelog, setNotice } = useNotification();

  const fetchChangelog = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_get_changelog_data",

        nonce: WCF_ADDONS_ADMIN.nonce,
        settings: "wcf_save_widgets",
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content?.changelog?.change_logs)
          setChangelog(return_content.changelog.change_logs);
      });
  };
  const fetchNotice = async () => {
    await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "wcf_get_notice_data",

        nonce: WCF_ADDONS_ADMIN.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((return_content) => {
        if (return_content?.notice) setNotice(return_content?.notice);
      });
  };

  useEffect(async () => {
    fetchChangelog();
    fetchNotice();
  }, []);

  const hash = window.location.hash;

  useEffect(() => {
    if (hash) {
      setTimeout(() => {
        const element = document.getElementById(hash.substring(1));
        if (element) {
          element.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });

          const observer = new IntersectionObserver(
            (entries, observerInstance) => {
              entries.forEach((entry) => {
                if (entry.isIntersecting) {
                  setOpen(false);
                  observerInstance.unobserve(entry.target);
                }
              });
            },
            {
              root: null,
              threshold: 0.5,
            }
          );

          observer.observe(element);
        }
      }, 100);
    }
  }, [hash]);

  const urlParams = new URLSearchParams(window.location.search);

  useEffect(() => {
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      setTabKey(tabValue);
    }
  }, [urlParams]);

  const showContent = (item) => {
    switch (item.tabKey) {
      case "dashboard":
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <Dashboard />
          </MainLayout.FirstLayout>
        );
      case "widgets":
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <Widgets />
          </MainLayout.FirstLayout>
        );
      case "extensions":
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <Extensions />
          </MainLayout.FirstLayout>
        );
      case "free-pro":
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <FreePro />
          </MainLayout.FirstLayout>
        );
      case "integrations":
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <Integrations />
          </MainLayout.FirstLayout>
        );
      case "stater-template":
        return (
          <MainLayout.SecondLayout>
            <StaterTemplate />
          </MainLayout.SecondLayout>
        );
      default:
        return (
          <MainLayout.FirstLayout
            open={item.open}
            setOpen={item.setOpen}
            NavigateComponent={item.NavigateComponent}
          >
            <Dashboard />
          </MainLayout.FirstLayout>
        );
    }
  };

  const NavigateComponent = (value) => {
    if (value) {
      setTabKey(value);
    }
  };

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
          {showContent({ tabKey, open, setOpen, NavigateComponent })}
        </Suspense>
      </div>
    </div>
  );
};

MainLayout.FirstLayout = ({ open, setOpen, NavigateComponent, children }) => {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
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
        <div className="container overflow-x-hidden bg-background rounded-[10px]">
          <MainHeader
            open={open}
            setOpen={setOpen}
            NavigateComponent={NavigateComponent}
          />
          <div className="px-5 2xl:px-24 py-8">{children}</div>
        </div>
      )}
    </>
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

export default MainLayout;
