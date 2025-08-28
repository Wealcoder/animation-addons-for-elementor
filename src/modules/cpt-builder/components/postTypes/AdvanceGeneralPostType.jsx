import { Checkbox } from "../ui/checkbox";
import { Label } from "../ui/label";
import TextAreaDesc from "../common/TextAreaDesc";
import { useEffect, useState } from "react";

const items = [
  {
    id: "wcf-title",
    label: "Title",
    value: "title",
  },
  {
    id: "wcf-author",
    label: "Author",
    value: "author",
  },
  {
    id: "wcf-comments",
    label: "Comments",
    value: "comments",
  },
  {
    id: "wcf-trackbacks",
    label: "Trackbacks",
    value: "trackbacks",
  },
  {
    id: "wcf-editor",
    label: "Editor",
    value: "editor",
  },
  {
    id: "wcf-excerpt",
    label: "Excerpt",
    value: "excerpt",
  },
  {
    id: "wcf-revisions",
    label: "Revisions",
    value: "revisions",
  },
  {
    id: "wcf-page-attributes",
    label: "Page Attributes",
    value: "page-attributes",
  },
  {
    id: "wcf-featured-image",
    label: "Featured Image",
    value: "thumbnail",
  },
  {
    id: "wcf-custom-fields",
    label: "Custom Fields",
    value: "custom-fields",
  },
  {
    id: "wcf-post-formats",
    label: "Post Formats",
    value: "post-formats",
  },
];

const AdvanceGeneralPostType = ({ postTypeData, setPostTypeData }) => {
  const [selectedItems, setSelectedItems] = useState(
    postTypeData.supports ?? ["title", "editor", "excerpt", "thumbnail"]
  );

  const handleChange = (value) => {
    setSelectedItems((prevSelected) =>
      prevSelected.includes(value)
        ? prevSelected.filter((item) => item !== value)
        : [...prevSelected, value]
    );
  };

  useEffect(() => {
    setPostTypeData((prev) => ({
      ...prev,
      supports: selectedItems,
    }));
  }, [selectedItems]);

  const updatePostTypeData = (value, pKey) => {
    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="py-8">
        <div className="mb-4">
          <h3 className="text-base">Supports</h3>
          <p className="text-xs mt-1">
            Enable various features in the content editor.
          </p>
        </div>
        <div className="grid grid-cols-5 gap-y-2 gap-x-6">
          {items.map((item) => (
            <div
              key={item.id}
              className="group flex flex-row items-center space-x-2"
            >
              <Checkbox
                id={item.id}
                checked={selectedItems.includes(item.value)}
                onCheckedChange={() => handleChange(item.value)}
              />
              <Label htmlFor={item.id} className="text-sm font-normal flex-1">
                {item.label}
              </Label>
            </div>
          ))}
          {/* <div className="flex flex-row items-center space-x-2">
            <Checkbox />
            <Input className="text-sm font-normal h-6 w-[60%] rounded-none border-0 border-b border-solid border-border focus-visible:ring-0 shadow-none px-0" />
          </div>
          <div>
            <Button variant="link" className="h-6 text-xs px-1">
              <Plus size={14} className="mr-1" /> Add Custom
            </Button>
          </div> */}
        </div>
      </div>
      <div className="py-8 border-t">
        <TextAreaDesc
          uniqId={"wcf-cpt-advanceConfig-description"}
          label={"Description"}
          placeholder={"Enter Description"}
          value={postTypeData.description}
          valueChange={updatePostTypeData}
          changeableValueName="description"
        />
      </div>
    </div>
  );
};

export default AdvanceGeneralPostType;
