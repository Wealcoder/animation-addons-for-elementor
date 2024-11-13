import { RiApps2AddLine } from "react-icons/ri";
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
import { useActiveItem, useExtensions } from "@/hooks/app.hooks";

const ExtensionTopBar = ({ filterKey, setFilterKey }) => {
  const { allExtensions } = useExtensions();
  const { updateActiveFullExtension } = useActiveItem();

  const extensionCount = WCF_ADDONS_ADMIN.extensions;

  return (
    <div className="grid grid-cols-2 gap-11 justify-between items-center">
      <div className="flex items-center gap-3">
        <div className="border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
          <RiApps2AddLine size={24} color="#FC6848" />
        </div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">Extensions</h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label">
              {extensionCount?.total} Total Extensions
            </p>
            <Dot className="w-4 h-4 text-icon-secondary" strokeWidth={4} />
            <p className="text-sm text-label ">
              {Object.keys(extensionCount?.active).length} Active Extensions
            </p>
          </div>
        </div>
      </div>
      <div className="flex justify-end items-center gap-5">
        <div className="flex items-center space-x-2">
          <Switch
            id="global-enable-all"
            checked={allExtensions.is_active}
            onCheckedChange={(value) => updateActiveFullExtension({ value })}
          />
          <Label htmlFor="global-enable-all">Enable All</Label>
        </div>

        <div>
          <Select value={filterKey} onValueChange={setFilterKey}>
            <SelectTrigger className="w-[119px] rounded-[10px]">
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

export default ExtensionTopBar;
