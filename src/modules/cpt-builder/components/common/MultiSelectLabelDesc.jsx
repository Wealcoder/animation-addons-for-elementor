import { X } from "lucide-react";

import { Badge } from "S/components/ui/badge";
import {
  Command,
  CommandGroup,
  CommandItem,
  CommandList,
} from "S/components/ui/command";
import { Command as CommandPrimitive } from "cmdk";
import { useCallback, useRef, useState } from "react";
import { Label } from "../ui/label";
import { cn } from "S/lib/utils";

const MultiSelectLabelDesc = ({
  className,
  labelClassName,
  descriptionClassName,
  label,
  placeholder,
  description,
  uniqId,
  showItems,
  selected = [],
  valueChange,
  changeableValueName,
}) => {
  const inputRef = useRef(null);
  const [open, setOpen] = useState(false);
  const [inputValue, setInputValue] = useState("");

  const handleUnselect = useCallback(
    (framework) => {
      const result = selected.filter((s) => s !== framework);
      valueChange((prev) => ({
        ...prev,
        [changeableValueName]: result,
      }));
    },
    [selected]
  );

  const handleKeyDown = useCallback(
    (e) => {
      const input = inputRef.current;
      if (input) {
        if (e.key === "Delete" || e.key === "Backspace") {
          if (input.value === "") {
            const result = [...selected];
            result.pop();
            valueChange((prev) => ({
              ...prev,
              [changeableValueName]: result,
            }));
          }
        }
        if (e.key === "Escape") {
          input.blur();
        }
      }
    },
    [selected]
  );

  const selectables = showItems.filter(
    (framework) => !selected.includes(framework.value)
  );

  return (
    <div className={cn("grid w-full max-w-lg items-center gap-2", className)}>
      {label && (
        <Label htmlFor={uniqId} className={cn(labelClassName)}>
          {label}
        </Label>
      )}
      <Command
        onKeyDown={handleKeyDown}
        className="overflow-visible bg-transparent"
      >
        <div className="group rounded-md border border-border px-3 py-1 text-sm">
          <div className="flex flex-wrap gap-1">
            {selected.map((framework) => {
              return (
                <Badge key={framework} variant="secondary">
                  {showItems?.find((el) => el.value === framework)?.label}
                  <button
                    className="ml-1 rounded-full outline-none px-[2px] cursor-pointer"
                    onKeyDown={(e) => {
                      if (e.key === "Enter") {
                        handleUnselect(framework);
                      }
                    }}
                    onMouseDown={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                    }}
                    onClick={() => handleUnselect(framework)}
                  >
                    <X className="h-3 w-3 text-text-secondary hover:text-text-secondary-hover" />
                  </button>
                </Badge>
              );
            })}
            {/* Avoid having the "Search" Icon */}
            <CommandPrimitive.Input
              ref={inputRef}
              value={inputValue}
              onValueChange={setInputValue}
              onBlur={() => setOpen(false)}
              onFocus={() => setOpen(true)}
              placeholder={placeholder}
              className="ml-2 flex-1 bg-transparent outline-none placeholder:text-text-secondary border-0 active:ring-0 focus:ring-0 "
            />
          </div>
        </div>
        <div className="relative">
          <CommandList>
            {open && selectables.length > 0 ? (
              <div className="absolute top-0 z-10 w-full rounded-md border bg-background text-popover-foreground shadow-md outline-none animate-in">
                <CommandGroup className="h-full overflow-auto">
                  {selectables.map((framework) => {
                    return (
                      <CommandItem
                        key={framework.value}
                        onMouseDown={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                        }}
                        onSelect={(value) => {
                          setInputValue("");
                          const result = [...selected, framework.value];
                          valueChange((prev) => ({
                            ...prev,
                            [changeableValueName]: result,
                          }));
                        }}
                        className={"cursor-pointer"}
                      >
                        {framework.label}
                      </CommandItem>
                    );
                  })}
                </CommandGroup>
              </div>
            ) : null}
          </CommandList>
        </div>
      </Command>
      {description && (
        <small className={cn(descriptionClassName)}>{description}</small>
      )}
    </div>
  );
};

export default MultiSelectLabelDesc;
