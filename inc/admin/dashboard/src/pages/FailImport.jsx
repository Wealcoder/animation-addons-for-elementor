import { Button } from "@/components/ui/button";
import CompleteBG from "../../public/images/complete-bg.png";

const FailImport = () => {
  const url = new URL(window.location.href);
  const template = url.searchParams.get("template");
  const templateid = url.searchParams.get("templateid");
  const plugins = url.searchParams.get("plugins");
  const theme = url.searchParams.get("theme");

  const changeRoute = (value) => {
    const pageQuery = url.searchParams.get("page");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;
    url.searchParams.set("tab", value);
    if (value === "demo-importing") {
      url.searchParams.set("template", template);
      url.searchParams.set("templateid", templateid);
      if (plugins) url.searchParams.set("plugins", plugins);
      if (theme) url.searchParams.set("theme", theme);
    }
    window.history.replaceState({}, "", url);
    window.location.reload();
  };

  return (
    <div className="bg-background w-[680px] rounded-2xl p-1.5 shadow-auth-card">
      <div className="border border-border-secondary rounded-xl p-8 pb-3.5">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">Fail to Import</h3>
          <p className="mt-1.5 text-text-secondary">
            An issue occurred while importing
          </p>
        </div>
        <div className="mb-6">
          <img
            src={CompleteBG}
            className="w-[616px] h-[258px]"
            alt="demo importing"
          />
        </div>
        <div className="flex flex-col gap-1.5">
          <Button
            className={"w-full"}
            onClick={() => changeRoute("demo-importing")}
          >
            Retry
          </Button>
          <Button
            variant="link"
            className={"w-full"}
            onClick={() => changeRoute("dashboard")}
          >
            Go to dashboard
          </Button>
        </div>
      </div>
    </div>
  );
};

export default FailImport;
