import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { RiNotificationLine } from "react-icons/ri";
import { Badge } from "../ui/badge";
import { Separator } from "../ui/separator";
import { ScrollArea } from "../ui/scroll-area";
import { useNotification } from "@/hooks/app.hooks";
import React from "react";

const showContent = (data) => {
  switch (data.type) {
    case "notice":
      const date = new Date(data.date);
      return (
        <div>
          <h3 className="text-lg font-medium">{data.title}</h3>
          <p className="mt-2 text-sm">{data.description}</p>
          <p className="mt-2 text-xs text-brand">{date.toLocaleDateString()}</p>
        </div>
      );
    case "promotion":
      return (
        <div>
          <h3 className="text-lg font-medium">{data.title}</h3>
          <p className="mt-2 text-sm">{data.description}</p>
          <Button variant={"secondary"} className="mt-6">
            Get Pro Version
          </Button>
        </div>
      );
    default:
      return <></>;
  }
};

const Notification = () => {
  const { notice, changelog } = useNotification();

  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="secondary" size="icon" className="relative">
          <Badge className="absolute top-[9px] right-2" variant="solid" />
          <RiNotificationLine size={20} />
        </Button>
      </SheetTrigger>
      <SheetContent className="p-0">
        <SheetHeader className={"hidden"}>
          <SheetTitle></SheetTitle>
          <SheetDescription></SheetDescription>
        </SheetHeader>
        <div className="py-4 ps-6 pe-4 border-b border-[#0D1A331A]">
          <h2 className="text-lg font-medium">Updates</h2>
        </div>
        <ScrollArea className="h-[90vh] px-5 py-6">
          {notice &&
            notice?.length &&
            notice?.map((item, i) => (
              <>
                {showContent(item)}{" "}
                {notice.length - 1 !== i && <Separator className="my-6" />}
              </>
            ))}

          {changelog && changelog.length && (
            <>
              <Separator className="my-6" />
              {changelog?.map((log, i) => (
                <React.Fragment key={`changelog-${log.version}-${i}`}>
                  <div>
                    <Badge variant="secondary">Version {log.version}</Badge>
                    <h3 className="text-lg font-medium mt-4">{log.date}</h3>
                    <p className="mt-1 text-sm">{log.summery}</p>
                    <div className="mt-5 ps-4">
                      {log?.data && log?.data?.length && (
                        <ul className="list-outside space-y-2 [&>li]:text-sm [&>li]:text-text-secondary [&>li]:tracking-wider">
                          {log?.data.map((feature, j) => (
                            <li key={`feature-${i}-${j}`}>
                              <span className="text-text">
                                {feature.type}:{" "}
                              </span>{" "}
                              {feature.title}
                            </li>
                          ))}
                        </ul>
                      )}
                    </div>
                  </div>
                </React.Fragment>
              ))}
            </>
          )}
        </ScrollArea>
      </SheetContent>
    </Sheet>
  );
};

export default Notification;
