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
import { useActiveItem, useWidgets } from "@/hooks/app.hooks";

const WidgetTopBar = ({
  filterKey,
  setFilterKey,
  searchKey,
  setSearchKey,
  widgetCount,
}) => {
  const { allWidgets } = useWidgets();
  const { updateActiveFullWidget } = useActiveItem();

  const setCheck = (data) => {
    updateActiveFullWidget(data);
  };
  return (
    <div className="grid grid-cols-2 gap-11 justify-between items-center">
      <div className="flex items-center gap-3">
        <div className="border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
          <RiCommandLine size={24} color="#FC6848" />
        </div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">Widgets</h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label ">
              {widgetCount?.total} Total Widgets
            </p>
            <Dot className="w-4 h-4 text-icon-secondary" strokeWidth={4} />
            <p className="text-sm text-label ">
              {widgetCount?.active} Active Widgets
            </p>
          </div>
        </div>
      </div>
      <div className="flex justify-end items-center">
        <div className="flex items-center space-x-2">
          <Switch
            id="global-enable-all"
            checked={allWidgets.is_active}
            onCheckedChange={(value) => setCheck({ value })}
          />
          <Label htmlFor="global-enable-all">Enable All</Label>
        </div>
        <div className="ml-6 mr-2">
          <div className="relative">
            <RiSearchLine className="absolute left-3 top-2.5 h-5 w-5 text-icon-secondary" />
            <Input
              value={searchKey}
              onChange={(e) => setSearchKey(e.target.value)}
              placeholder="Search Widgets"
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
              <SelectValue placeholder="Filter" />
            </SelectTrigger>
            <SelectContent className="w-[119px] rounded-[10px]" align="end">
              <SelectGroup>
                <SelectItem value="free-pro">Free + Pro</SelectItem>
                <SelectItem value="free">Free</SelectItem>
                <SelectItem value="pro">Pro</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
      </div>
    </div>
  );
};

export default WidgetTopBar;
