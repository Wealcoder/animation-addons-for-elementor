import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { useTNavigation } from "@/hooks/app.hooks";

const RequiredFeatures = () => {
  const { setTabKey } = useTNavigation();

  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    setTabKey(value);
  };
  return (
    <div className="bg-background w-[692px] rounded-2xl p-1.5 shadow-auth-card">
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
              <AccordionItem value="plugins" className="border px-4 rounded-xl">
                <AccordionTrigger>
                  <div className="flex justify-between items-center gap-4 w-full mr-2.5">
                    <h3 className="text-lg text-medium">Required Plugins</h3>
                    <p className="text-text-secondary">4 Plugins</p>
                  </div>
                </AccordionTrigger>
                <AccordionContent className="mt-2 space-y-4">
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Elementor-Builder" />
                    <label
                      htmlFor="Elementor-Builder"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Elementor Builder
                    </label>
                    <Badge variant={"installed"}>Installed</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Animation-Addons-for-Elementor" />
                    <label
                      htmlFor="Animation-Addons-for-Elementor"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Animation Addons for Elementor
                    </label>
                    <Badge variant={"installed"}>Installed</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Jet-Form-Builder-for-Elementor" />
                    <label
                      htmlFor="Jet-Form-Builder-for-Elementor"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Jet Form Builder for Elementor
                    </label>
                    <Badge variant={"inProgress"}>In Progress</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="WooCommerce–WordPress-plugin" />
                    <label
                      htmlFor="WooCommerce–WordPress-plugin"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      WooCommerce – WordPress plugin
                    </label>
                  </div>
                </AccordionContent>
              </AccordionItem>
              <AccordionItem value="themes" className="border px-4 rounded-xl">
                <AccordionTrigger>
                  <div className="flex justify-between items-center gap-4 w-full mr-2.5">
                    <h3 className="text-lg text-medium">Required Theme</h3>
                    <p className="text-text-secondary">2 Themes</p>
                  </div>
                </AccordionTrigger>
                <AccordionContent className="mt-2">
                  <RadioGroup defaultValue="Hello Animation" className="gap-4">
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem
                        value="Hello Animation"
                        id="Hello-Animation"
                      />
                      <Label
                        htmlFor="Hello-Animation"
                        className="text-base font-normal"
                      >
                        Hello Animation{" "}
                        <span className="text-text-secondary">
                          (Default Theme)
                        </span>
                      </Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem
                        value="Hello Elementor"
                        id="Hello-Elementor"
                      />
                      <Label
                        htmlFor="Hello-Elementor"
                        className="text-base font-normal"
                      >
                        Hello Elementor{" "}
                        <span className="text-text-secondary">
                          (Commercial Theme)
                        </span>
                      </Label>
                    </div>
                  </RadioGroup>
                </AccordionContent>
              </AccordionItem>
              <AccordionItem
                value="extensions"
                className="border px-4 rounded-xl"
              >
                <AccordionTrigger>
                  <div className="flex justify-between items-center gap-4 w-full mr-2.5">
                    <h3 className="text-lg text-medium">Required Extensions</h3>
                    <p className="text-text-secondary">5 Extensions</p>
                  </div>
                </AccordionTrigger>
                <AccordionContent className="mt-2 space-y-4">
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Elementor-Builder2" />
                    <label
                      htmlFor="Elementor-Builder2"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Elementor Builder
                    </label>
                    <Badge variant={"installed"}>Installed</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Animation-Addons-for-Elementor2" />
                    <label
                      htmlFor="Animation-Addons-for-Elementor2"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Animation Addons for Elementor
                    </label>
                    <Badge variant={"installed"}>Installed</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="Jet-Form-Builder-for-Elementor2" />
                    <label
                      htmlFor="Jet-Form-Builder-for-Elementor2"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Jet Form Builder for Elementor
                    </label>
                    <Badge variant={"inProgress"}>In Progress</Badge>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="WooCommerce–WordPress-plugin2" />
                    <label
                      htmlFor="WooCommerce–WordPress-plugin2"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      WooCommerce – WordPress plugin
                    </label>
                  </div>
                  <div className="flex items-center space-x-2.5">
                    <Checkbox id="WP-Rocket–WordPress-plugin2" />
                    <label
                      htmlFor="WP Rocket–WordPress-plugin2"
                      className="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      WP Rocket – WordPress plugin
                    </label>
                  </div>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
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
    </div>
  );
};

export default RequiredFeatures;
