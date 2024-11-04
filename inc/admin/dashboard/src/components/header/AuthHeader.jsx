import { RiArrowLeftLine } from "react-icons/ri";
import LargeLogo from "./LargeLogo";
import { useNavigate } from "react-router-dom";

const AuthHeader = () => {
  const navigate = useNavigate();

  return (
    <div className="bg-background px-8 py-5 border-b border-border">
      <div className="flex gap-4 items-center">
        <div onClick={() => navigate(-1)}>
          <RiArrowLeftLine
            size={20}
            className="text-icon-secondary hover:text-[#101828]"
          />
        </div>
        <LargeLogo />
      </div>
    </div>
  );
};

export default AuthHeader;
