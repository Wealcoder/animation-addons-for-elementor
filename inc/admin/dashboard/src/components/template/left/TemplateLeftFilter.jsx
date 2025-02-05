import {
  RiFileTextLine,
  RiGift2Line,
  RiHeartLine,
  RiVipCrown2Line,
} from "react-icons/ri";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { AllTemplateCategoryList } from "@/config/data/allTemplateCategoryList";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";
import TemplateProBg from "../../../../public/images/template-pro-bg.png";
import ProIcon from "../../../../public/images/pro-icon-1.png";

const TemplateLeftFilter = () => {
  const allCategory = AllTemplateCategoryList;
  return (
    <div className="px-5 py-6 flex flex-col justify-between gap-5 h-full">
      <div>
        <div className="flex gap-2 items-center pb-5">
          <RiFileTextLine size={20} className="text-icon-secondary" />
          <h3 className="font-medium">Filter Settings</h3>
        </div>
        <ScrollArea className="h-[calc(100vh-410px)]">
          <div>
            <Accordion
              type="multiple"
              defaultValue={["types", "license", "categories"]}
              className="w-full"
            >
              <AccordionItem value="types" className="border-b-0 border-t">
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2 bg-transparent">
                  Types
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup type="single" className="justify-start gap-2">
                    <ToggleGroupItem
                      value="all"
                      variant="outline"
                      aria-label="Toggle all"
                    >
                      All
                    </ToggleGroupItem>
                    <ToggleGroupItem
                      value="favorites"
                      variant="outline"
                      className="ps-2"
                      aria-label="Toggle favorites"
                    >
                      <RiHeartLine size={18} className="text-icon" /> Favorites
                    </ToggleGroupItem>
                  </ToggleGroup>
                </AccordionContent>
              </AccordionItem>
              <AccordionItem value="license" className="border-b-0 border-t">
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2 bg-transparent">
                  License
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup type="single" className="justify-start gap-2">
                    <ToggleGroupItem
                      value="pro"
                      variant="outline"
                      className="ps-2"
                      aria-label="Toggle pro"
                    >
                      <RiVipCrown2Line size={18} className="text-icon" /> Pro
                    </ToggleGroupItem>
                    <ToggleGroupItem
                      value="free"
                      variant="outline"
                      className="ps-2"
                      aria-label="Toggle free"
                    >
                      <RiGift2Line size={18} className="text-icon" /> Free
                    </ToggleGroupItem>
                  </ToggleGroup>
                </AccordionContent>
              </AccordionItem>
              <AccordionItem value="categories" className="border-b-0 border-t">
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2 bg-transparent">
                  Categories
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup
                    type="multiple"
                    className="justify-start flex-wrap gap-1.5"
                  >
                    {allCategory?.map((category, i) => (
                      <ToggleGroupItem
                        key={`${category.slug}-${i}`}
                        value={category.slug}
                        variant="outline"
                        aria-label="Toggle category"
                      >
                        {category.name}
                      </ToggleGroupItem>
                    ))}
                  </ToggleGroup>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>
        </ScrollArea>
      </div>
      <div
        className="bg-cover rounded-[10px]"
        style={{ backgroundImage: `url(${TemplateProBg})` }}
      >
        <div>
          <img src={ProIcon} alt="Pro icon" className="w-[105px] h-[106px]" />
        </div>
        <div className="-mt-[25px] p-4 pt-0">
          <h3 className="text-lg font-medium">Get Pro Version</h3>
          <p className="text-sm text-text-secondary mt-2">
            Enhance functionality therefor create a greatly premium user.
          </p>
          <Button variant="pro" className="w-full mt-4">
            <span className="me-2">
              <RiVipCrown2Line size={20} />
            </span>{" "}
            Get Pro Version
          </Button>
        </div>
      </div>
    </div>
  );
};

export default TemplateLeftFilter;
