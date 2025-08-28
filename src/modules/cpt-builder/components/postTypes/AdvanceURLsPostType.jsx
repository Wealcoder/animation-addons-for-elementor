import { useState } from "react";
import InputLabelDesc from "../common/InputLabelDesc";
import SelectLabelDesc from "../common/SelectLabelDesc";
import SwitchLabelDesc from "../common/SwitchLabelDesc";

const PermalinkReWriteItems = [
  {
    key: "post_type_key",
    name: "Post Type Key (default)",
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
    key: "post_type_key",
    name: "Post Type Key (default)",
  },
  {
    key: "custom_query_variable",
    name: "Custom Query Variable",
  },
  {
    key: "no_query_variable_support",
    name: "No Query Variable Support",
  },
];

const AdvanceURLsPostType = ({ postTypeData, setPostTypeData }) => {
  const [postTypeRewrite, setPostTypeRewrite] = useState(
    postTypeData.rewrite || {}
  );

  const updatePostTypeRewrite = (value, pKey) => {
    const result = { ...postTypeRewrite, [pKey]: value };
    setPostTypeRewrite(result);
    setPostTypeData((prev) => ({
      ...prev,
      rewrite: result,
    }));
  };

  const updatePostTypeData = (value, pKey) => {
    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  return (
    <div>
      <div className="flex flex-col gap-7 py-8">
        <SelectLabelDesc
          uniqId={"wcf-cpt-permalink-rewrite-select"}
          label={"Permalink Rewrite"}
          placeholder={"Select"}
          selectItems={PermalinkReWriteItems}
          value={
            postTypeRewrite["permalink_type"] ?? PermalinkReWriteItems[0].key
          }
          valueChange={updatePostTypeRewrite}
          changeableValueName={`permalink_type`}
        />
        {postTypeRewrite.permalink_type === "custom_permalink" && (
          <InputLabelDesc
            uniqId={"wcf-cpt-url-slug"}
            label={"URL Slug"}
            placeholder={""}
            description={"Customize the slug used in the URL."}
            value={postTypeRewrite["slug"] ?? ""}
            valueChange={updatePostTypeRewrite}
            changeableValueName="slug"
          />
        )}

        {postTypeRewrite.permalink_type !== "no_permalink" && (
          <SwitchLabelDesc
            uniqId={"wcf-cpt-front-url-prefix-switch"}
            label={"Front URL Prefix"}
            description={
              "Alters the permalink structure to add the `WP_Rewrite::$front` prefix to URLs."
            }
            value={postTypeRewrite["with_front"] ?? ""}
            valueChange={updatePostTypeRewrite}
            changeableValueName="with_front"
          />
        )}

        {postTypeRewrite.permalink_type !== "no_permalink" && (
          <SwitchLabelDesc
            uniqId={"wcf-cpt-feed-url-switch"}
            label={"Feed URL"}
            description={"RSS feed URL for the post type items."}
            value={postTypeRewrite["feeds"] ?? ""}
            valueChange={updatePostTypeRewrite}
            changeableValueName="feeds"
          />
        )}

        {postTypeRewrite.permalink_type !== "no_permalink" && (
          <SwitchLabelDesc
            uniqId={"wcf-cpt-pagination-switch"}
            label={"Pagination"}
            description={
              "Pagination support for the items URLs such as the archives."
            }
            value={postTypeRewrite["pages"] ?? ""}
            valueChange={updatePostTypeRewrite}
            changeableValueName="pages"
          />
        )}
      </div>
      <div className="flex flex-col gap-7 py-8 border-t border-border">
        <SwitchLabelDesc
          uniqId={"wcf-cpt-archive-switch"}
          label={"Archive"}
          description={
            "Has an item archive that can be customized with an archive template file in your theme."
          }
          value={postTypeData.has_archive}
          valueChange={updatePostTypeData}
          changeableValueName="has_archive"
        />
        {postTypeData.has_archive && (
          <InputLabelDesc
            uniqId={"wcf-cpt-archive-slug"}
            label={"Archive Slug"}
            placeholder={""}
            description={"Custom slug for the Archive URL."}
            value={postTypeData.has_archive_slug ?? ""}
            valueChange={updatePostTypeData}
            changeableValueName="has_archive_slug"
          />
        )}
      </div>
      <div className="flex flex-col gap-7 py-8 border-t border-border">
        <SwitchLabelDesc
          uniqId={"wcf-cpt-publicly-queryable-switch"}
          label={"Publicly Queryable"}
          description={
            "URLs for an item and items can be accessed with a query string."
          }
          value={postTypeData.publicly_queryable}
          valueChange={updatePostTypeData}
          changeableValueName="publicly_queryable"
        />
      </div>
      {postTypeData.publicly_queryable && (
        <div className="flex flex-col gap-7 py-8 border-t border-border">
          <SelectLabelDesc
            uniqId={"wcf-cpt-query-variable-support-select"}
            label={"Query Variable Support"}
            placeholder={"Select"}
            selectItems={QueryVariableItems}
            description={
              "Items can be accessed using the non-pretty permalink, eg. {post_type}={post_slug}."
            }
            value={postTypeData.query_var ?? QueryVariableItems[0].key}
            valueChange={updatePostTypeData}
            changeableValueName={`query_var`}
          />

          {postTypeData.query_var === "custom_query_variable" && (
            <InputLabelDesc
              uniqId={"wcf-cpt-query-variable-support-variable"}
              label={"Query Variable"}
              placeholder={""}
              description={"Customize the query variable name."}
              value={postTypeData.query_var_data ?? ""}
              valueChange={updatePostTypeData}
              changeableValueName="query_var_data"
            />
          )}
        </div>
      )}
    </div>
  );
};

export default AdvanceURLsPostType;
