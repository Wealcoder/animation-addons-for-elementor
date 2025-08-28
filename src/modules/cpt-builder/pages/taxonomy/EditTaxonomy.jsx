import { Button } from "S/components/ui/button";
import { useTab, useTaxonomy } from "S/hooks/app.hooks";

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
import InputLabelDesc from "S/components/common/InputLabelDesc";
import SwitchLabelDesc from "S/components/common/SwitchLabelDesc";
import { useCallback, useEffect, useState } from "react";
import MultiSelectLabelDesc from "S/components/common/MultiSelectLabelDesc";
import { debounceFn, generateLabelValue } from "S/lib/utils";
import { Label } from "S/components/ui/label";
import { Input } from "S/components/ui/input";
import AdvanceGeneralTaxonomy from "S/components/Taxonomy/AdvanceGeneralTaxonomy";
import AdvanceLabelsTaxonomy from "S/components/Taxonomy/AdvanceLabelsTaxonomy";
import AdvanceVisibilityTaxonomy from "S/components/Taxonomy/AdvanceVisibilityTaxonomy";
import AdvanceURLsTaxonomy from "S/components/Taxonomy/AdvanceURLsTaxonomy";
import AdvancePermissionTaxonomy from "S/components/Taxonomy/AdvancePermissionTaxonomy";
import AdvanceRestApiTaxonomy from "S/components/Taxonomy/AdvanceRestApiTaxonomy";

const EditTaxonomy = () => {
  const { updateTabKey } = useTab();
  const { setAllTaxonomy } = useTaxonomy();
  const url = new URL(window.location.href);
  const taxonomyId = url.searchParams.get("taxonomyId");

  const [taxonomyData, setTaxonomyData] = useState({});
  const [taxonomyTitle, setTaxonomyTitle] = useState("");
  const [taxonomyItems, setTaxonomyItems] = useState([]);
  const [taxonomyCaps, setTaxonomyCaps] = useState([]);
  const [tKSpaceError, setTKSpaceError] = useState("");
  const [tKError, setTKError] = useState("");
  const [disableTK, setDisableTK] = useState(false);

  const changeRoute = (value) => {
    const pageQuery = url.searchParams.get("page");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);

    updateTabKey(value);
  };

  const getTaxonomy = async (value) => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_taxonomy_builder_single_item",
          taxonomy_id: value,
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          setTaxonomyData(return_content?.data?.meta);
          setDisableTK(return_content?.data?.meta?.taxonomy_key);
          setTaxonomyCaps(return_content?.data?.caps);
          const result = generateLabelValue(return_content?.data?.post_types);
          setTaxonomyItems(result);
          setTaxonomyTitle(return_content?.data?.title);
        });
    } catch (error) {
      console.log(error);
    }
  };

  useEffect(() => {
    getTaxonomy(taxonomyId);
  }, []);

  const updateTaxonomyData = (value, pKey) => {
    if (pKey === "taxonomy_key") {
      checkTaxonomyKey(value);
    }

    setTaxonomyData((prev) => ({
      ...prev,
      [pKey]: value,
    }));
  };

  const checkTaxonomyKey = useCallback(
    debounceFn(async (value) => {
      try {
        await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
          body: new URLSearchParams({
            action: "aae_taxonomy_exist",
            taxonomy_type: value,
            nonce: WCF_ADDONS_ADMIN.nonce,
          }),
        })
          .then((response) => {
            return response.json();
          })
          .then((return_content) => {
            if (return_content?.hasExist) {
              setTKError("Taxonomy Key Already Exist");
            } else {
              setTKError("");
            }
          });
      } catch (error) {
        console.log(error);
      }
    }),
    []
  );

  const updateTaxonomyTitle = (value, pKey) => {
    setTaxonomyTitle(value);
  };

  const updateTaxonomy = async () => {
    if (!tKSpaceError && !tKError) {
      try {
        await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
          body: new URLSearchParams({
            action: "aae_add_or_update_new_taxonomy_builder",
            taxonomy_meta: JSON.stringify(taxonomyData),
            taxonomy_id: taxonomyId,
            taxonomy_title: taxonomyTitle,
            nonce: WCF_ADDONS_ADMIN.nonce,
          }),
        })
          .then((response) => {
            return response.json();
          })
          .then((return_content) => {
            setAllTaxonomy(return_content?.data);
            changeRoute("taxonomy");
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
            <h2 className="text-[18px] font-medium ">{taxonomyTitle}</h2>
          </div>
          <div>
            <Button onClick={updateTaxonomy}>Save Taxonomy</Button>
          </div>
        </div>
      </div>
      <div className="mt-8">
        <Breadcrumb>
          <BreadcrumbList className="text-brand text-xs items-center">
            <BreadcrumbItem>
              <BreadcrumbLink
                onClick={() => changeRoute("taxonomy")}
                className="text-text-2"
              >
                Taxonomy
              </BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />

            <BreadcrumbItem>
              <BreadcrumbPage>Update Taxonomy</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
      </div>
      <div className="mt-11">
        <div>
          <div className="flex flex-col gap-5 pb-11">
            <InputLabelDesc
              uniqId={"wcf-ct-plural-label"}
              label={"Plural Label"}
              placeholder={"Enter Plural Label"}
              value={taxonomyTitle}
              valueChange={updateTaxonomyTitle}
              changeableValueName="taxonomy_title"
            />

            <InputLabelDesc
              uniqId={"wcf-ct-singular-label"}
              label={"Singular Label"}
              placeholder={"Enter Singular Label"}
              value={taxonomyData?.singular_name}
              valueChange={updateTaxonomyData}
              changeableValueName="singular_name"
            />

            <div className="grid w-full max-w-lg items-center gap-2">
              <Label htmlFor={"wcf-ct-post-type-key"}>{"Taxonomy Key"}</Label>
              <Input
                id={"wcf-ct-taxonomy-key"}
                placeholder={"Enter Taxonomy Key"}
                value={taxonomyData?.taxonomy_key}
                disabled={disableTK}
                onChange={(e) => {
                  const inputValue = e.target.value;
                  const sanitizedValue = inputValue.replace(/\s+/g, "");
                  if (inputValue !== sanitizedValue) {
                    setTKSpaceError("Spaces are not allowed.");
                  } else {
                    setTKSpaceError("");
                  }
                  updateTaxonomyData(sanitizedValue, "taxonomy_key");
                }}
              />
              {(tKSpaceError || tKError) && (
                <small className="text-brand">{tKSpaceError || tKError}</small>
              )}
              <small>
                {
                  "Lower case letters, underscores and dashes only, Max 20 characters."
                }
              </small>
            </div>

            <MultiSelectLabelDesc
              uniqId={"wcf-ct-post_types-select"}
              label={"Post Types"}
              placeholder={"Select"}
              showItems={taxonomyItems}
              description={
                "One or many post types that can be classified with this taxonomy."
              }
              selected={taxonomyData?.post_types}
              valueChange={setTaxonomyData}
              changeableValueName="post_types"
            />
          </div>
          <div className="flex flex-col gap-5 pb-11 pt-11 border-t">
            <SwitchLabelDesc
              uniqId={"wcf-ct-public-switch"}
              label={"Public"}
              description={
                "Visible on the frontend and in the admin dashboard."
              }
              value={taxonomyData?.public}
              valueChange={updateTaxonomyData}
              changeableValueName="public"
            />
            <SwitchLabelDesc
              uniqId={"wcf-ct-hierarchical-switch"}
              label={"Hierarchical"}
              description={
                "Hierarchical post types can have descendants (like pages)."
              }
              value={taxonomyData?.hierarchical}
              valueChange={updateTaxonomyData}
              changeableValueName="hierarchical"
            />
            <SwitchLabelDesc
              uniqId={"wcf-ct-advanceConfig-active-switch"}
              label={"Active"}
              description={
                "Active post types are enabled and registered with WordPress."
              }
              value={taxonomyData.active}
              valueChange={updateTaxonomyData}
              changeableValueName="active"
            />
          </div>
          <div className="flex flex-col gap-5 pb-11 pt-11 border-t">
            <SwitchLabelDesc
              uniqId={"wcf-ct-advanceConfig-switch"}
              label={"Advance Configuration"}
              description={"I know what I'm doing, show me all the options."}
              value={taxonomyData.advance}
              valueChange={updateTaxonomyData}
              changeableValueName="advance"
            />
          </div>
        </div>
        {taxonomyData.advance && (
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
                      <AdvanceGeneralTaxonomy
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
                      />
                    </TabsContent>
                    <TabsContent value="labels" className="px-8">
                      <AdvanceLabelsTaxonomy
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
                      />
                    </TabsContent>
                    <TabsContent value="visibility" className="px-8">
                      <AdvanceVisibilityTaxonomy
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
                      />
                    </TabsContent>
                    <TabsContent value="urls" className="px-8">
                      <AdvanceURLsTaxonomy
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
                      />
                    </TabsContent>
                    <TabsContent value="permissions" className="px-8">
                      <AdvancePermissionTaxonomy
                        taxonomyCaps={taxonomyCaps}
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
                      />
                    </TabsContent>
                    <TabsContent value="rest_api" className="px-8">
                      <AdvanceRestApiTaxonomy
                        taxonomyData={taxonomyData}
                        setTaxonomyData={setTaxonomyData}
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

export default EditTaxonomy;
