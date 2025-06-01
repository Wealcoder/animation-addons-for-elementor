import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { useTNavigation } from "@/hooks/app.hooks";
import { useEffect, useState } from "react";

const RequiredFeatures = () => {
  const { setTabKey } = useTNavigation();
  const [currenTemplate, setCurrenTemplate] = useState({});
  const [selectedPlugins, setSelectedPlugins] = useState([]);
  const [selectedTheme, setSelectedTheme] = useState("");
  const [allowAttachment, setAllowAttachment] = useState(true);
  const [loading, setIsLoading] = useState(true);

  const url = new URL(window.location.href);
  const templateid = url.searchParams.get("templateid");
  const changeRoute = (value) => {
    const pageQuery = url.searchParams.get("page");
    const template = url.searchParams.get("template");
    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;
    url.searchParams.set("template", template);
    url.searchParams.set("templateid", templateid);
    url.searchParams.set("tab", value);
    if (selectedPlugins && selectedPlugins?.length) {
      url.searchParams.set("plugins", selectedPlugins.toString());
    }
    if (selectedTheme) {
      url.searchParams.set("theme", selectedTheme);
    }
    url.searchParams.set("attachment", allowAttachment);

    window.history.replaceState({}, "", url);
    setTabKey(value);
  };

  useEffect(() => {
    if (templateid) {
      getTemplateData(templateid);
    }
  }, []);

  const getTemplateData = async (id) => {
    try {
      const url = new URL(
        `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
      );

      if (id) {
        url.searchParams.append("tplid", id);
      }

      await fetch(url.toString())
        .then((response) => response.json())
        .then((data) => {
          if (data?.templates) {
            const result = Object.entries(data.templates).find(
              ([key, value]) => value.id == id
            )?.[1];
            if (
              !(
                result?.dependencies?.plugins?.length &&
                result?.dependencies?.plugins?.length
              )
            ) {
              changeRoute("demo-importing");
              return;
            }
            validateData(result);
          }
        });
    } catch (error) {
      console.log(error);
    }
  };

  const validateData = async (mainContent) => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },

        body: new URLSearchParams({
          action: "aaeaddon_template_dependency_status",
          nonce: WCF_ADDONS_ADMIN.nonce,
          dependencies: JSON.stringify(mainContent?.dependencies),
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          if (return_content.success) {
            const result = return_content?.data?.dependencies;
            setSelectedPlugins((prev) => {
              const requiredSlugs = result?.plugins
                .filter((p) => p.required)
                .map((p) => p.slug);
              return Array.from(new Set([...prev, ...requiredSlugs]));
            });
            mainContent.dependencies = result;
            setCurrenTemplate(mainContent);
          }
        });
    } catch (error) {
      console.log(error);
    } finally {
      setIsLoading(false);
    }
  };
  return (
    <div className="bg-background w-[692px] rounded-2xl p-1.5 shadow-auth-card">
      {loading ? (
        <div className="flex justify-center items-center h-[10vh]">
          <p className="text-lg font-semibold">Loading...</p>
        </div>
      ) : (
        <div className="border border-border-secondary rounded-xl">
          <div className="border-b border-border-secondary p-8 pb-6">
            <div className="mb-7">
              <h3 className="text-2xl font-medium">Required Features</h3>
              <p className="mt-1.5 text-text-secondary">
                Install every plugins, themes and extensions listed below.
              </p>
            </div>
            <div>
              <Accordion
                type="multiple"
                defaultValue={["plugins", "themes"]}
                className="w-full space-y-3"
              >
                {currenTemplate?.dependencies?.plugins?.length ? (
                  <AccordionItem
                    value="plugins"
                    className="border px-4 rounded-xl"
                  >
                    <AccordionTrigger>
                      <div className="flex justify-between items-center gap-4 w-full mr-2.5">
                        <h3 className="text-lg text-medium">
                          Required Plugins
                        </h3>
                        <p className="text-text-secondary">
                          {currenTemplate?.dependencies?.plugins?.length}{" "}
                          Plugins
                        </p>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent className="mt-2 space-y-4">
                      {currenTemplate?.dependencies?.plugins?.map(
                        (plugin, i) => (
                          <div
                            className="flex items-center space-x-2.5"
                            key={plugin.slug + i}
                          >
                            <Checkbox
                              id={`plugin-${plugin.slug}`}
                              checked={selectedPlugins.includes(plugin?.slug)}
                              disabled={plugin?.required}
                              onCheckedChange={(value) =>
                                setSelectedPlugins((prev) =>
                                  value
                                    ? [...prev, plugin?.slug]
                                    : prev.filter((p) => p !== plugin?.slug)
                                )
                              }
                            />
                            <label
                              htmlFor={`plugin-${plugin.slug}`}
                              className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                              {plugin.name}
                            </label>
                            <Badge
                              variant={
                                plugin?.status === "Not Installed"
                                  ? "inProgress"
                                  : "installed"
                              }
                            >
                              {plugin?.status}
                            </Badge>
                          </div>
                        )
                      )}
                    </AccordionContent>
                  </AccordionItem>
                ) : (
                  ""
                )}

                {currenTemplate?.dependencies?.themes?.length ? (
                  <AccordionItem
                    value="themes"
                    className="border px-4 rounded-xl"
                  >
                    <AccordionTrigger>
                      <div className="flex justify-between items-center gap-4 w-full mr-2.5">
                        <h3 className="text-lg text-medium">
                          Recommended Themes
                        </h3>
                        <p className="text-text-secondary">
                          {currenTemplate?.dependencies?.themes?.length} Themes
                        </p>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent className="mt-2 space-y-4">
                      {currenTemplate?.dependencies?.themes?.map((theme, i) => (
                        <div
                          className="flex items-center space-x-2.5"
                          key={theme.slug + i}
                        >
                          <Checkbox
                            id={`theme-${theme.slug}`}
                            checked={selectedTheme === theme?.slug}
                            disabled={
                              selectedTheme && selectedTheme !== theme?.slug
                            }
                            onCheckedChange={(value) =>
                              setSelectedTheme(value ? theme?.slug : "")
                            }
                          />
                          <label
                            htmlFor={`theme-${theme.slug}`}
                            className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                          >
                            {theme.title}
                          </label>
                          <Badge
                            variant={
                              theme?.status === "Not Installed"
                                ? "inProgress"
                                : "installed"
                            }
                          >
                            {theme?.status}
                          </Badge>
                        </div>
                      ))}
                    </AccordionContent>
                  </AccordionItem>
                ) : (
                  ""
                )}
              </Accordion>
            </div>
            <div className="flex items-center space-x-2.5 mt-6">
              <Checkbox
                id={`demo-import-attachment`}
                checked={allowAttachment}
                onCheckedChange={setAllowAttachment}
              />
              <label
                htmlFor={`demo-import-attachment`}
                className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                Import demo with attachments (recommended)
              </label>
            </div>
          </div>
          <div className="px-8 pt-4 pb-6 flex justify-end items-center gap-3">
            <Button
              variant="secondary"
              onClick={() => changeRoute("stater-template")}
            >
              Go Back
            </Button>
            <Button onClick={() => changeRoute("demo-importing")}>
              Continue to next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default RequiredFeatures;
