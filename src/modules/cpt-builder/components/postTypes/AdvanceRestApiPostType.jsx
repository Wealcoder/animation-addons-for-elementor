import InputLabelDesc from "../common/InputLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";
import TextAreaDesc from "../common/TextAreaDesc";

const AdvanceRestApiPostType = ({ postTypeData, setPostTypeData }) => {
  const updatePostTypeData = (value, pKey) => {
    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div className="flex flex-col gap-7 py-8 max-w-[600px]">
      <SwitchLabelDesc
        uniqId={"wcf-cpt-show-in-REST-API-switch"}
        label={"Show In REST API"}
        description={
          "Exposes this post type in the REST API. Required to use the block editor."
        }
        value={postTypeData.show_in_rest}
        valueChange={updatePostTypeData}
        changeableValueName="show_in_rest"
      />
      {postTypeData.show_in_rest && (
        <>
          <InputLabelDesc
            uniqId={"wcf-cpt-base-URL"}
            label={"Base URL"}
            placeholder={""}
            description={"The base URL for the post type REST API URLs."}
            value={postTypeData.rest_base ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="rest_base"
          />
          <InputLabelDesc
            uniqId={"wcf-cpt-namespace-route"}
            label={"Namespace Route"}
            placeholder={"wp/v2"}
            description={"The namespace part of the REST API URL."}
            value={postTypeData.rest_namespace ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="rest_namespace"
          />
          <InputLabelDesc
            uniqId={"wcf-cpt-controller-class"}
            label={"Controller Class"}
            placeholder={"WP_REST_Posts_Controller"}
            description={
              "Optional custom controller to use instead of `WP_REST_Posts_Controller`."
            }
            value={postTypeData.rest_controller_class ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="rest_controller_class"
          />
          <TextAreaDesc
            uniqId={"wcf-cpt-template"}
            label={"Template"}
            placeholder={""}
            value={postTypeData.template ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="template"
            description={
              "JSON String of blocks to use as the default initial state for an editor session. Each item should be an JSON containing block name and optional attributes."
            }
          />
        </>
      )}
    </div>
  );
};

export default AdvanceRestApiPostType;
