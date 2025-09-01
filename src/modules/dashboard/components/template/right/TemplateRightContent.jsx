import TemplateShow from "./TemplateShow";

const TemplateRightContent = ({ allTemplate }) => {
  return (
    <div className="mx-[31px]">
      <div className="mb-10">
        <TemplateShow allTemplate={allTemplate} />
      </div>
    </div>
  );
};

export default TemplateRightContent;
