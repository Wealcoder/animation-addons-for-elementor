import { DashboardSearchContent } from "@/config/dashboardSearchContent";
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from "../ui/command";
import { useEffect, useState } from "react";
import { AllWidgetList } from "@/config/data/allWidgetList";
import { Avatar, AvatarFallback, AvatarImage } from "../ui/avatar";
import { RiLandscapeFill } from "react-icons/ri";
import { AllGSAPExtensionList } from "@/config/data/allExtensionList";

const GlobalSearchOld = ({ open, setOpen }) => {
  const dashboardContent = DashboardSearchContent;
  const widgetContent = AllWidgetList;

  const [gsapExtensionContent, setGsapExtensionContent] = useState([]);
  // const router = useNavigate();
  const router = "";

  const [inputValue, setInputValue] = useState("");

  useEffect(() => {
    const gsapExtension = AllGSAPExtensionList;
    const result = [];
    gsapExtension?.forEach((el) =>
      el.extensions.forEach((item) => {
        result.push(item);
      })
    );
    setGsapExtensionContent(result);
  }, []);

  return (
    <CommandDialog
      open={open}
      onOpenChange={setOpen}
      hideClose={true}
      className={"bg-transparent"}
    >
      <div className="bg-background rounded-[10px] border-2 border-border shadow-common-2">
        <CommandInput
          placeholder="Search Anything"
          value={inputValue}
          onValueChange={setInputValue}
        />
      </div>
      <div className="bg-background mt-2.5 rounded-[10px] border-2 border-border shadow-common-2">
        <CommandList>
          {inputValue ? (
            <>
              <CommandEmpty>No results found.</CommandEmpty>
              <CommandGroup heading="Dashboard">
                {dashboardContent?.map((item) => (
                  <CommandItem
                    key={`dashboard_item-${item.slug}`}
                    className="flex items-center gap-2 cursor-pointer"
                    onSelect={() => router(`/#${item.slug}`)}
                  >
                    {item.icon} {item.name}
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandSeparator className="my-3" />
              <CommandGroup heading="Widget">
                {widgetContent?.map((item) => (
                  <CommandItem
                    key={`widget_item-${item.slug}`}
                    className="flex items-center gap-2 cursor-pointer"
                    onSelect={() => router(`/widgets?tab=all/#${item.slug}`)}
                  >
                    <div>
                      <Avatar className="rounded-full h-5 w-5 flex justify-center items-center">
                        <AvatarImage
                          className="w-5 h-5"
                          src={item?.logo}
                          alt="Widget Logo"
                        />
                        <AvatarFallback>
                          <RiLandscapeFill size={20} color="#CACFD8" />
                        </AvatarFallback>
                      </Avatar>
                    </div>{" "}
                    {item.title}
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandSeparator className="my-3" />
              <CommandGroup heading="GSAP Extension">
                {gsapExtensionContent?.map((item) => (
                  <CommandItem
                    key={`widget_item-${item.slug}`}
                    className="flex items-center gap-2 cursor-pointer"
                    onSelect={() => router(`/extensions?tab=all/#${item.slug}`)}
                  >
                    <div>
                      <Avatar className="rounded-full h-5 w-5 flex justify-center items-center">
                        <AvatarImage
                          className="w-5 h-5"
                          src={item?.logo}
                          alt="Widget Logo"
                        />
                        <AvatarFallback>
                          <RiLandscapeFill size={20} color="#CACFD8" />
                        </AvatarFallback>
                      </Avatar>
                    </div>{" "}
                    {item.title}
                  </CommandItem>
                ))}
              </CommandGroup>
            </>
          ) : (
            <>
              <CommandGroup heading="Dashboard">
                {dashboardContent?.slice(0, 3)?.map((item) => (
                  <CommandItem
                    key={`dashboard_item-default-${item.slug}`}
                    className="flex items-center gap-2 cursor-pointer"
                    onSelect={() => router(`/#${item.slug}`)}
                  >
                    {item.icon} {item.name}
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandSeparator className="my-3" />
              <CommandGroup heading="Widget">
                {widgetContent?.slice(0, 3)?.map((item) => (
                  <CommandItem
                    key={`widget_item-default-${item.slug}`}
                    className="flex items-center gap-2 cursor-pointer"
                    onSelect={() => router(`/widgets?tab=all/#${item.slug}`)}
                  >
                    <div>
                      <Avatar className="rounded-full h-5 w-5 flex justify-center items-center">
                        <AvatarImage
                          className="w-5 h-5"
                          src={item?.logo}
                          alt="Widget Logo"
                        />
                        <AvatarFallback>
                          <RiLandscapeFill size={20} color="#CACFD8" />
                        </AvatarFallback>
                      </Avatar>
                    </div>{" "}
                    {item.title}
                  </CommandItem>
                ))}
              </CommandGroup>
            </>
          )}
        </CommandList>
      </div>
    </CommandDialog>
  );
};

export default GlobalSearchOld;
