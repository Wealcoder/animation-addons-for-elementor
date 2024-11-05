import { cn } from "@/lib/utils";
import {
  RiArrowRightLine,
  RiArrowRightUpLine,
  RiFileTextLine,
} from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { Separator } from "../ui/separator";
import { DocumentList } from "@/config/data/documentList";

const Documentation = () => {
  const documents = DocumentList;
  // const location = useLocation();

  // const hashValue = location.hash.replace("#", "");
  const hashValue = "";

  return (
    <div
      className={cn(
        "border rounded-2xl p-5 shadow-common",
        hashValue === "wcf-documentation"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : ""
      )}
    >
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center" id="wcf-documentation">
          <RiFileTextLine size={20} color="#4870FF" />
          <p className="font-medium">Documentation</p>
        </div>
        <div>
          <a
            href={"#"}
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            View all <RiArrowRightUpLine size={18} className="ml-1" />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div>
        {documents?.map((el, i) => (
          <div key={`document_list-${i}`}>
            <div className="flex flex-col gap-2">
              <a
                href={"#"}
                className={cn(
                  "text-sm font-medium inline-flex items-center gap-[6px] hover:text-brand"
                )}
              >
                {el.title} <RiArrowRightLine size={16} />
              </a>
              <p className="text-sm text-text-secondary">{el.subTitle}</p>
            </div>
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
