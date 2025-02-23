import {
  RiFileTextLine,
  RiGift2Line,
  RiHeartLine,
  RiShoppingBasketLine,
  RiVipCrown2Line,
} from "react-icons/ri";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { ScrollArea } from "@/components/ui/scroll-area";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";
import { useEffect, useState } from "react";

const TemplateLeftFilter = ({
  types,
  setTypes,
  license,
  setLicense,
  selectedCategory,
  setSelectedCategory,
  setPageNum,
}) => {
  const [allCategory, setAllCategory] = useState([]);

  useEffect(() => {
    fetch(`${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/st-cat`)
      .then((response) => response.json())
      .then((data) => {
        setAllCategory(data);
      });
  }, []);

  return (
    <div className="px-5 py-6 flex flex-col justify-between gap-5 h-full">
      <div>
        <div className="flex gap-2 items-center pb-5">
          <RiFileTextLine size={20} className="text-icon-secondary" />
          <h3 className="font-medium">Filter Settings</h3>
        </div>
        <ScrollArea className="h-[calc(100vh-180px)]">
          <div>
            <Accordion
              type="multiple"
              defaultValue={["types", "license", "categories"]}
              className="w-full"
            >
              <AccordionItem value="types" className="border-b-0 border-t">
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2">
                  Types
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup
                    type="multiple"
                    className="justify-start flex-wrap gap-2"
                    value={types}
                    onValueChange={(value) => {
                      setTypes(value);
                      setPageNum(1);
                    }}
                  >
                    <ToggleGroupItem
                      value="wishlist"
                      variant="outline"
                      className="ps-2"
                      aria-label="Toggle wish list"
                    >
                      <RiShoppingBasketLine size={18} className="text-icon" /> Wishlist
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
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2">
                  License
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup
                    type="single"
                    className="justify-start flex-wrap gap-2"
                    value={license}
                    onValueChange={(value) => {
                      setLicense(value);
                      setPageNum(1);
                    }}
                  >
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
                <AccordionTrigger className="pt-5 pb-5 data-[state=open]:pb-2">
                  Categories
                </AccordionTrigger>
                <AccordionContent className="pb-5">
                  <ToggleGroup
                    type="multiple"
                    className="justify-start flex-wrap gap-1.5"
                    value={selectedCategory}
                    onValueChange={(value) => {
                      setSelectedCategory(value);
                      setPageNum(1);
                    }}
                  >
                    {allCategory?.map((category, i) => (
                      <ToggleGroupItem
                        key={`${category?.slug}-${i}`}
                        value={category?.id}
                        variant="outline"
                        aria-label="Toggle category"
                      >
                        {category?.name}
                      </ToggleGroupItem>
                    ))}
                  </ToggleGroup>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>
        </ScrollArea>
      </div>
    </div>
  );
};

export default TemplateLeftFilter;
