import TemplateShow from "./TemplateShow";

const TemplateRightContent = ({ allTemplate, metaData, setMetaData }) => {
  return (
    <div className="mx-[31px]">
      <div className="mb-10">
        <TemplateShow
          allTemplate={allTemplate}
          metaData={metaData}
          setMetaData={setMetaData}
        />
      </div>
    </div>
  );
};

export default TemplateRightContent;
