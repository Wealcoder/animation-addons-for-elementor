import { DashboardSearchContent } from "@/lib/dashboardSearchContent";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useEffect, useState } from "react";
import { AllWidgetList } from "@/config/data/allWidgetList";
import { Avatar, AvatarFallback, AvatarImage } from "../ui/avatar";
import { RiLandscapeFill } from "react-icons/ri";
import {
  ALLGeneralExtensionList,
  AllGSAPExtensionList,
} from "@/config/data/allExtensionList";
import { Input } from "../ui/input";
import { Separator } from "../ui/separator";
import { generateSearchContent } from "@/lib/utils";

const GlobalSearch = ({ open, setOpen }) => {
  const dashboardContent = DashboardSearchContent;
  const widgetContent = AllWidgetList;

  const [storeAllContent, setStoreAllContent] = useState([]);
  const [allSearchContent, setAllSearchContent] = useState([]);

  const [inputValue, setInputValue] = useState("");

  useEffect(() => {
    const dashboard = generateSearchContent(
      DashboardSearchContent,
      "Dashboard"
    );
    const widgets = generateSearchContent(AllWidgetList, "Widgets");
    const gsapExtensions = generateSearchContent(
      AllGSAPExtensionList,
      "GSAP Extension",
      "extensions"
    );

    const generalExtensions = generateSearchContent(
      ALLGeneralExtensionList,
      "General Extension"
    );

    const templates = {
      category: "Stater Template",
      items: [
        {
          title: "Templates",
          slug: "",
          path: "/stater-template",
        },
      ],
    };

    setStoreAllContent([
      dashboard,
      widgets,
      gsapExtensions,
      generalExtensions,
      templates,
    ]);
  }, []);

  const filterContent = (value) => {
    setInputValue(value);
    const result = [];
    storeAllContent?.map((el) => {
      const itemResult = el.items?.filter((item) => {
        if (item?.name && item.name.toLowerCase().match(value)) {
          return item;
        } else if (item?.title && item.title.toLowerCase().match(value)) {
          return item;
        }
      });
      if (itemResult && itemResult.length) {
        const data = {
          category: el.category,
          items: itemResult,
        };
        result.push(data);
      }
    });
    setAllSearchContent(result);
  };

  const changeRoute = (value, path, cTab, pluginId) => {
    const url = new URL(window.location.href);
    if (path) {
      url.searchParams.set("tab", path);
    } else {
      url.searchParams.set("tab", "dashboard");
    }
    if (cTab) {
      url.searchParams.set("cTab", cTab);
    }
    if (pluginId) {
      url.searchParams.set("pluginId", pluginId);
    }
    url.hash = value;
    window.history.replaceState({}, "", url);
    setOpen(false);
  };

  return (
    <Dialog open={open} onOpenChange={setOpen} className={"bg-transparent"}>
      <DialogContent
        hideClose={true}
        className="max-w-[506px] h-[450px] overflow-hidden pr-0"
      >
        <DialogHeader>
          <DialogTitle className="hidden"></DialogTitle>
          <DialogDescription className="hidden"></DialogDescription>
        </DialogHeader>
        <div>
          <div className="bg-background rounded-[10px] border-2 border-border shadow-common-2">
            <Input
              placeholder="Search Anything"
              className="focus-visible:border-0"
              value={inputValue}
              onChange={(e) => filterContent(e.target.value)}
            />
          </div>
          <div className="h-[350px]">
            <div className="bg-background mt-2.5 rounded-[10px] border-2 border-border shadow-common-2">
              <div className="max-h-[350px] overflow-y-auto overflow-x-hidden shadow-select border-0 py-3">
                {inputValue ? (
                  <div className="overflow-hidden text-text px-2 text-sm">
                    {allSearchContent?.map((category, i) => (
                      <div
                        key={`category-${category.category}-${i}`}
                        className="border-b border-border last:border-0 py-3 first:pt-0 last:pb-0"
                      >
                        <h4 className="text-xs text-text-secondary px-2.5 mb-2">
                          {category.category}
                        </h4>
                        {category?.items?.map((item) => (
                          <div
                            key={`dashboard_item-default-${item.slug}`}
                            className="search-item"
                            onClick={() => {
                              changeRoute(
                                item.slug,
                                item?.path,
                                item?.location?.cTab,
                                item?.location?.pluginId
                              );
                              console.log(item?.path);
                            }}
                          >
                            {item.icon ? (
                              item.icon
                            ) : item.logo ? (
                              <div>
                                <Avatar className="rounded-full h-5 w-5 flex justify-center items-center">
                                  <AvatarImage
                                    className="w-5 h-5"
                                    src={item?.logo}
                                    alt="Widget Logo"
                                  />
                                  <AvatarFallback>
                                    <RiLandscapeFill
                                      size={20}
                                      color="#CACFD8"
                                    />
                                  </AvatarFallback>
                                </Avatar>
                              </div>
                            ) : (
                              ""
                            )}{" "}
                            {item.name || item.title}
                          </div>
                        ))}
                      </div>
                    ))}
                    {!allSearchContent.length ? (
                      <div>No results found.</div>
                    ) : (
                      ""
                    )}
                  </div>
                ) : (
                  <div className="overflow-hidden text-text px-2 text-sm">
                    <div>
                      <h4 className="text-xs text-text-secondary px-2.5 mb-2">
                        Dashboard
                      </h4>
                      {dashboardContent?.slice(0, 3)?.map((item) => (
                        <div
                          key={`category_item-${item.slug}`}
                          className="search-item"
                          onClick={() => changeRoute(item.slug)}
                        >
                          {item.icon} {item.name}
                        </div>
                      ))}
                    </div>
                    <Separator className="my-2" />
                    <div>
                      <h4 className="text-xs text-text-secondary px-2.5 mb-2">
                        Widgets
                      </h4>
                      {widgetContent?.slice(0, 3)?.map((item) => (
                        <div
                          key={`dashboard_item-default-${item.slug}`}
                          className="search-item"
                          onClick={() =>
                            changeRoute(item.slug, "widgets", "all")
                          }
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
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default GlobalSearch;
