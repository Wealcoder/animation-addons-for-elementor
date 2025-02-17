import TemplateTopBar from "./TemplateTopBar";
import TemplateShow from "./TemplateShow";

const TemplateRightContent = ({
  searchKey,
  setSearchKey,
  filterKey,
  setFilterKey,
  setPageNum,
  allTemplate,
  setOpenSidebar
}) => {
  return (
    <div className="mx-[31px]">
      <div className="mt-6 mb-8">
        <TemplateTopBar
          filterKey={filterKey}
          setFilterKey={setFilterKey}
          searchKey={searchKey}
          setSearchKey={setSearchKey}
          setPageNum={setPageNum}
          setOpenSidebar={setOpenSidebar}
        />
      </div>
      <div className="mb-10">
        <TemplateShow allTemplate={allTemplate} />
      </div>
    </div>
  );
};

export default TemplateRightContent;
