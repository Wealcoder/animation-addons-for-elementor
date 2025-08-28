import { Check, ChevronsUpDown } from "lucide-react";
import { cn } from "S/lib/utils";
import { Button } from "S/components/ui/button";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "S/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "S/components/ui/popover";
import { Label } from "S/components/ui/label";
import { useState } from "react";

const SelectWithSearch = ({
  className,
  selectTriggerClassName,
  selectContentClassName,
  labelClassName,
  descriptionClassName,
  label,
  placeholder = "Select an item...",
  description,
  uniqId,
  selectItems,
  value,
  valueChange,
  changeableValueName,
}) => {
  const [open, setOpen] = useState(false);

  return (
    <div className={cn("grid w-full max-w-lg items-center gap-2", className)}>
      {label && (
        <Label htmlFor={uniqId} className={cn(labelClassName)}>
          {label}
        </Label>
      )}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            id={uniqId}
            variant="outline"
            role="combobox"
            aria-expanded={open}
            className={cn(
              "w-full justify-between rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm",
              selectTriggerClassName
            )}
          >
            {value || placeholder}
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className={cn("w-[512px] p-0 pt-2", selectContentClassName)}>
          <Command>
            <CommandInput placeholder={`Search ${label || "items"}...`} />
            <CommandList>
              <CommandEmpty>No items found.</CommandEmpty>
              <CommandGroup>
                {selectItems.map((item) => (
                  <CommandItem
                    key={uniqId + item}
                    value={item}
                    onSelect={(currentValue) => {
                      valueChange(
                        currentValue === value ? "" : currentValue,
                        changeableValueName
                      );
                      setOpen(false);
                    }}
                    className='cursor-pointer'
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4",
                        value === item ? "opacity-100" : "opacity-0"
                      )}
                    />
                    {item}
                  </CommandItem>
                ))}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
      {description && (
        <small className={cn(descriptionClassName)}>{description}</small>
      )}
    </div>
  );
};

export default SelectWithSearch;
