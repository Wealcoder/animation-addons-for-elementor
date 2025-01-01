import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { RiNotificationLine } from "react-icons/ri";
import { Badge } from "../ui/badge";
import { Separator } from "../ui/separator";

const Notification = () => {
  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="secondary" size="icon" className="relative">
          <Badge className="absolute top-[9px] right-2" variant="solid" />
          <RiNotificationLine size={20} />
        </Button>
      </SheetTrigger>
      <SheetContent className="p-0">
        <div className="py-4 ps-6 pe-4 border-b border-[#0D1A331A]">
          <h2 className="text-lg font-medium">Updates</h2>
        </div>
        <div className="px-5 py-6">
          <div>
            <h3 className="text-lg font-medium">
              Congratulations! You have activated pro licence.
            </h3>
            <p className="mt-2 text-sm">
              Unlocking premium features has never been easier than it is now!
              With just a few simple steps, you can access a whole.
            </p>
          </div>
          <Separator className="my-6" />
          <div>
            <h3 className="text-lg font-medium">
              Upgrade to pro plan to active all feature.
            </h3>
            <p className="mt-2 text-sm">
              Unlocking premium features has never been easier than it is now!
              With just a few simple steps, you can access a whole.
            </p>
            <Button variant={"secondary"} className="mt-6">
              Get Pro Version
            </Button>
          </div>
          <Separator className="my-6" />
          <div>
            <Badge variant="secondary">Version 1.2</Badge>
            <h3 className="text-lg font-medium mt-4">Oct 12, 2024</h3>
            <p className="mt-1 text-sm">
              Ui Components, and other stuff changes
            </p>
            <div className="mt-5">
              <h4 className="text-sm font-medium">New Features :</h4>
              <div className="ps-4">
                <ul className="mt-2 list-outside space-y-2 [&>li]:text-sm [&>li]:text-text-secondary [&>li]:tracking-wider">
                  <li>
                    Added Advanced Timeline Controls for more precise animation
                    sequencing.
                  </li>
                  <li>
                    Introduced Parallax Scrolling Effects: Create depth in your
                    site with smooth parallax animations.
                  </li>
                </ul>
              </div>
            </div>
            <div className="mt-3">
              <h4 className="text-sm font-medium">New Features :</h4>
              <div className="ps-4">
                <ul className="mt-2 list-outside space-y-2 [&>li]:text-sm [&>li]:text-text-secondary [&>li]:tracking-wider">
                  <li>
                    Added Advanced Timeline Controls for more precise animation
                    sequencing.
                  </li>
                  <li>
                    Introduced Parallax Scrolling Effects: Create depth in your
                    site with smooth parallax animations.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
};

export default Notification;
