import { Input } from "@/components/ui/input";
import { RiCloseLine, RiSearchLine } from "react-icons/ri";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const TemplateTopBar = ({
  filterKey,
  setFilterKey,
  searchKey,
  setSearchKey,
  setPageNum,
}) => {
  return (
    <div className="flex justify-between items-center gap-5">
      <h3 className="text-xl font-medium">All Templates</h3>
      <div className="flex justify-end items-center">
        <div className="ml-6 mr-2">
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
      </div>
    </div>
  );
};

export default TemplateTopBar;
