import { LoadingSpinner } from "@/components/shared/LoadingSpinner";
import { Progress } from "@/components/ui/progress";
import DemoImportingBG from "../../public/images/demo-importing-bg.png";
import { useCallback, useEffect, useState } from "react";
import { debounceFn } from "@/lib/utils";
import { useTNavigation } from "@/hooks/app.hooks";

const DemoImporting = () => {
  const [currenTemplate, setCurrenTemplate] = useState(false);
  const [msg, setMsg] = useState("");
  const [tempstate, setTempState] = useState(null);
  const [progress, setProgress] = useState(0);

  const { setTabKey } = useTNavigation();

  const url = new URL(window.location.href);
  const template = url.searchParams.get("template");
  const templateid = url.searchParams.get("templateid");
  const plugins = url.searchParams.get("plugins");
  const theme = url.searchParams.get("theme");
  const attachment = url.searchParams.get("attachment");

  const changeRoute = (value, meta) => {
    const pageQuery = url.searchParams.get("page");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;
    url.searchParams.set("template", template);
    url.searchParams.set("templateid", templateid);
    url.searchParams.set("tab", value);
    if (meta.plugins) url.searchParams.set("plugins", meta.plugins);
    if (meta.theme) url.searchParams.set("theme", meta.theme);
    url.searchParams.set("attachment", meta.attachment);
    window.history.replaceState({}, "", url);
    setTabKey(value);
  };

  const changeCompleteRoute = (value) => {
    const pageQuery = url.searchParams.get("page");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;
    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    setTabKey(value);
  };

  const [step, setStep] = useState("Varifying");
  useEffect(() => {
    if (!currenTemplate) {
      getTemplate(templateid);
    } else {
      runImport(currenTemplate);
    }
  }, [currenTemplate]);

  const getTemplate = useCallback(
    debounceFn(async (id) => {
      try {
        const url = new URL(
          `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
        );

        if (id) {
          url.searchParams.append("tplid", id);
        }

        await fetch(url.toString())
          .then((response) => response.json())
          .then((data) => {
            if (data?.templates) {
              const result = Object.entries(data.templates).find(
                ([key, value]) => value.id == id
              )?.[1];

              setCurrenTemplate(result);
            }
          });
      } catch (error) {
        console.log(error);
      }
    }),
    []
  );

  const runImport = useCallback(
    debounceFn(async (tpldata) => {
      try {
        delete tpldata.author;
        delete tpldata.categories;
        delete tpldata.date;
        delete tpldata.demo_link;
        delete tpldata.user_favourite;
        delete tpldata.template_preview;
        delete tpldata.downloads;
        delete tpldata.is_pro;
        delete tpldata.excerpt;
        setTempState(tpldata);
        const formData = new URLSearchParams();

        if (tpldata?.next_step && tpldata.next_step == "download-xml-file") {
          formData.append("action", "aaeaddon_upload_manual_import_file");
        } else {
          formData.append("action", "aaeaddon_template_installer");
        }

        formData.append("template_data", JSON.stringify(tpldata));
        formData.append("nonce", WCF_ADDONS_ADMIN.nonce);
        if (plugins) formData.append("user_plugins", plugins);
        if (theme) formData.append("theme_slug", theme);
        formData.append("attachment", attachment);
        const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: formData.toString(),
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get("content-type");

        if (contentType && contentType.includes("application/json")) {
          const data = await response.json();

          if ("undefined" !== typeof data.status && "newAJAX" === data.status) {
            if (data?.state && data.state !== "") {
              setMsg(data.state);
            }

            runImport(tpldata);
          }

          if (data?.progress) {
            setProgress((prevProgress) =>
              Number(data.progress) > Number(prevProgress)
                ? data.progress
                : prevProgress
            );
          }

          if (data?.template) {
            const completed =
              data.template.next_step?.trim().toLowerCase() === "done";
            if (completed === true) {
              changeCompleteRoute("complete-import");
            } else if (data.template.next_step === "fail") {
              changeRoute("fail-import", { plugins, theme, attachment });
            } else {
              runImport(data.template);

              if (data?.template?.next_step) {
                setStep(data.template.next_step);
              }

              if (data?.msg) {
                setMsg(data.msg);
              }
            }
          }
        } else {
          runImport(tpldata);
        }
      } catch (error) {
        if (tempstate) {
          runImport(tempstate);
        }
      }
    }, 300),
    []
  );

  return (
    <div className="bg-background w-[680px] rounded-2xl p-1.5 shadow-auth-card">
      <div className="border border-border-secondary rounded-xl p-8">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">
            {!currenTemplate
              ? "Template finding....."
              : "Creating your website..."}{" "}
          </h3>
          <p className="mt-1.5 text-text-secondary">
            Please wait, your website is being created.
          </p>
        </div>
        <div className="mb-8">
          <img
            src={DemoImportingBG}
            className="w-[616px] h-[258px]"
            alt="demo importing"
          />
        </div>
        <div>
          <p className="text-text-secondary">
            <span className="text-text"></span> {msg}
          </p>
          <div className="mt-4">
            <span>
              <Progress value={progress} />
            </span>
          </div>
          <div className="flex items-center gap-1.5 mt-3">
            <LoadingSpinner className={"text-[#07B22B]"} />{" "}
            <p className="text-sm">
              <span>{progress}%</span> Completed
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DemoImporting;
