import { Command as CommandPrimitive } from "cmdk";
import { useState, useRef, useCallback } from "react";

import {
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "../ui/command";
import { cn } from "@/lib/utils";

const SearchItem = ({
  options,
  setActiveItem,
  setOpenSearch,
  placeholder,
  emptyMessage,
}) => {
  const inputRef = useRef(null);

  const [search, setSearch] = useState("");
  const [isOpen, setOpen] = useState(false);

  const handleBlur = useCallback(() => {
    setOpen(false);
    setOpenSearch(false);
  }, []);

  const showSelectedItem = (id) => {
    if (id) {
      setOpen(false);
      setOpenSearch(false);
      setActiveItem(id);
      const element = document.getElementById(id);
      element.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
      setTimeout(() => {
        setActiveItem("");
      }, 3000);
    }
  };

  return (
    <CommandPrimitive className="rounded-[10px] border h-10 w-[208px]">
      <div>
        <CommandInput
          ref={inputRef}
          onBlur={handleBlur}
          onFocus={() => {
            setActiveItem("");
            setOpen(true);
          }}
          onInput={() => setOpen(true)}
          onClick={() => {
            setActiveItem("");
            setOpen(true);
            setOpenSearch(true);
          }}
          placeholder={placeholder}
          value={search}
          onValueChange={setSearch}
          className="text-sm pb-1"
        />
      </div>
      <div className="relative">
        <div
          className={cn(
            "animate-in fade-in-0 zoom-in-95 absolute top-0 z-10 w-full rounded-xl bg-white mt-2 outline-none",
            isOpen ? "block" : "hidden"
          )}
        >
          <CommandList className={cn(search ? "rounded-lg" : "")}>
            {search && options.length > 0 ? (
              <CommandGroup>
                {options.map((option) => {
                  return (
                    <CommandItem
                      key={option.value}
                      value={option.label}
                      onMouseDown={(event) => {
                        event.preventDefault();
                        event.stopPropagation();
                      }}
                      onSelect={(value) =>
                        showSelectedItem(
                          value.toLowerCase().replace(/\s/g, "-")
                        )
                      }
                      className={cn("w-full cursor-pointer")}
                    >
                      {option.label}
                    </CommandItem>
                  );
                })}
              </CommandGroup>
            ) : null}
            {search ? (
              <CommandPrimitive.Empty className="select-none rounded-sm px-2 py-3 text-center text-sm">
                {emptyMessage}
              </CommandPrimitive.Empty>
            ) : null}
          </CommandList>
        </div>
      </div>
    </CommandPrimitive>
  );
};

export default SearchItem;
