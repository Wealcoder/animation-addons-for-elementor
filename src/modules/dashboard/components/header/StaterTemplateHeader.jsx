import {
  NavigationMenu2,
  NavigationMenuContent2,
  NavigationMenuItem2,
  NavigationMenuList2,
  NavigationMenuTrigger2,
} from "@/components/ui/navigation-menu-2";
import { useEffect, useState } from "react";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Badge } from "../ui/badge";
import { CircleX } from "lucide-react";
import templateMenuJson from "@/config/starterTemplateMenu.json";

export function StaterTemplateHeader({ metaData, setMetaData }) {
  const [menuData, setMenuData] = useState([]);

  useEffect(() => {
    if (
      WCF_ADDONS_ADMIN.template_menu &&
      WCF_ADDONS_ADMIN.template_menu.length
    ) {
      setMenuData(WCF_ADDONS_ADMIN.template_menu);
    } else {
      setMenuData(templateMenuJson);
    }
  }, []);

  const { selectedCategory, tempSelectedCategory } = metaData || {};

  return (
    <NavigationMenu2 viewport={false}>
      <NavigationMenuList2 className="gap-[25px]">
        {
          menuData?.map((gItem) => (
            <NavigationMenuItem2 key={gItem.id}>
              <NavigationMenuTrigger2 className="px-0 py-[21px] text-[16px] font-medium text-[#202020] data-[state=open]:text-[#F6502C] [&>svg]:w-5 [&>svg]:h-5 leading-[20px] h-full">
                {gItem.title}
              </NavigationMenuTrigger2>
              <NavigationMenuContent2 className="-left-[30px] p-0">
                <div className="grid grid-cols-2 w-[595px] p-[30px]">
                  <div className="min-h-[300px]  pr-5 border-r border-solid border-[#1212121A]">
                    <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                      {gItem.title}
                    </p>
                    <div className="flex flex-col gap-2.5 pb-2 border-b border-solid border-[#1212121A]">
                      {gItem?.children.map((item) => (
                        <div
                          className="flex items-center gap-1.5"
                          key={item.id}
                        >
                          <Checkbox
                            id={item.id}
                            checked={selectedCategory.includes(item.cat_id)}
                            onCheckedChange={(value) =>
                              setMetaData((prev) => {
                                let updatedCategories = [
                                  ...prev.selectedCategory,
                                ];
                                let updatedTempCategories = [
                                  ...prev.tempSelectedCategory,
                                ];

                                if (value) {
                                  if (
                                    !updatedCategories.includes(item.cat_id)
                                  ) {
                                    updatedCategories.push(item.cat_id);
                                  }
                                  if (
                                    !updatedTempCategories.some(
                                      (el) => el.cat_id === item.cat_id
                                    )
                                  ) {
                                    updatedTempCategories.push(item);
                                  }
                                } else {
                                  updatedCategories = updatedCategories.filter(
                                    (id) => id !== item.cat_id
                                  );
                                  updatedTempCategories =
                                    updatedTempCategories.filter(
                                      (el) => el.cat_id !== item.cat_id
                                    );
                                }
                                return {
                                  ...prev,
                                  selectedCategory: updatedCategories,
                                  tempSelectedCategory: updatedTempCategories,
                                  pageNum: 1,
                                };
                              })
                            }
                            className={
                              "w-3 h-3 border-[#202020] rounded-[3px] cursor-pointer"
                            }
                            svgClassName={"w-3 h-3 -mt-[1px]"}
                          />
                          <Label
                            htmlFor={item.id}
                            className="text-[15px] text-[#202020] cursor-pointer"
                          >
                            {item.title}
                          </Label>
                        </div>
                      ))}
                    </div>
                    <Button
                      variant="link"
                      className="px-0 py-0 text-[#F6502C] text-[15px] font-semibold mt-2 h-5 uppercase"
                      onClick={() => {
                        setMetaData((prev) => {
                          let updatedCategories = [...prev.selectedCategory];
                          let updatedTempCategories = [
                            ...prev.tempSelectedCategory,
                          ];

                          updatedTempCategories = updatedTempCategories.filter(
                            (el) => el.parent !== gItem.id
                          );

                          updatedCategories = updatedTempCategories.map(
                            (el) => el.cat_id
                          );

                          return {
                            ...prev,
                            selectedCategory: updatedCategories,
                            tempSelectedCategory: updatedTempCategories,
                            pageNum: 1,
                          };
                        });
                      }}
                    >
                      uncheck All
                    </Button>
                  </div>
                  <div className="min-h-[300px] pl-5">
                    <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                      Selected Services
                    </p>
                    <div className="flex flex-wrap items-center gap-2.5">
                      {tempSelectedCategory?.map((sCat) => (
                        <Badge
                          key={sCat.cat_id}
                          variant="secondary"
                          className="bg-[#F6502C] text-white h-6 px-2 text-sm gap-1 font-medium rounded-[6px]"
                        >
                          {sCat.title}
                          <CircleX
                            className="w-[14px] h-[14px] cursor-pointer"
                            onClick={() => {
                              setMetaData((prev) => {
                                let updatedCategories = [
                                  ...prev.selectedCategory,
                                ];
                                let updatedTempCategories = [
                                  ...prev.tempSelectedCategory,
                                ];

                                updatedCategories = updatedCategories.filter(
                                  (id) => id !== sCat.cat_id
                                );
                                updatedTempCategories =
                                  updatedTempCategories.filter(
                                    (el) => el.cat_id !== sCat.cat_id
                                  );
                                return {
                                  ...prev,
                                  selectedCategory: updatedCategories,
                                  tempSelectedCategory: updatedTempCategories,
                                  pageNum: 1,
                                };
                              });
                            }}
                          />
                        </Badge>
                      ))}
                    </div>
                  </div>
                </div>
              </NavigationMenuContent2>
            </NavigationMenuItem2>
          )) // Show actual menu items when loaded
        }
      </NavigationMenuList2>
    </NavigationMenu2>
  );
}
