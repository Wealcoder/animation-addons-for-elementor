import SwitchLabelDesc from "../common/SwitchLabelDesc";
import TextAreaDesc from "../common/TextAreaDesc";

const AdvanceGeneralTaxonomy = ({ taxonomyData, setTaxonomyData }) => {
  const updateTaxonomyData = (value, pKey) => {
    setTaxonomyData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="py-8 flex flex-col gap-5">
        <SwitchLabelDesc
          uniqId={"wcf-ct-sort-terms-switch"}
          label={"Sort Terms"}
          description={
            "Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`."
          }
          value={taxonomyData.sort}
          valueChange={updateTaxonomyData}
          changeableValueName="sort"
        />
        <SwitchLabelDesc
          uniqId={"wcf-ct-default-term-switch"}
          label={"Default Term"}
          description={
            "Create a term for the taxonomy that cannot be deleted. It will not be selected for posts by default."
          }
          value={taxonomyData.default_term}
          valueChange={updateTaxonomyData}
          changeableValueName="default_term"
        />
      </div>
      <div className="py-8 border-t">
        <TextAreaDesc
          uniqId={"wcf-ct-advanceConfig-description"}
          label={"Description"}
          placeholder={"Enter Description"}
          value={taxonomyData.description}
          valueChange={updateTaxonomyData}
          changeableValueName="description"
        />
      </div>
    </div>
  );
};

export default AdvanceGeneralTaxonomy;
