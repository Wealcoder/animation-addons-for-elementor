import { useState } from "react";
import TemplateTopBar from "./TemplateTopBar";
import TemplateShow from "./TemplateShow";

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
    </div>
  );
};

export default TemplateRightContent;
