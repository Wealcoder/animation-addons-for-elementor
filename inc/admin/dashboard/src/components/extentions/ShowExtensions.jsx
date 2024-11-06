import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "../ui/button";
import WidgetCard from "../shared/WidgetCard";
import React, { useEffect, useState } from "react";
import { Switch } from "../ui/switch";
import { Label } from "../ui/label";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion2";
import {
  ALLGeneralExtensionList,
  AllGSAPExtensionList,
} from "@/config/data/allExtensionList";
import { RiSettings2Line } from "react-icons/ri";
import { generateGeneralExtension, generateGsapExtension } from "@/lib/utils";
import { Dot } from "lucide-react";
import { Badge } from "../ui/badge";

const ShowExtensions = ({ filterKey, tabParam, pluginIdParam }) => {
  const [filteredGsapExtensions, setFilteredGsapExtensions] = useState([]);
  const [filteredGeneralExtensions, setFilteredGeneralExtensions] = useState(
    []
  );
  const [openAccordion, setOpenAccordion] = useState("");
  const [tabValue, setTabValue] = useState("gsap");

  const fetchWidgets = WCF_ADDONS_ADMIN?.addons_config?.extensions;

  console.log(fetchWidgets);

  useEffect(() => {
    const allGsapExtensions = AllGSAPExtensionList;
    const allGeneralExtensions = ALLGeneralExtensionList;
    if (allGsapExtensions && filterKey) {
      const result = generateGsapExtension(allGsapExtensions, filterKey);
      setFilteredGsapExtensions(result);
    }
    if (allGeneralExtensions && filterKey) {
      const result = generateGeneralExtension(allGeneralExtensions, filterKey);
      setFilteredGeneralExtensions(result);
    }
  }, [filterKey]);

  useEffect(() => {
    if (tabParam) {
      setTabValue(tabParam);
    }
  }, [tabParam]);

  useEffect(() => {
    if (pluginIdParam) {
      setOpenAccordion(pluginIdParam);
    }
  }, [pluginIdParam]);

  return (
    <Tabs value={tabValue} onValueChange={setTabValue}>
      <div className="flex justify-between items-center">
        <TabsList className="gap-1 h-11">
          <TabsTrigger value={"gsap"}>GSAP Extensions</TabsTrigger>
          <TabsTrigger value={"general"}>General Extensions</TabsTrigger>
        </TabsList>
        <div className="flex gap-2.5 items-center justify-end">
          <Button variant="secondary">Reset</Button>
          <Button>Save Settings</Button>
        </div>
      </div>
      <TabsContent
        value={`gsap`}
        className="bg-background-secondary p-2.5 rounded-lg"
      >
        <div>
          <div className="bg-background flex justify-between items-center p-5 rounded">
            <h3 className="text-base font-medium">GSAP Extensions</h3>
            <div className="flex items-center space-x-2">
              <Switch id={`enable-gsap`} />
              <Label htmlFor={`enable-gsap`}>Enable All</Label>
            </div>
          </div>

          <Accordion
            type="multiple"
            value={openAccordion}
            onValueChange={(value) => setOpenAccordion(value)}
            className="w-full mt-2 space-y-1.5"
          >
            {filteredGsapExtensions?.map((extension) => (
              <AccordionItem key={extension.id} value={extension.id}>
                <div className="p-[2px]">
                  <div className="flex items-center bg-background justify-between gap-3 py-3 px-4">
                    <AccordionTrigger className="rounded">
                      <div className="flex flex-col gap-1">
                        <div className="text-[15px] leading-6 font-medium flex items-center">
                          {extension.pluginName}
                          {extension?.isPro ? (
                            <>
                              <Dot
                                className="w-3.5 h-3.5 text-icon-secondary"
                                strokeWidth={2}
                              />
                              <Badge variant="pro">PRO</Badge>
                            </>
                          ) : (
                            ""
                          )}
                        </div>
                        <a
                          href={extension?.docLink}
                          className="text-sm text-text-secondary"
                        >
                          Documentation
                        </a>
                      </div>
                    </AccordionTrigger>
                    <div className="flex gap-4 items-center">
                      <Button variant="link" className="text-icon group">
                        <RiSettings2Line
                          className="me-1.5 text-icon group-hover:text-brand"
                          size={20}
                        />
                        Settings
                      </Button>
                      <Switch
                        onCheckedChange={(value) =>
                          value
                            ? setOpenAccordion((prev) => [
                                ...prev,
                                extension.id,
                              ])
                            : setOpenAccordion((prev) =>
                                prev?.filter((el) => el !== extension.id)
                              )
                        }
                      />
                    </div>
                  </div>
                </div>
                <AccordionContent>
                  <div className="grid grid-cols-3 gap-1 mt-1 p-[2px]">
                    {extension?.extensions?.map((content, i) => (
                      <React.Fragment key={`tab_content-${i}`}>
                        <WidgetCard widget={content} className="rounded p-5" />
                      </React.Fragment>
                    ))}
                    {Array.from({
                      length:
                        3 -
                        (extension?.extensions?.length % 3 === 0
                          ? 3
                          : extension?.extensions?.length % 3),
                    }).map((_, index) => (
                      <WidgetCard
                        key={`tab_content_empty-${index}`}
                        className="rounded"
                      />
                    ))}
                  </div>
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        </div>
      </TabsContent>
      <TabsContent
        value={`general`}
        className="bg-background-secondary p-3 rounded-lg"
      >
        <div>
          <div className="bg-background flex justify-between items-center p-5 rounded">
            <h3 className="text-base font-medium">General Extensions</h3>
            <div className="flex items-center space-x-2">
              <Switch id={`enable-general`} />
              <Label htmlFor={`enable-general`}>Enable All</Label>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-1 mt-1">
            {filteredGeneralExtensions?.map((content, i) => (
              <React.Fragment key={`tab_content-${i}`}>
                <WidgetCard widget={content} className="rounded p-5" />
              </React.Fragment>
            ))}
            {Array.from({
              length:
                3 -
                (filteredGeneralExtensions?.length % 3 === 0
                  ? 3
                  : filteredGeneralExtensions?.length % 3),
            }).map((_, index) => (
              <WidgetCard
                key={`tab_content_empty-${index}`}
                className="rounded"
              />
            ))}
          </div>
        </div>
      </TabsContent>
    </Tabs>
  );
};

export default ShowExtensions;
