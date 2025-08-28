import InputLabelDesc from "../common/InputLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";

const AdvanceVisibilityPostType = ({ postTypeData, setPostTypeData }) => {
  const updatePostTypeData = (value, pKey) => {
    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="flex flex-col gap-7 py-8">
        <SwitchLabelDesc
          uniqId={"wcf-cpt-show-in-ui-switch"}
          label={"Show In UI"}
          description={
            "Items can be edited and managed in the admin dashboard."
          }
          value={postTypeData.show_ui}
          valueChange={updatePostTypeData}
          changeableValueName="show_ui"
        />
        {postTypeData.show_ui && (
          <SwitchLabelDesc
            uniqId={"wcf-cpt-show-in-admin-menu"}
            label={"Show In Admin Menu"}
            description={"Admin editor navigation in the sidebar menu."}
            value={postTypeData.show_in_menu}
            valueChange={updatePostTypeData}
            changeableValueName="show_in_menu"
          />
        )}

        {postTypeData.show_ui && postTypeData.show_in_menu && (
          <InputLabelDesc
            uniqId={"wcf-cpt-admin-menu-parent"}
            label={"Admin Menu Parent"}
            placeholder={"edit.php?post_type={parent_page}"}
            description={
              "By default the post type will get a new top level item in the admin menu. If an existing top level item is supplied here, the post type will be added as a submenu item under it."
            }
            value={postTypeData.admin_menu_parent}
            valueChange={updatePostTypeData}
            changeableValueName="admin_menu_parent"
          />
        )}

        {postTypeData.show_ui && postTypeData.show_in_menu && (
          <InputLabelDesc
            uniqId={"wcf-cpt-menu-position"}
            label={"Menu Position"}
            placeholder={"Enter Menu Position"}
            description={
              "The position in the sidebar menu in the admin dashboard."
            }
            value={postTypeData.menu_position}
            valueChange={updatePostTypeData}
            changeableValueName="menu_position"
          />
        )}

        {postTypeData.show_ui && postTypeData.show_in_menu && (
          <InputLabelDesc
            uniqId={"wcf-cpt-menu-icon"}
            label={"Menu Icon"}
            placeholder={"Enter Menu Icon Class Name"}
            value={postTypeData.menu_icon}
            valueChange={updatePostTypeData}
            changeableValueName="menu_icon"
          />
        )}

        {postTypeData.show_ui && (
          <InputLabelDesc
            uniqId={"wcf-cpt-custom-meta-box-callback"}
            label={"Custom Meta Box Callback"}
            placeholder={"Enter Custom Meta Box Callback"}
            description={
              "A PHP function name to be called when setting up the meta boxes for the edit screen. For security, this callback will be executed in a special context without access to any superglobals like $_POST or $_GET."
            }
            value={postTypeData.register_meta_box_cb}
            valueChange={updatePostTypeData}
            changeableValueName="register_meta_box_cb"
          />
        )}
      </div>
      <div className="flex flex-col gap-7 py-8 border-t">
        {postTypeData.show_ui && (
          <SwitchLabelDesc
            uniqId={"wcf-cpt-show-in-admin-bar-switch"}
            label={"Show In Admin Bar"}
            description={
              "Appears as an item in the 'New' menu in the admin bar."
            }
            value={postTypeData.show_in_admin_bar}
            valueChange={updatePostTypeData}
            changeableValueName="show_in_admin_bar"
          />
        )}
        <SwitchLabelDesc
          uniqId={"wcf-cpt-appearance-menus-support"}
          label={"Appearance Menus Support"}
          description={
            "Allow items to be added to menus in the 'Appearance' > 'Menus' screen. Must be turned on in 'Screen options'."
          }
          value={postTypeData.show_in_nav_menus}
          valueChange={updatePostTypeData}
          changeableValueName="show_in_nav_menus"
        />
        <SwitchLabelDesc
          uniqId={"wcf-cpt-exclude-from-search"}
          label={"Exclude From Search"}
          description={
            "Sets whether posts should be excluded from search results and taxonomy archive pages."
          }
          value={postTypeData.exclude_from_search}
          valueChange={updatePostTypeData}
          changeableValueName="exclude_from_search"
        />
      </div>
    </div>
  );
};

export default AdvanceVisibilityPostType;
