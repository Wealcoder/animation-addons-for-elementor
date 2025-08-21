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
import { Dot } from "lucide-react";
import { Badge } from "../ui/badge";
import { deviceMediaMatch } from "@/lib/utils";
import { useActiveItem, useExtensions, useSetup } from "@/hooks/app.hooks";
import { ExtensionSettingConfig } from "@/config/extensionSettingConfig";
import ExtensionGsapSettings from "../extentions/ExtensionGsapSettings";
import { allSetupExtensionFn } from "@/lib/extensionService";

const ShowWizExtensions = () => {
  const exSettings = ExtensionSettingConfig;
  const { allExtensions } = useExtensions();
  const {
    updateActiveGeneralExtension,
    updateActiveGeneralGroupExtension,
    updateActiveGsapExtension,
    updateActiveGsapGroupExtension,
    updateActiveGsapAllExtension,
  } = useActiveItem();
  const { setupType } = useSetup();

  const [filteredGsapExtensions, setFilteredGsapExtensions] = useState(
    allExtensions.elements["gsap-extensions"]
  );
  const [filteredGeneralExtensions, setFilteredGeneralExtensions] = useState(
    allExtensions.elements["general-extensions"]
  );
  const [openAccordion, setOpenAccordion] = useState([]);

  useEffect(() => {
    if (allExtensions) {
      setFilteredGsapExtensions(allExtensions?.elements["gsap-extensions"]);
      setFilteredGeneralExtensions(
        allExtensions?.elements["general-extensions"]
      );
    }
  }, [allExtensions]);

  useEffect(() => {
    if (allExtensions) {
      const result = allSetupExtensionFn(allExtensions, setupType);
      setFilteredGsapExtensions(result?.gsapResult);
      setFilteredGeneralExtensions(result?.generalResult);
    }
  }, []);

  return (
    <div className="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-background-secondary p-3 rounded-lg">
      <div>
        <div className="bg-background flex justify-between items-center p-5 rounded">
          <h3 className="text-base font-medium">
            {filteredGsapExtensions?.title}
          </h3>
          <div className="flex items-center space-x-2">
            <Switch
              id={`enable-gsap`}
              checked={filteredGsapExtensions?.is_active}
              onCheckedChange={(value) =>
                updateActiveGsapAllExtension({ value })
              }
            />
            <Label htmlFor={`enable-gsap`}>Enable All</Label>
          </div>
        </div>

        <Accordion
          type="multiple"
          value={openAccordion}
          onValueChange={(value) => setOpenAccordion(value)}
          className="w-full mt-2 space-y-1.5"
        >
          {filteredGsapExtensions?.elements &&
            Object.keys(filteredGsapExtensions?.elements)?.map((extension) => (
              <AccordionItem key={extension} value={extension}>
                <div className="p-[2px]">
                  <div className="flex items-center bg-background justify-between gap-3 py-3 px-4">
                    <AccordionTrigger className="rounded">
                      <div className="flex flex-col gap-1">
                        <div className="text-[15px] leading-6 font-medium flex items-center">
                          {filteredGsapExtensions?.elements[extension].title}
                          {filteredGsapExtensions?.elements[extension]
                            ?.is_pro ? (
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
                      </div>
                    </AccordionTrigger>
                    <div className="flex gap-1 items-center">
                      {Object.keys(
                        filteredGsapExtensions?.elements[extension]?.elements
                      )?.length ? (
                        ""
                      ) : (
                        <>
                          <Badge
                            variant="pro"
                            className="px-2.5 py-1.5 h-7 bg-[linear-gradient(180deg,#FFA184_0%,#F2754F_100%)] mr-1"
                          >
                            COMING SOON!
                          </Badge>
                        </>
                      )}

                      <ExtensionGsapSettings
                        extension={filteredGsapExtensions?.elements[extension]}
                      >
                        {
                          exSettings?.find((item) => item.key === extension)
                            ?.component
                        }
                      </ExtensionGsapSettings>

                      <Switch
                        checked={
                          filteredGsapExtensions?.elements[extension]?.is_active
                        }
                        onCheckedChange={(value) => {
                          value
                            ? setOpenAccordion((prev) => [...prev, extension])
                            : setOpenAccordion((prev) =>
                                prev?.filter((el) => el !== extension)
                              );
                          updateActiveGsapGroupExtension({
                            value,
                            slug: extension,
                          });
                        }}
                        disabled={
                          !Object.keys(
                            filteredGsapExtensions?.elements[extension]
                              ?.elements
                          )?.length
                        }
                      />
                    </div>
                  </div>
                </div>
                <AccordionContent>
                  <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1 p-[2px]">
                    {Object.keys(
                      filteredGsapExtensions?.elements[extension]?.elements
                    )?.length ? (
                      <>
                        {Object.keys(
                          filteredGsapExtensions?.elements[extension]?.elements
                        )?.map((content, i) => (
                          <React.Fragment key={`tab_content-${i}`}>
                            <WidgetCard
                              widget={
                                filteredGsapExtensions?.elements[extension]
                                  ?.elements[content]
                              }
                              slug={content}
                              updateActiveItem={updateActiveGsapExtension}
                              isDisable={
                                !filteredGsapExtensions?.elements[extension]
                                  ?.is_active
                              }
                              className="rounded p-5"
                            />
                          </React.Fragment>
                        ))}
                        {Array.from({
                          length:
                            deviceMediaMatch() -
                            (Object.keys(
                              filteredGsapExtensions?.elements[extension]
                                ?.elements
                            )?.length %
                              deviceMediaMatch() ===
                            0
                              ? deviceMediaMatch()
                              : Object.keys(
                                  filteredGsapExtensions?.elements[extension]
                                    ?.elements
                                )?.length % deviceMediaMatch()),
                        }).map((_, index) => (
                          <WidgetCard
                            key={`tab_content_empty-${index}`}
                            className="rounded"
                          />
                        ))}
                      </>
                    ) : (
                      <div className="col-span-3 px-4 py-[15px] bg-background rounded-lg  box-border">
                        <p className="text-center">Coming soon...</p>
                      </div>
                    )}
                  </div>
                </AccordionContent>
              </AccordionItem>
            ))}
        </Accordion>
      </div>

      <div className="mt-1.5">
        <div className="bg-background flex justify-between items-center p-5 rounded">
          <h3 className="text-base font-medium">
            {filteredGeneralExtensions?.title}
          </h3>
          <div className="flex items-center space-x-2">
            <Switch
              id={`enable-general`}
              checked={filteredGeneralExtensions?.is_active}
              onCheckedChange={(value) =>
                updateActiveGeneralGroupExtension({ value })
              }
            />
            <Label htmlFor={`enable-general`}>Enable All</Label>
          </div>
        </div>

        <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1">
          {filteredGeneralExtensions?.elements &&
            Object.keys(filteredGeneralExtensions?.elements)?.map(
              (content, i) => (
                <React.Fragment key={`tab_content-${i}`}>
                  <WidgetCard
                    widget={filteredGeneralExtensions?.elements[content]}
                    slug={content}
                    updateActiveItem={updateActiveGeneralExtension}
                    className="rounded p-5"
                  />
                </React.Fragment>
              )
            )}
          {filteredGeneralExtensions?.elements &&
            Array.from({
              length:
                deviceMediaMatch() -
                (Object.keys(filteredGeneralExtensions?.elements)?.length %
                  deviceMediaMatch() ===
                0
                  ? deviceMediaMatch()
                  : Object.keys(filteredGeneralExtensions?.elements)?.length %
                    deviceMediaMatch()),
            }).map((_, index) => (
              <WidgetCard
                key={`tab_content_empty-${index}`}
                className="rounded"
              />
            ))}
        </div>
      </div>
    </div>
  );
};

export default ShowWizExtensions;
