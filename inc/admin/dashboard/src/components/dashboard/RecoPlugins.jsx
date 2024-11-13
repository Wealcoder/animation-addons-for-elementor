import { RecommendedPluginsList } from "@/config/data/recoPluginsList";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";
import { RiArrowRightUpLine, RiPuzzle2Line } from "react-icons/ri";
import { Separator } from "../ui/separator";

const RecoPlugins = () => {
  const plugins = RecommendedPluginsList;
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  return (
    <div
      className={cn(
        "border rounded-2xl p-5",
        hashValue === "wcf-recommended-plugins"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : "shadow-common"
      )}
      id="wcf-recommended-plugins"
    >
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center">
          <RiPuzzle2Line size={20} color="#7D52F4" />
          <p className="font-medium">Recommended Themes</p>
        </div>
        <div>
          <a
            href={"https://crowdytheme.com/wordpress-portfolio"}
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            View all <RiArrowRightUpLine size={18} className="ml-1" />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div>
        {plugins?.map((el, i) => (
          <div key={`reco_plugin_list-${i}`}>
            <div className=" group flex items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <div>
                  <img
                    src={el.logo}
                    alt="Plugins logo"
                    style={{ width: "40px", height: "40px" }}
                  />
                </div>
                <div>
                  <a
                    href={el.url}
                    target="_blank"
                    className={cn("text-sm font-medium group-hover:text-brand")}
                  >
                    {el.title}
                  </a>
                  <p className="text-sm text-text-secondary">{el.subTitle}</p>
                </div>
              </div>
              <div className="overflow-hidden relative w-5 h-5">
                <RiArrowRightUpLine
                  size={20}
                  color="#99A0AE"
                  className="absolute top-0 left-0 transition-all group-hover:!text-brand group-hover:translate-x-6 group-hover:-translate-y-6"
                />
                <RiArrowRightUpLine
                  size={20}
                  color="#99A0AE"
                  className="absolute top-0 left-0 transition-all group-hover:!text-brand -translate-x-6 translate-y-6 group-hover:translate-x-0 group-hover:translate-y-0"
                />
              </div>
            </div>
            {i + 1 !== plugins.length ? (
              <Separator className="my-4 bg-border-secondary" />
            ) : (
              ""
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default RecoPlugins;
