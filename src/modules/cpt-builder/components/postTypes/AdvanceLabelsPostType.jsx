import { CPTALabelConfig } from "S/config/CPTALabelConfig";
import InputLabelDesc from "../common/InputLabelDesc";
import { Button } from "../ui/button";
import { useState } from "react";

const AdvanceLabelsPostType = ({ postTypeData, setPostTypeData }) => {
  const [postTypeLabel, setPostTypeLabel] = useState(postTypeData.label || {});

  const updatePostTypeLabel = (value, pKey) => {
    const result = { ...postTypeLabel, [pKey]: value };
    setPostTypeLabel(result);
    setPostTypeData((prev) => ({
      ...prev,
      label: result,
    }));
  };

  return (
    <div>
      <div className="flex justify-end items-center">
        <Button
          className="h-6 rounded-md"
          onClick={() => {
            setPostTypeLabel({});
            setPostTypeData((prev) => ({
              ...prev,
              label: {},
            }));
          }}
        >
          Clear
        </Button>
      </div>
      <div className="flex flex-col gap-5 py-8">
        {CPTALabelConfig?.map((item, i) => (
          <InputLabelDesc
            key={`wcf-cpt-adv-label-${i}`}
            uniqId={item.uniqId}
            label={item.label}
            placeholder={item.placeholder}
            description={item.description}
            value={postTypeLabel[item.key] ?? ""}
            valueChange={updatePostTypeLabel}
            changeableValueName={item.key}
          />
        ))}
      </div>
    </div>
  );
};

export default AdvanceLabelsPostType;
