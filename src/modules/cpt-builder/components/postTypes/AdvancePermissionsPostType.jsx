import InputLabelDesc from "../common/InputLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";

const AdvancePermissionsPostType = ({ postTypeData, setPostTypeData }) => {
  const updatePostTypeData = (value, pKey) => {
    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div className="flex flex-col gap-7 py-8 max-w-[600px]">
      <SwitchLabelDesc
        uniqId={"wcf-cpt-rename-capabilities-switch"}
        label={"Rename Capabilities"}
        description={
          "By default the capabilities of the post type will inherit the 'Post' capability names, eg. edit_post, delete_posts. Enable to use post type specific capabilities, eg. edit_{singular}, delete_{plural}."
        }
        value={postTypeData.capability}
        valueChange={updatePostTypeData}
        changeableValueName="capability"
      />
      {postTypeData.capability && (
        <>
          <InputLabelDesc
            uniqId={"wcf-cpt-singular-capability-name"}
            label={"Singular Capability Name"}
            placeholder={""}
            description={
              "Choose another post type to base the capabilities for this post type."
            }
            value={postTypeData.capability_singular ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="capability_singular"
          />
          <InputLabelDesc
            uniqId={"wcf-cpt-plural-capability-name"}
            label={"Plural Capability Name"}
            placeholder={""}
            description={
              "Optionally provide a plural to be used in capabilities."
            }
            value={postTypeData.capability_plural ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="capability_plural"
          />
        </>
      )}
      <SwitchLabelDesc
        uniqId={"wcf-cpt-can-export-switch"}
        label={"Can Export"}
        description={
          "Allow the post type to be exported from 'Tools' > 'Export'."
        }
        value={postTypeData.can_export}
        valueChange={updatePostTypeData}
        changeableValueName="can_export"
      />
      <SwitchLabelDesc
        uniqId={"wcf-cpt-delete-with-user-switch"}
        label={"Delete With User"}
        description={"Delete items by a user when that user is deleted."}
        value={postTypeData.delete_with_user}
        valueChange={updatePostTypeData}
        changeableValueName="delete_with_user"
      />
    </div>
  );
};

export default AdvancePermissionsPostType;
