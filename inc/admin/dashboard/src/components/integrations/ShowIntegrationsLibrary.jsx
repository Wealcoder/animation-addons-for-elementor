import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion2";
import { Switch } from "@/components/ui/switch";
import { Dot } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { useState } from "react";
import WidgetCard from "../shared/WidgetCard";
import { deviceMediaMatch } from "@/lib/utils";

const ShowIntegrationsLibrary = () => {
  const allLibrary = WCF_ADDONS_ADMIN.addons_config.integrations?.library;

  const [openAccordion, setOpenAccordion] = useState([]);

  return (
    <div className="bg-background-secondary p-2.5 rounded-lg">
      <div>
        <div className="bg-background flex items-center p-5 rounded">
          <h3 className="text-base font-medium">{allLibrary?.title}</h3>
        </div>

        <Accordion
          type="multiple"
          value={openAccordion}
          onValueChange={(value) => setOpenAccordion(value)}
          className="w-full mt-2 space-y-1.5"
        >
          {Object.keys(allLibrary?.elements)?.map((extension) => (
            <AccordionItem key={extension} value={extension}>
              <div className="p-[2px]">
                <div className="flex items-center bg-background justify-between gap-3 py-3 px-4">
                  <AccordionTrigger className="rounded cursor-pointer w-full">
                    <div className="flex flex-col gap-1">
                      <div className="text-[15px] leading-6 font-medium flex items-center">
                        {allLibrary?.elements[extension].title}
                        {allLibrary?.elements[extension]?.is_pro ? (
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
                    {Object.keys(allLibrary?.elements[extension]?.elements)
                      ?.length ? (
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

                    <Switch
                      checked={allLibrary?.elements[extension]?.is_active}
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
                        !Object.keys(allLibrary?.elements[extension]?.elements)
                          ?.length
                      }
                    />
                  </div>
                </div>
              </div>
              <AccordionContent>
                <div className="grid grid-cols-2 xl:grid-cols-3 gap-1 mt-1 p-[2px]">
                  {Object.keys(allLibrary?.elements[extension]?.elements)
                    ?.length ? (
                    <>
                      {Object.keys(
                        allLibrary?.elements[extension]?.elements
                      )?.map((content, i) => (
                        <React.Fragment key={`tab_content-${i}`}>
                          <WidgetCard
                            widget={
                              allLibrary?.elements[extension]?.elements[content]
                            }
                            slug={content}
                            updateActiveItem={() => "hi"}
                            className="rounded p-5"
                            preview={false}
                          />
                        </React.Fragment>
                      ))}
                      {Array.from({
                        length:
                          deviceMediaMatch() -
                          (Object.keys(
                            allLibrary?.elements[extension]?.elements
                          )?.length %
                            deviceMediaMatch() ===
                          0
                            ? deviceMediaMatch()
                            : Object.keys(
                                allLibrary?.elements[extension]?.elements
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
    </div>
  );
};

export default ShowIntegrationsLibrary;
