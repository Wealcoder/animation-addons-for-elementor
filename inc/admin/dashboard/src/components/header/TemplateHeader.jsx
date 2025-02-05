import { RiArrowLeftLine, RiVipCrown2Line } from "react-icons/ri";
import { Button } from "../ui/button";
import LargeLogo from "./LargeLogo";

const TemplateHeader = ({ NavigateComponent }) => {
  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    NavigateComponent(value);
    window.location.reload();
  };
  return (
    <div className="bg-background px-8 py-5 flex justify-between gap-11 items-center border-b border-border">
      <div className="flex gap-4 items-center">
        <div onClick={() => changeRoute("dashboard")}>
          <RiArrowLeftLine
            size={20}
            className="text-icon-secondary hover:text-[#101828]"
          />
        </div>
        <LargeLogo />
      </div>
      <div className="flex justify-end gap-3 items-center">
        <Button variant="pro">
          <span className="me-2">
            <RiVipCrown2Line size={20} />
          </span>{" "}
          Get Pro Version
        </Button>
      </div>
    </div>
  );
};

export default TemplateHeader;
