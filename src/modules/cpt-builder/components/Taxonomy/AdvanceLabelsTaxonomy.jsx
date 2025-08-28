import React, { useState } from "react";
import InputLabelDesc from "../common/InputLabelDesc";
import { CTALabelConfig } from "S/config/CTALabelConfig";
import { Button } from "../ui/button";
import TextAreaDesc from "../common/TextAreaDesc";

const AdvanceLabelsTaxonomy = ({ taxonomyData, setTaxonomyData }) => {
  const [taxonomyLabel, setTaxonomyLabel] = useState(taxonomyData.label || {});

  const updateTaxonomyLabel = (value, pKey) => {
    const result = { ...taxonomyLabel, [pKey]: value };
    setTaxonomyLabel(result);
    setTaxonomyData((prev) => ({
      ...prev,
      label: result,
    }));
  };

  const generateField = (item) => {
    switch (item?.iType) {
      case "textarea":
        return (
          <TextAreaDesc
            uniqId={item.uniqId}
            label={item.label}
            placeholder={item.placeholder}
            description={item.description}
            value={taxonomyLabel[item.key] ?? ""}
            valueChange={updateTaxonomyLabel}
            changeableValueName={item.key}
          />
        );
      default:
        return (
          <InputLabelDesc
            uniqId={item.uniqId}
            label={item.label}
            placeholder={item.placeholder}
            description={item.description}
            value={taxonomyLabel[item.key] ?? ""}
            valueChange={updateTaxonomyLabel}
            changeableValueName={item.key}
          />
        );
    }
  };

  return (
    <div>
      <div className="flex justify-end items-center">
        <Button
          className="h-6 rounded-md"
          onClick={() => {
            setTaxonomyLabel({});
            setTaxonomyData((prev) => ({
              ...prev,
              label: {},
            }));
          }}
        >
          Clear
        </Button>
      </div>
      <div className="flex flex-col gap-5 py-8">
        {CTALabelConfig?.map((item, i) => (
          <React.Fragment key={`wcf-cpt-adv-label-${i}`}>
            {generateField(item)}
          </React.Fragment>
        ))}
      </div>
    </div>
  );
};

export default AdvanceLabelsTaxonomy;
