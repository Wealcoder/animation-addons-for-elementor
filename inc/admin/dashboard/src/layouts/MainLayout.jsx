import MainHeader from "@/components/header/MainHeader";
import TemplateHeader from "@/components/header/TemplateHeader";
import { useNotification } from "@/hooks/app.hooks";
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

  const showContent = (tabKey) => {
    switch (tabKey) {
      case "dashboard":
        return (
          <div className="px-5 2xl:px-24 py-8">
            <Dashboard />
          </div>
        );
      case "widgets":
        return (
          <div className="px-5 2xl:px-24 py-8">
            <Widgets />
          </div>
        );
      case "extensions":
        return (
          <div className="px-5 2xl:px-24 py-8">
            <Extensions />
          </div>
        );
      case "free-pro":
        return (
          <div className="px-5 2xl:px-24 py-8">
            <FreePro />
          </div>
        );
      case "integrations":
        return (
          <div className="px-5 2xl:px-24 py-8">
            <Integrations />
          </div>
        );
      case "stater-template":
        return <StaterTemplate />;
      default:
        return (
          <div className="px-5 2xl:px-24 py-8">
            <Dashboard />
          </div>
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
      <div className="wcf-anim2024-style container overflow-x-hidden bg-background rounded-[10px]">
        {tabKey === "stater-template" ? (
          <TemplateHeader
            NavigateComponent={NavigateComponent}
          />
        ) : (
          <MainHeader
            open={open}
            setOpen={setOpen}
            NavigateComponent={NavigateComponent}
          />
        )}
        <div>
          <Suspense fallback={<p>Loading...</p>}>
            {showContent(tabKey)}
          </Suspense>
        </div>
      </div>
    </div>
  );
};

export default MainLayout;
