import { useState } from "react";
import InputLabelDesc from "../common/InputLabelDesc";
import SelectLabelDesc from "../common/SelectLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";

const PermalinkReWriteItems = [
  {
    key: "taxonomy_default",
    name: "Taxonomy Key (default)",
  },
  {
    key: "custom_permalink",
    name: "Custom Permalink",
  },
  {
    key: "no_permalink",
    name: "No Permalink (prevent URL rewriting)",
  },
];

const QueryVariableItems = [
  {
    key: "taxonomy_default",
    name: "Taxonomy Key (default)",
  },
  {
    key: "custom_query_variable",
    name: "Custom Query Variable",
  },
  {
    key: "no_query",
    name: "No Query Variable Support",
  },
];

const AdvanceURLsTaxonomy = ({ taxonomyData, setTaxonomyData }) => {
  const [rewrite, setRewrite] = useState(
    taxonomyData.rewrite || {}
  );

  const updateRewrite = (value, pKey) => {
    const result = { ...rewrite, [pKey]: value };
    setRewrite(result);
    setTaxonomyData((prev) => ({
      ...prev,
      rewrite: result,
    }));
  };

  const updateData = (value, pKey) => {
    setTaxonomyData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="flex flex-col gap-7 py-8">
        <SelectLabelDesc
          uniqId={"wcf-ct-permalink-rewrite-select"}
          label={"Permalink Rewrite"}
          placeholder={"Select"}
          selectItems={PermalinkReWriteItems}
          value={
            rewrite["permalink_type"] ?? PermalinkReWriteItems[0].key
          }
          valueChange={updateRewrite}
          changeableValueName={`permalink_type`}
        />
        {rewrite.permalink_type === "custom_permalink" && (
          <InputLabelDesc
            uniqId={"wcf-ct-url-slug"}
            label={"URL Slug"}
            placeholder={""}
            description={"Customize the slug used in the URL."}
            value={rewrite["slug"] ?? ""}
            valueChange={updateRewrite}
            changeableValueName="slug"
          />
        )}

        {rewrite.permalink_type !== "no_permalink" && (
          <SwitchLabelDesc
            uniqId={"wcf-ct-front-url-prefix-switch"}
            label={"Front URL Prefix"}
            description={
              "Alters the permalink structure to add the `WP_Rewrite::$front` prefix to URLs."
            }
            value={rewrite["with_front"] ?? ""}
            valueChange={updateRewrite}
            changeableValueName="with_front"
          />
        )}

        {rewrite.permalink_type !== "no_permalink" && (
          <SwitchLabelDesc
            uniqId={"wcf-ct-hierarchical-switch"}
            label={"Hierarchical"}
            description={"Parent-child terms in URLs for hierarchical taxonomies."}
            value={rewrite["hierarchical"] ?? ""}
            valueChange={updateRewrite}
            changeableValueName="hierarchical"
          />
        )}

      </div>

      <div className="flex flex-col gap-7 py-8 border-t border-border">
        <SwitchLabelDesc
          uniqId={"wcf-ct-publicly-queryable-switch"}
          label={"Publicly Queryable"}
          description={
            "URLs for an item and items can be accessed with a query string."
          }
          value={taxonomyData.publicly_queryable}
          valueChange={updateData}
          changeableValueName="publicly_queryable"
        />
      </div>
      {taxonomyData.publicly_queryable && (
        <div className="flex flex-col gap-7 py-8 border-t border-border">
          <SelectLabelDesc
            uniqId={"wcf-ct-query-variable-support-select"}
            label={"Query Variable Support"}
            placeholder={"Select"}
            selectItems={QueryVariableItems}
            description={
              "Items can be accessed using the non-pretty permalink, eg. {post_type}={post_slug}."
            }
            value={taxonomyData.query_var ?? QueryVariableItems[0].key}
            valueChange={updateData}
            changeableValueName={`query_var`}
          />

          {taxonomyData.query_var === "custom_query_variable" && (
            <InputLabelDesc
              uniqId={"wcf-ct-query-variable-support-variable"}
              label={"Query Variable"}
              placeholder={""}
              description={"Customize the query variable name."}
              value={taxonomyData.query_var_data ?? ""}
              valueChange={updateData}
              changeableValueName="query_var_data"
            />
          )}
        </div>
      )}
    </div>
  );
};

export default AdvanceURLsTaxonomy;
