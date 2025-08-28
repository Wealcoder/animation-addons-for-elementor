import SelectWithSearch from "../common/selectWithSearch";

const AdvancePermissionTaxonomy = ({
  taxonomyCaps,
  taxonomyData,
  setTaxonomyData,
}) => {
  const updateData = (value, pKey) => {
    setTaxonomyData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="flex flex-col gap-7 py-8">
        <SelectWithSearch
          uniqId={"wcf-ct-manage-terms-capability-select"}
          label={"Manage Terms Capability"}
          placeholder={"Select"}
          description={
            "The capability name for managing terms of this taxonomy."
          }
          selectItems={taxonomyCaps}
          value={taxonomyData?.manage_teams_capability ?? "manage_categories"}
          valueChange={updateData}
          changeableValueName={`manage_teams_capability`}
        />
        <SelectWithSearch
          uniqId={"wcf-ct-edit-terms-capability-select"}
          label={"Edit Terms Capability"}
          placeholder={"Select"}
          description={
            "The capability name for editing terms of this taxonomy."
          }
          selectItems={taxonomyCaps}
          value={taxonomyData?.edit_teams_capability ?? "manage_categories"}
          valueChange={updateData}
          changeableValueName={`edit_teams_capability`}
        />
        <SelectWithSearch
          uniqId={"wcf-ct-delete-terms-capability-select"}
          label={"Delete Terms Capability"}
          placeholder={"Select"}
          description={
            "The capability name for deleting terms of this taxonomy."
          }
          selectItems={taxonomyCaps}
          value={taxonomyData?.delete_teams_capability ?? "manage_categories"}
          valueChange={updateData}
          changeableValueName={`delete_teams_capability`}
        />
        <SelectWithSearch
          uniqId={"wcf-ct-assign-terms-capability-select"}
          label={"Assign Terms Capability"}
          placeholder={"Select"}
          description={
            "The capability name for assigning terms of this taxonomy."
          }
          selectItems={taxonomyCaps}
          value={taxonomyData?.assign_teams_capability ?? "manage_categories"}
          valueChange={updateData}
          changeableValueName={`assign_teams_capability`}
        />
      </div>
    </div>
  );
};

export default AdvancePermissionTaxonomy;
