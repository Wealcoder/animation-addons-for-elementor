import {
  RiNotificationLine,
  RiSearchLine,
  RiVipCrown2Line,
} from "react-icons/ri";
import { Button } from "../ui/button";
import MainNav from "./MainNav";
import ShortLogo from "./ShortLogo";
import { Badge } from "../ui/badge";
import GlobalSearch from "../shared/GlobalSearch";

const MainHeader = ({ open, setOpen, NavigateComponent }) => {
  return (
    <div className="flex justify-between items-center gap-6 py-5 px-8 border-b border-border-secondary">
      <div>
        <ShortLogo />
      </div>
      <div className="flex-1">
        <MainNav NavigateComponent={NavigateComponent} />
      </div>
      <div className="flex gap-2.5 max-w-[400px]">
        <Button variant="secondary" size="icon" onClick={() => setOpen(true)}>
          <RiSearchLine size={20} />
        </Button>
        <Button variant="secondary" size="icon" className="relative">
          <Badge className="absolute top-[9px] right-2" variant="solid" />
          <RiNotificationLine size={20} />
        </Button>
        <Button variant="pro">
          <span className="me-2">
            <RiVipCrown2Line size={20} />
          </span>
          Get Pro Version
        </Button>
      </div>
      <GlobalSearch open={open} setOpen={setOpen} />
    </div>
  );
};

export default MainHeader;
