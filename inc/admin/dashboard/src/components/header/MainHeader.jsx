import { RiSearchLine } from "react-icons/ri";
import { Button } from "../ui/button";
import MainNav from "./MainNav";
import ShortLogo from "./ShortLogo";
import GlobalSearch from "../shared/GlobalSearch";
import MobileNav from "./MobileNav";
import Notification from "../notification";
import GetProButton from "../shared/GetProButton";

const MainHeader = ({ open, setOpen }) => {
  return (
    <div className="flex justify-between items-center gap-6 py-5 px-8 border-b border-border-secondary">
      <div>
        <ShortLogo />
      </div>
      <div className="hidden xl:block flex-1">
        <MainNav />
      </div>
      <div className="flex gap-2.5 max-w-[400px]">
        <Button variant="secondary" size="icon" onClick={() => setOpen(true)}>
          <RiSearchLine size={20} />
        </Button>
        {/* <Button variant="secondary" size="icon" className="relative">
          <Badge className="absolute top-[9px] right-2" variant="solid" />
          <RiNotificationLine size={20} />
        </Button> */}
        <Notification />
        <div className="block xl:hidden">
          <MobileNav />
        </div>
        <GetProButton />
      </div>
      <GlobalSearch open={open} setOpen={setOpen} />
    </div>
  );
};

export default MainHeader;
