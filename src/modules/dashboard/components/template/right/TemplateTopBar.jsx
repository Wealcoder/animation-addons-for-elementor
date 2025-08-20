import { Input } from "@/components/ui/input";
import { RiCloseLine, RiFilterLine, RiSearchLine } from "react-icons/ri";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";

const TemplateTopBar = ({
  filterKey,
  setFilterKey,
  searchKey,
  setSearchKey,
  setPageNum,
  setOpenSidebar,
}) => {
  return (
    <div className="flex justify-between items-center gap-5">
      <h3 className="text-xl font-medium">All Templates</h3>
      <div className="flex justify-end items-center gap-2">
        <div className="ml-6">
          <div className="relative">
            <RiSearchLine className="absolute left-3 top-2.5 h-5 w-5 text-icon-secondary" />
            <Input
              value={searchKey}
              onChange={(e) => {
                setSearchKey(e.target.value);
                setPageNum(1);
              }}
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
          <Select
            value={filterKey}
            onValueChange={(value) => {
              setFilterKey(value);
              setPageNum(1);
            }}
          >
            <SelectTrigger className="w-[119px] rounded-[10px] h-10">
              <SelectValue placeholder="Filter" />
            </SelectTrigger>
            <SelectContent className="w-[119px] rounded-[10px]" align="end">
              <SelectGroup>
                <SelectItem value="popular">Popular</SelectItem>
                <SelectItem value="latest">Latest</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
        <div className="lg:hidden">
          <Button
            onClick={() => setOpenSidebar((prev) => !prev)}
            variant="secondary"
            size="icon"
          >
            <RiFilterLine size={20} className="text-icon-secondary" />
          </Button>
        </div>
      </div>
    </div>
  );
};

export default TemplateTopBar;
