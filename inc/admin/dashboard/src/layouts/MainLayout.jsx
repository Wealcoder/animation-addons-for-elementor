import MainHeader from "@/components/header/MainHeader";
import TemplateHeader from "@/components/header/TemplateHeader";
import { ScrollArea } from "@/components/ui/scroll-area";
import { ShowContent } from "@/config/showFullContent";
import { useNotification, useTNavigation } from "@/hooks/app.hooks";
import { hideElements } from "@/lib/utils";
import { useEffect, useState, Suspense } from "react";

const MainLayout = () => {
  const { tabKey, setTabKey } = useTNavigation();
  const { setNotice } = useNotification();

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
    fetchNotice();
  }, []);

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

MainLayout.FirstLayout = ({ children }) => {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(true);

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
          <MainHeader open={open} setOpen={setOpen} />
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
