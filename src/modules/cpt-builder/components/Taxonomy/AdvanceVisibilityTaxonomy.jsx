import SwitchLabelDesc from "../common/SwitchLabelDesc";

const AdvanceVisibilityTaxonomy = ({ taxonomyData, setTaxonomyData }) => {
  const UpdateData = (value, pKey) => {
    setTaxonomyData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="flex flex-col gap-7 py-8">
        <SwitchLabelDesc
          uniqId={"wcf-ct-show-in-ui-switch"}
          label={"Show In UI"}
          description={
            "Items can be edited and managed in the admin dashboard."
          }
          value={taxonomyData.show_ui}
          valueChange={UpdateData}
          changeableValueName="show_ui"
        />
        {taxonomyData.show_ui && (
          <SwitchLabelDesc
            uniqId={"wcf-ct-show-in-admin-menu"}
            label={"Show In Admin Menu"}
            description={"Admin editor navigation in the sidebar menu."}
            value={taxonomyData.show_in_menu}
            valueChange={UpdateData}
            changeableValueName="show_in_menu"
          />
        )}
      </div>
      <div className="flex flex-col gap-7 py-8 border-t">
        <SwitchLabelDesc
          uniqId={"wcf-ct-appearance-menus-support"}
          label={"Appearance Menus Support"}
          description={
            "Allow items to be added to menus in the 'Appearance' > 'Menus' screen. Must be turned on in 'Screen options'."
          }
          value={taxonomyData.show_in_nav_menus}
          valueChange={UpdateData}
          changeableValueName="show_in_nav_menus"
        />
        <SwitchLabelDesc
          uniqId={"wcf-ct-tag-cloud"}
          label={"Tag Cloud"}
          description={"List the taxonomy in the Tag Cloud Widget controls."}
          value={taxonomyData.show_tagcloud}
          valueChange={UpdateData}
          changeableValueName="show_tagcloud"
        />
        <SwitchLabelDesc
          uniqId={"wcf-ct-quick-edit"}
          label={"Quick Edit"}
          description={"Show the taxonomy in the quick/bulk edit panel."}
          value={taxonomyData.show_in_quick_edit}
          valueChange={UpdateData}
          changeableValueName="show_in_quick_edit"
        />

        <SwitchLabelDesc
          uniqId={"wcf-ct-show-admin-column"}
          label={"Show Admin Column"}
          description={
            "Display a column for the taxonomy on post type listing screens."
          }
          value={taxonomyData.show_admin_column}
          valueChange={UpdateData}
          changeableValueName="show_admin_column"
        />
      </div>
    </div>
  );
};

export default AdvanceVisibilityTaxonomy;
