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
          <MainLayout.SecondLayout NavigateComponent={item.NavigateComponent}>
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
        <Suspense fallback={<p>Loading...</p>}>
          {showContent({ tabKey, open, setOpen, NavigateComponent })}
        </Suspense>
      </div>
    </div>
  );
};

MainLayout.FirstLayout = ({ open, setOpen, NavigateComponent, children }) => {
  return (
    <div className="container overflow-x-hidden bg-background rounded-[10px]">
      <MainHeader
        open={open}
        setOpen={setOpen}
        NavigateComponent={NavigateComponent}
      />
      <div className="px-5 2xl:px-24 py-8">{children}</div>
    </div>
  );
};
MainLayout.SecondLayout = ({ NavigateComponent, children }) => {
  useEffect(() => {
    const hideElements = () => {
      const wpAdminBar = document.getElementById("wpadminbar");
      const adminMenuWrap = document.getElementById("adminmenuwrap");
      const adminMenuBack = document.getElementById("adminmenuback");
      const wpfooter = document.getElementById("wpfooter");
      const wpcontent = document.getElementById("wpcontent");
      const wpbodyContent = document.getElementById("wpbody-content");
      const wrap = document.querySelector(".wrap");
      const wpToolbar = document.querySelector(".wp-toolbar");
      const wcfAnim2024 = document.querySelector(".wcf-anim2024");

      if (wpAdminBar) wpAdminBar.style.display = "none";
      if (adminMenuWrap) adminMenuWrap.style.display = "none";
      if (adminMenuBack) adminMenuBack.style.display = "none";
      if (wpfooter) wpfooter.style.display = "none";
      if (wpcontent) wpcontent.style.marginLeft = "0px";
      if (wpcontent) wpcontent.style.paddingLeft = "0px";
      if (wpbodyContent)
        wpbodyContent.style.setProperty("padding-bottom", "0px", "important");
      if (wrap) wrap.style.margin = "0px";
      if (wpToolbar) wpToolbar.style.paddingTop = "0px";
      if (wcfAnim2024) wcfAnim2024.style.overflow = "hidden";
    };

    hideElements();
  }, []);

  return (
    <div className="bg-background">
      <TemplateHeader NavigateComponent={NavigateComponent} />
      <div>{children}</div>
    </div>
  );
};

export default MainLayout;
