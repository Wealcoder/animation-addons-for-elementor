import { __ } from "@wordpress/i18n";
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
import {
  useActiveItem,
  useAtomicExtensions,
  useExtensions,
} from "@/hooks/app.hooks";
import { useState } from "react";
import DisableAllV3Dialog from "../shared/DisableAllV3Dialog";

const ExtensionTopBar = ({
  filterKey,
  setFilterKey,
  extensionCount,
  system = "v3",
}) => {
  const { allExtensions } = useExtensions();
  const { allAtomicExtensions } = useAtomicExtensions();
  const { updateActiveFullExtension, updateActiveAtomicFullExtension } =
    useActiveItem();

  const isAtomic = system === "atomic";
  const activeExtensions = isAtomic ? allAtomicExtensions : allExtensions;

  const [confirmOpen, setConfirmOpen] = useState(false);

  const setCheck = (data) => {
    if (isAtomic) {
      updateActiveAtomicFullExtension(data);
    } else {
      updateActiveFullExtension(data);
    }
  };

  // Mirrors WidgetTopBar — see the note there. Milder consequence for
  // extensions (saved effects stop running rather than content disappearing),
  // but still not something to discover after the fact.
  const onToggleAll = (value) => {
    if (!isAtomic && value === false) {
      setConfirmOpen(true);
      return;
    }
    setCheck({ value });
  };

  return (
    <>
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 xl:gap-11 justify-between items-center">
      <div className="flex items-center gap-3">
        <div className="border rounded-full h-[52px] w-[52px] flex justify-center items-center shadow-common">
          <RiApps2AddLine size={24} color="#FC6848" />
        </div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">
              {isAtomic
                ? __("Atomic Extensions", "animation-addons-for-elementor")
                : __("Extensions", "animation-addons-for-elementor")}
            </h2>
          </div>
          <div className="flex items-center">
            <p className="text-sm text-label">
              {extensionCount?.total} {__("Total Extensions", "animation-addons-for-elementor")}
            </p>
            <Dot className="w-4 h-4 text-icon-secondary" strokeWidth={4} />
            <p className="text-sm text-label ">
              {extensionCount?.active} {__("Active Extensions", "animation-addons-for-elementor")}
            </p>
          </div>
        </div>
      </div>
      <div className="flex justify-between xl:justify-end items-center gap-5">
        <div className="flex items-center gap-x-2">
          <Switch
            id="global-enable-all"
            checked={activeExtensions?.is_active}
            onCheckedChange={onToggleAll}
            reverse
          />
          <Label htmlFor="global-enable-all">{__("Enable All", "animation-addons-for-elementor")}</Label>
        </div>

        <div>
          <Select value={filterKey} onValueChange={setFilterKey}>
            <SelectTrigger className="w-[119px] rounded-[10px]">
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

    <DisableAllV3Dialog
      open={confirmOpen}
      setOpen={setConfirmOpen}
      kind="extensions"
      activeCount={extensionCount?.active || 0}
      onConfirm={() => setCheck({ value: false })}
    />
    </>
  );
};

export default ExtensionTopBar;
