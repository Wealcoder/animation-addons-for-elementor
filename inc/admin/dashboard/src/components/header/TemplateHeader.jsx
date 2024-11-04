import { RiArrowLeftLine, RiUserLine, RiVipCrown2Line } from "react-icons/ri";
import { Button, buttonVariants } from "../ui/button";
import LargeLogo from "./LargeLogo";
import { Link } from "react-router-dom";
import { cn } from "@/lib/utils";

const TemplateHeader = () => {
  return (
    <div className="bg-background px-8 py-5 flex justify-between gap-11 items-center border-b border-border">
      <div className="flex gap-4 items-center">
        <Link to={"/"}>
          <RiArrowLeftLine
            size={20}
            className="text-icon-secondary hover:text-[#101828]"
          />
        </Link>
        <LargeLogo />
      </div>
      <div className="flex justify-end gap-3 items-center">
        <Button variant="pro">
          <span className="me-2">
            <RiVipCrown2Line size={20} />
          </span>{" "}
          Get Pro Version
        </Button>
        <Link
          to={"/registration"}
          className={cn(
            buttonVariants({ variant: "secondary" }),
            "ps-3 pe-3.5"
          )}
        >
          <span className="me-2">
            <RiUserLine size={18} />
          </span>{" "}
          Login
        </Link>
      </div>
    </div>
  );
};

export default TemplateHeader;
