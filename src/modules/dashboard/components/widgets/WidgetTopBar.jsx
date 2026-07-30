import { __ } from "@wordpress/i18n";
import { RiCloseLine, RiCommandLine, RiSearchLine } from "react-icons/ri";
import { Dot } from "lucide-react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "../ui/input";
import { useActiveItem, useAtomicWidgets, useWidgets } from "@/hooks/app.hooks";

const WidgetTopBar = ({
  filterKey,
  setFilterKey,
  searchKey,
  setSearchKey,
  widgetCount,
  system = "v3",
}) => {
  const { allWidgets } = useWidgets();
  const { allAtomicWidgets } = useAtomicWidgets();
  const { updateActiveFullWidget, updateActiveAtomicFullWidget } =
    useActiveItem();

  const isAtomic = system === "atomic";
  const activeWidgets = isAtomic ? allAtomicWidgets : allWidgets;

  const setCheck = (data) => {
    if (isAtomic) {
      updateActiveAtomicFullWidget(data);
    } else {
      updateActiveFullWidget(data);
    }
  };
  return (
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 xl:gap-11 justify-between items-center">
      <div className="flex items-center gap-3">
        <div className="border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
          <RiCommandLine size={24} color="#FC6848" />
        </div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">
              {isAtomic
                ? __("Atomic Widgets", "animation-addons-for-elementor")
                : __("Widgets", "animation-addons-for-elementor")}
            </h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label ">
              {widgetCount?.total} {__("Total Widgets", "animation-addons-for-elementor")}
            </p>
            <Dot className="w-4 h-4 text-icon-secondary" strokeWidth={4} />
            <p className="text-sm text-label ">
              {widgetCount?.active} {__("Active Widgets", "animation-addons-for-elementor")}
            </p>
          </div>
        </div>
      </div>
      <div className="flex justify-between xl:justify-end items-center">
        <div className="flex items-center gap-x-2">
          <Switch
            id="global-enable-all"
            checked={activeWidgets?.is_active}
            onCheckedChange={(value) => setCheck({ value })}
            reverse
          />
          <Label htmlFor="global-enable-all">{__("Enable All", "animation-addons-for-elementor")}</Label>
        </div>
        <div className="ml-6 mr-2">
          <div className="relative">
            <RiSearchLine className="absolute left-3 top-2.5 h-5 w-5 text-icon-secondary" />
            <Input
              value={searchKey}
              onChange={(e) => setSearchKey(e.target.value)}
              placeholder={__("Search Widgets", "animation-addons-for-elementor")}
              className="px-9"
            />
            {searchKey ? (
              <RiCloseLine
                onClick={() => setSearchKey("")}
                className="absolute right-3 top-2.5 h-5 w-5 cursor-pointer text-icon-secondary"
              />
            ) : (
              ""
            )}
          </div>
        </div>
        <div>
          <Select value={filterKey} onValueChange={setFilterKey}>
            <SelectTrigger className="w-[119px] rounded-[10px] h-10">
              <SelectValue placeholder={__("Filter", "animation-addons-for-elementor")} />
            </SelectTrigger>
            <SelectContent className="w-[119px] rounded-[10px]" align="end">
              <SelectGroup>
                <SelectItem value="free-pro">{__("Free + Pro", "animation-addons-for-elementor")}</SelectItem>
                <SelectItem value="free">{__("Free", "animation-addons-for-elementor")}</SelectItem>
                <SelectItem value="pro">{__("Pro", "animation-addons-for-elementor")}</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
      </div>
    </div>
  );
};

export default WidgetTopBar;
