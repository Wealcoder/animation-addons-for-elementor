import { __ } from "@wordpress/i18n";
import { cn } from "@/lib/utils";
import {
  RiArrowRightLine,
  RiArrowRightUpLine,
  RiFileTextLine,
} from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { Separator } from "../ui/separator";
import { DocumentList } from "@/config/data/documentList";
import { API_ENDPOINTS } from "@/config/api";
import { useRemoteData } from "@/hooks/useRemoteData";

const Documentation = () => {
  const { data: documents } = useRemoteData(
    API_ENDPOINTS.documentation,
    DocumentList,
  );
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  return (
    <div
      className={cn(
        "border rounded-2xl p-5",
        hashValue === "wcf-documentation"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : "shadow-common",
      )}
    >
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center" id="wcf-documentation">
          <RiFileTextLine size={20} color="#4870FF" />
          <p className="font-medium">
            {__("Documentation", "animation-addons-for-elementor")}
          </p>
        </div>
        <div>
          <a
            href={"https://animation-addons.com/docs"}
            target="_blank"
            rel="noreferrer"
            className={cn(
              buttonVariants({ variant: "secondary", size: "sm" }),
              "me-1",
            )}
          >
            {__("View all", "animation-addons-for-elementor")}
            <RiArrowRightUpLine
              size={18}
              className="rtl:rotate-360 rtl:scale-x-[-1]"
            />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div>
        {documents?.map((el, i) => (
          <div key={`document_list-${i}`}>
            <a
              href={el.url}
              target="_blank"
              rel="noreferrer"
              className="group flex items-center justify-between gap-4"
            >
              <div className="flex items-center gap-[18px] flex-1 min-w-0">
                <img
                  src={el.icon}
                  alt=""
                  className="w-12 h-12 rounded-full shrink-0"
                />
                <div className="min-w-0">
                  <p className="text-sm font-medium text-text group-hover:text-brand truncate">
                    {el.title}
                  </p>
                  <p className="text-sm text-text-secondary mt-1 truncate">
                    <span dir="ltr">{el.subTitle}</span>
                  </p>
                </div>
              </div>
              <div
                className={cn(
                  "w-8 h-8 rounded-full border flex items-center justify-center shrink-0 text-icon-secondary",
                  "group-hover:border-brand group-hover:text-brand",
                )}
              >
                <RiArrowRightLine size={16} className="rtl:rotate-180" />
              </div>
            </a>
            {i + 1 !== documents.length ? (
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

export default Documentation;
