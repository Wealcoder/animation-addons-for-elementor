import {
  NavigationMenu2,
  NavigationMenuContent2,
  NavigationMenuItem2,
  NavigationMenuList2,
  NavigationMenuTrigger2,
} from "@/components/ui/navigation-menu-2";
import { RadioGroup2, RadioGroupItem2 } from "@/components/ui/radio-group-2";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";

const StarterTemplateFilter = ({ metaData, setMetaData }) => {
  const { filterData } = metaData || {};
  return (
    <NavigationMenu2 viewport={false}>
      <NavigationMenuList2 className="gap-[25px]">
        <NavigationMenuItem2>
          <NavigationMenuTrigger2 className="px-0 py-[19px] [&>svg]:hidden h-full">
            <Button className="px-3 py-0 h-[26px] bg-[#F6502C] rounded-full text-base font-medium text-white gap-1">
              <div className="flex justify-center items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 14 14"
                  fill="none"
                >
                  <path
                    d="M5.16685 7.29522C3.71526 6.20993 2.68079 5.01617 2.11595 4.34506C1.9411 4.13731 1.88381 3.98527 1.84936 3.71747C1.7314 2.80047 1.67242 2.34197 1.9413 2.04598C2.21019 1.75 2.68569 1.75 3.63669 1.75H10.3633C11.3143 1.75 11.7898 1.75 12.0587 2.04598C12.3276 2.34197 12.2686 2.80047 12.1507 3.71747C12.1162 3.98528 12.0589 4.13731 11.884 4.34506C11.3184 5.01703 10.2819 6.21291 8.82735 7.29954C8.69575 7.39789 8.60901 7.55807 8.59291 7.73582C8.44883 9.32867 8.31594 10.2011 8.23323 10.6424C8.09976 11.355 7.08937 11.7837 6.5485 12.1662C6.22656 12.3938 5.83584 12.1228 5.79412 11.7704C5.71459 11.0986 5.56477 9.73373 5.40124 7.73582C5.38655 7.55644 5.2995 7.39439 5.16685 7.29522Z"
                    stroke="white"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </div>{" "}
              Filter Items
            </Button>
          </NavigationMenuTrigger2>
          <NavigationMenuContent2 className="-left-[30px] p-0">
            <div className="grid grid-cols-5 gap-5 w-[595px] p-[30px]">
              <div>
                <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                  By Type
                </p>
                <RadioGroup2
                  value={filterData?.type}
                  onValueChange={(value) =>
                    setMetaData((pre) => ({
                      ...pre,
                      filterData: { ...filterData, type: value, pageNum: 1 },
                    }))
                  }
                >
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="all" id="type_all" />
                    <Label
                      htmlFor="type_all"
                      className="text-[15px] cursor-pointer"
                    >
                      All
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="dark" id="type_dark" />
                    <Label
                      htmlFor="type_dark"
                      className="text-[15px] cursor-pointer"
                    >
                      Dark
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="light" id="type_light" />
                    <Label
                      htmlFor="type_light"
                      className="text-[15px] cursor-pointer"
                    >
                      Light
                    </Label>
                  </div>
                </RadioGroup2>
              </div>
              <div>
                <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                  By Mode
                </p>
                <RadioGroup2
                  value={filterData?.mode}
                  onValueChange={(value) =>
                    setMetaData((pre) => ({
                      ...pre,
                      filterData: { ...filterData, mode: value, pageNum: 1  },
                    }))
                  }
                >
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="all" id="mode_all" />
                    <Label
                      htmlFor="mode_all"
                      className="text-[15px] cursor-pointer"
                    >
                      All
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="premium" id="mode_premium" />
                    <Label
                      htmlFor="mode_premium"
                      className="text-[15px] cursor-pointer"
                    >
                      Premium
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="free" id="mode_free" />
                    <Label
                      htmlFor="mode_free"
                      className="text-[15px] cursor-pointer"
                    >
                      Free
                    </Label>
                  </div>
                </RadioGroup2>
              </div>
              <div>
                <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                  By Direction
                </p>
                <RadioGroup2
                  value={filterData?.dir}
                  onValueChange={(value) =>
                    setMetaData((pre) => ({
                      ...pre,
                      filterData: { ...filterData, dir: value, pageNum: 1  },
                    }))
                  }
                >
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="all" id="dir_all" />
                    <Label
                      htmlFor="dir_all"
                      className="text-[15px] cursor-pointer"
                    >
                      All
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="rtl" id="dir_rtl" />
                    <Label
                      htmlFor="dir_rtl"
                      className="text-[15px] cursor-pointer"
                    >
                      RTL
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="ltr" id="dir_ltr" />
                    <Label
                      htmlFor="dir_ltr"
                      className="text-[15px] cursor-pointer"
                    >
                      LTR
                    </Label>
                  </div>
                </RadioGroup2>
              </div>
              <div>
                <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                  By order
                </p>
                <RadioGroup2
                  value={filterData?.order}
                  onValueChange={(value) =>
                    setMetaData((pre) => ({
                      ...pre,
                      filterData: { ...filterData, order: value, pageNum: 1  },
                    }))
                  }
                >
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="default" id="order_default" />
                    <Label
                      htmlFor="order_default"
                      className="text-[15px] cursor-pointer"
                    >
                      Default
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="popular" id="type_popular" />
                    <Label
                      htmlFor="type_popular"
                      className="text-[15px] cursor-pointer"
                    >
                      Popular
                    </Label>
                  </div>
                  <div className="flex items-center gap-1">
                    <RadioGroupItem2 value="new" id="type_new" />
                    <Label
                      htmlFor="type_new"
                      className="text-[15px] cursor-pointer"
                    >
                      New
                    </Label>
                  </div>
                </RadioGroup2>
              </div>
              <div>
                <p className="text-xs font-medium uppercase text-[#797979] pb-2 border-b border-solid border-[#1212121A] mb-3">
                  By Wishlist
                </p>
                <div className="flex items-center gap-1.5">
                  <Checkbox
                    id="wishlist_checkbox"
                    checked={filterData?.wishlist}
                    onCheckedChange={(value) =>
                      setMetaData((pre) => ({
                        ...pre,
                        filterData: { ...filterData, wishlist: value, pageNum: 1  },
                      }))
                    }
                    className={"w-3 h-3 border-[#202020] rounded-full"}
                    svgClassName={"w-3 h-3 -mt-[1px]"}
                  />
                  <Label
                    htmlFor="wishlist_checkbox"
                    className="text-[15px] text-[#202020]"
                  >
                    Wishlist
                  </Label>
                </div>
              </div>
            </div>
          </NavigationMenuContent2>
        </NavigationMenuItem2>
      </NavigationMenuList2>
    </NavigationMenu2>
  );
};

export default StarterTemplateFilter;
