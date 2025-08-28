import InputLabelDesc from "../common/InputLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";

const AdvanceRestApiTaxonomy = ({ taxonomyData, setTaxonomyData }) => {
  const updateTaxonomyData = (value, pKey) => {
    setTaxonomyData((prev) => ({
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
        value={taxonomyData.show_in_rest}
        valueChange={updateTaxonomyData}
        changeableValueName="show_in_rest"
      />
      {taxonomyData.show_in_rest && (
        <>
          <InputLabelDesc
            uniqId={"wcf-cpt-base-URL"}
            label={"Base URL"}
            placeholder={""}
            description={"The base URL for the post type REST API URLs."}
            value={taxonomyData.rest_base ?? ""}
            valueChange={updateTaxonomyData}
            changeableValueName="rest_base"
          />
          <InputLabelDesc
            uniqId={"wcf-cpt-namespace-route"}
            label={"Namespace Route"}
            placeholder={"wp/v2"}
            description={"The namespace part of the REST API URL."}
            value={taxonomyData.rest_namespace ?? ""}
            valueChange={updateTaxonomyData}
            changeableValueName="rest_namespace"
          />
          <InputLabelDesc
            uniqId={"wcf-cpt-controller-class"}
            label={"Controller Class"}
            placeholder={"WP_REST_Posts_Controller"}
            description={
              "Optional custom controller to use instead of `WP_REST_Posts_Controller`."
            }
            value={taxonomyData.rest_controller_class ?? ""}
            valueChange={updateTaxonomyData}
            changeableValueName="rest_controller_class"
          />
        </>
      )}
    </div>
  );
};

export default AdvanceRestApiTaxonomy;
