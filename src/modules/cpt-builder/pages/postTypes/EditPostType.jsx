import { Button } from "S/components/ui/button";
import { usePostType, useTab } from "S/hooks/app.hooks";

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "S/components/ui/breadcrumb";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "S/components/ui/accordion";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "S/components/ui/tabs";
import AdvanceGeneralPostType from "S/components/postTypes/AdvanceGeneralPostType";
import AdvanceLabelsPostType from "S/components/postTypes/AdvanceLabelsPostType";
import AdvanceVisibilityPostType from "S/components/postTypes/AdvanceVisibilityPostType";
import AdvanceURLsPostType from "S/components/postTypes/AdvanceURLsPostType";
import AdvancePermissionsPostType from "S/components/postTypes/AdvancePermissionsPostType";
import InputLabelDesc from "S/components/common/InputLabelDesc";
import SwitchLabelDesc from "S/components/common/SwitchLabelDesc";
import { useCallback, useEffect, useState } from "react";
import MultiSelectLabelDesc from "S/components/common/MultiSelectLabelDesc";
import { debounceFn, generateLabelValue } from "S/lib/utils";
import { Label } from "S/components/ui/label";
import { Input } from "S/components/ui/input";
import AdvanceRestApiPostType from "S/components/postTypes/AdvanceRestApiPostType";

const EditPostType = () => {
  const { updateTabKey } = useTab();
  const { setAllPostTypes } = usePostType();
  const url = new URL(window.location.href);
  const postId = url.searchParams.get("postId");

  const [postTypeData, setPostTypeData] = useState({});
  const [postTypeTitle, setPostTypeTitle] = useState("");
  const [taxonomyItems, setTaxonomyItems] = useState([]);
  const [pTKSpaceError, setPTKSpaceError] = useState("");
  const [pTKError, setPTKError] = useState("");
  const [disablePTK, setDisablePTK] = useState(false);

  const changeRoute = (value) => {
    const pageQuery = url.searchParams.get("page");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);

    updateTabKey(value);
  };

  const getPostType = async (value) => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_post_type_builder_single_item",
          post_type_id: value,
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          setPostTypeData(return_content?.data?.meta);
          setDisablePTK(return_content?.data?.meta?.post_type_key);
          const result = generateLabelValue(return_content?.data?.taxonomies);
          setTaxonomyItems(result);
          setPostTypeTitle(return_content?.data?.title);
        });
    } catch (error) {
      console.log(error);
    }
  };

  useEffect(() => {
    getPostType(postId);
  }, []);

  const updatePostTypeData = (value, pKey) => {
    if (pKey === "post_type_key") {
      checkPostTypeKey(value);
    }

    setPostTypeData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  const checkPostTypeKey = useCallback(
    debounceFn(async (value) => {
      try {
        await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
          body: new URLSearchParams({
            action: "aae_post_type_exist",
            post_type: value,
            nonce: WCF_ADDONS_ADMIN.nonce,
          }),
        })
          .then((response) => {
            return response.json();
          })
          .then((return_content) => {
            if (return_content?.hasExist) {
              setPTKError("Post Type Key Already Exist");
            } else {
              setPTKError("");
            }
          });
      } catch (error) {
        console.log(error);
      }
    }),
    []
  );

  const updatePostTypeTitle = (value, pKey) => {
    setPostTypeTitle(value);
  };

  const updatePostType = async () => {
    if (!pTKSpaceError && !pTKError) {
      try {
        await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
          body: new URLSearchParams({
            action: "aae_add_or_update_new_post_type_builder",
            post_meta: JSON.stringify(postTypeData),
            post_type_id: postId,
            post_type_title: postTypeTitle,
            nonce: WCF_ADDONS_ADMIN.nonce,
          }),
        })
          .then((response) => {
            return response.json();
          })
          .then((return_content) => {
            setAllPostTypes(return_content?.data);
            changeRoute("post-types");
          });
      } catch (error) {
        console.log(error);
      }
    }
  };

  return (
    <div className="min-h-[70vh] px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <div className="flex gap-11 justify-between items-center">
          <div>
            <h2 className="text-[18px] font-medium ">{postTypeTitle}</h2>
          </div>
          <div>
            <Button onClick={() => updatePostType()}>Save Post Type</Button>
          </div>
        </div>
      </div>
      <div className="mt-8">
        <Breadcrumb>
          <BreadcrumbList className="text-brand text-xs items-center">
            <BreadcrumbItem>
              <BreadcrumbLink
                onClick={() => changeRoute("post-types")}
                className="text-text-2"
              >
                Post Types
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />

            <BreadcrumbItem>
              <BreadcrumbPage>Update Post Type</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
      </div>
      <div className="mt-11">
        <div>
          <div className="flex flex-col gap-5 pb-11">
            <InputLabelDesc
              uniqId={"wcf-cpt-plural-label"}
              label={"Plural Label"}
              placeholder={"Enter Plural Label"}
              value={postTypeTitle}
              valueChange={updatePostTypeTitle}
              changeableValueName="post_title"
            />

            <InputLabelDesc
              uniqId={"wcf-cpt-singular-label"}
              label={"Singular Label"}
              placeholder={"Enter Singular Label"}
              value={postTypeData.singular_name}
              valueChange={updatePostTypeData}
              changeableValueName="singular_name"
            />

            <div className="grid w-full max-w-lg items-center gap-2">
              <Label htmlFor={"wcf-cpt-post-type-key"}>{"Post Type Key"}</Label>
              <Input
                id={"wcf-cpt-post-type-key"}
                placeholder={"Enter Post Type Key"}
                value={postTypeData.post_type_key}
                disabled={disablePTK}
                onChange={(e) => {
                  const inputValue = e.target.value;
                  const sanitizedValue = inputValue.replace(/\s+/g, "");
                  if (inputValue !== sanitizedValue) {
                    setPTKSpaceError("Spaces are not allowed.");
                  } else {
                    setPTKSpaceError("");
                  }
                  updatePostTypeData(sanitizedValue, "post_type_key");
                }}
              />
              {(pTKSpaceError || pTKError) && (
                <small className="text-brand">
                  {pTKSpaceError || pTKError}
                </small>
              )}
              <small>
                {
                  "Lower case letters, underscores and dashes only, Max 20 characters."
                }
              </small>
            </div>

            <MultiSelectLabelDesc
              uniqId={"wcf-cpt-taxonomies-select"}
              label={"Taxonomies"}
              placeholder={"Select"}
              showItems={taxonomyItems}
              description={
                "Select existing taxonomies to classify items of the post type."
              }
              selected={postTypeData.taxonomies}
              valueChange={setPostTypeData}
              changeableValueName="taxonomies"
            />
          </div>
          <div className="flex flex-col gap-5 pb-11 pt-11 border-t">
            <SwitchLabelDesc
              uniqId={"wcf-cpt-public-switch"}
              label={"Public"}
              description={
                "Visible on the frontend and in the admin dashboard."
              }
              value={postTypeData.public}
              valueChange={updatePostTypeData}
              changeableValueName="public"
            />
            <SwitchLabelDesc
              uniqId={"wcf-cpt-hierarchical-switch"}
              label={"Hierarchical"}
              description={
                "Hierarchical post types can have descendants (like pages)."
              }
              value={postTypeData.hierarchical}
              valueChange={updatePostTypeData}
              changeableValueName="hierarchical"
            />
            <SwitchLabelDesc
              uniqId={"wcf-cpt-advanceConfig-active-switch"}
              label={"Active"}
              description={
                "Active post types are enabled and registered with WordPress."
              }
              value={postTypeData.active}
              valueChange={updatePostTypeData}
              changeableValueName="active"
            />
          </div>
          <div className="flex flex-col gap-5 pb-11 pt-11 border-t">
            <SwitchLabelDesc
              uniqId={"wcf-cpt-advanceConfig-switch"}
              label={"Advance Configuration"}
              description={"I know what I'm doing, show me all the options."}
              value={postTypeData.advance}
              valueChange={updatePostTypeData}
              changeableValueName="advance"
            />
          </div>
        </div>
        {postTypeData.advance && (
          <div className="border rounded-lg">
            <Accordion
              defaultValue="advance_type"
              type="single"
              collapsible
              className="w-full"
            >
              <AccordionItem value="advance_type">
                <AccordionTrigger className="px-8">
                  Advance Settings
                </AccordionTrigger>
                <AccordionContent>
                  <Tabs defaultValue="general">
                    <TabsList className="w-full justify-start py-4 px-4 border-b bg-transparent rounded-none">
                      <TabsTrigger value="general">General</TabsTrigger>
                      <TabsTrigger value="labels">Labels</TabsTrigger>
                      <TabsTrigger value="visibility">Visibility</TabsTrigger>
                      <TabsTrigger value="urls">URLs</TabsTrigger>
                      <TabsTrigger value="permissions">Permissions</TabsTrigger>
                      <TabsTrigger value="rest_api">REST API</TabsTrigger>
                    </TabsList>
                    <TabsContent value="general" className="px-8">
                      <AdvanceGeneralPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                    <TabsContent value="labels" className="px-8">
                      <AdvanceLabelsPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                    <TabsContent value="visibility" className="px-8">
                      <AdvanceVisibilityPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                    <TabsContent value="urls" className="px-8">
                      <AdvanceURLsPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                    <TabsContent value="permissions" className="px-8">
                      <AdvancePermissionsPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                    <TabsContent value="rest_api" className="px-8">
                      <AdvanceRestApiPostType
                        postTypeData={postTypeData}
                        setPostTypeData={setPostTypeData}
                      />
                    </TabsContent>
                  </Tabs>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>
        )}
      </div>
    </div>
  );
};

export default EditPostType;
