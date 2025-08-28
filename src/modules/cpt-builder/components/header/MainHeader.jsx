import MainNav from "./MainNav";
import ShortLogo from "./ShortLogo";

import MobileNav from "./MobileNav";

const MainHeader = () => {
  return (
    <div className="flex justify-between items-center gap-6 py-5 px-8 border-b border-border-secondary">
      <div>
        <ShortLogo />
      </div>
      <div className="hidden xl:block flex-1">
        <MainNav  />
      </div>
      <div className="flex gap-2.5 max-w-[400px]">
        <div className="block xl:hidden">
          <MobileNav />
        </div>
      </div>
    </div>
  );
};

export default MainHeader;
