import { useState } from "react";
import TemplateTopBar from "./TemplateTopBar";
import TemplateShow from "./TemplateShow";
import TemplatePagination from "./TemplatePagination";

const TemplateRightContent = () => {
  const [searchKey, setSearchKey] = useState("");
  const [filterKey, setFilterKey] = useState("popular");

  return (
    <div className="mx-8">
      <div className="mt-6 mb-8">
        <TemplateTopBar
          filterKey={filterKey}
          setFilterKey={setFilterKey}
          searchKey={searchKey}
          setSearchKey={setSearchKey}
        />
      </div>
      <div className="mb-10">
        <TemplateShow />
      </div>
      <div className="py-5">
        <TemplatePagination />
      </div>
    </div>
  );
};

export default TemplateRightContent;
