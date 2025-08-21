import { RiArrowLeftLine } from "react-icons/ri";
import LargeLogo from "./LargeLogo";
import GetProButton from "../shared/GetProButton";

const TemplateHeader = ({ activeBtn = true }) => {
  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    window.location.reload();
  };
  return (
    <div className="bg-background px-8 py-5 flex justify-between gap-11 items-center border-b border-border">
      <div className="flex gap-4 items-center">
        {activeBtn ? (
          <div
            onClick={() => changeRoute("dashboard")}
            className="cursor-pointer"
          >
            <RiArrowLeftLine
              size={20}
              className="text-icon-secondary hover:text-[#101828]"
            />
          </div>
        ) : (
          ""
        )}
        <LargeLogo />
      </div>
      {activeBtn ? (
        <div className="flex justify-end gap-3 items-center">
          <GetProButton />
        </div>
      ) : (
        ""
      )}
    </div>
  );
};

export default TemplateHeader;
