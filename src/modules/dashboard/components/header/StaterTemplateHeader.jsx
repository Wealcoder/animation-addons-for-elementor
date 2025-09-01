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

export function StaterTemplateHeader() {
  const [menuData, setMenuData] = useState([]);

  useEffect(() => {
    fetch("http://www.themecrowdy.com/wp-json/wcf/v1/menu/42")
      .then((res) => res.json())
      .then((data) => {
        setMenuData(data.items);
      });
  }, []);

  return (
    <NavigationMenu2 viewport={false}>
      <NavigationMenuList2 className="gap-[25px]">
        {menuData?.map((gItem) => (
          <NavigationMenuItem2 key={gItem.id}>
            <NavigationMenuTrigger2 className="px-0 py-[21px] text-base font-medium text-[#202020] data-[state=open]:text-[#F6502C] [&>svg]:w-5 [&>svg]:h-5 leading-[20px] h-full">
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
                      <div className="flex items-center gap-1.5" key={item.id}>
                        <Checkbox
                          id={item.id}
                          className={"w-3 h-3 border-[#202020] rounded-[3px]"}
                          svgClassName={"w-3 h-3 -mt-[1px]"}
                        />
                        <Label
                          htmlFor={item.id}
                          className="text-[15px] text-[#202020]"
                        >
                          {item.title}
                        </Label>
                      </div>
                    ))}
                  </div>
                  <Button
                    variant="link"
                    className="px-0 py-0 text-[#F6502C] text-[15px] font-semibold mt-2 h-5 uppercase"
                  >
                    uncheck All
                  </Button>
                </div>
                <div className="min-h-[300px] pl-5">
                  <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                    Selected Services
                  </p>
                </div>
              </div>
            </NavigationMenuContent2>
          </NavigationMenuItem2>
        ))}
      </NavigationMenuList2>
    </NavigationMenu2>
  );
}
