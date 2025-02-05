import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Toggle } from "@/components/ui/toggle";
import { AllTemplateList } from "@/config/data/allTemplateList";
import { Dot, Heart } from "lucide-react";
import { RiDownloadLine, RiEyeLine, RiVipCrown2Fill } from "react-icons/ri";

const TemplateShow = () => {
  const allTemplate = AllTemplateList;
  return (
    <div className="grid grid-cols-4 gap-x-[23px] gap-y-8">
      {allTemplate?.map((template, i) => (
        <div key={`all_template-${i}`} className="group" id={template.slug}>
          <div
            className="rounded-[12px] overflow-hidden shadow-template-card bg-cover h-[330px]"
            style={{ backgroundImage: `url(${template.image})` }}
          >
            <div className="w-full h-full group-hover:bg-[#0E121B]/40 relative">
              {template.isPro ? (
                <div className="absolute top-2.5 right-2.5">
                  <Badge variant={"tPro"} className={"ps-2"}>
                    <RiVipCrown2Fill size={14} className="mr-1.5" /> PRO
                  </Badge>
                </div>
              ) : (
                ""
              )}
              <div className="w-full h-full hidden group-hover:flex justify-center items-center gap-2">
                <Button variant="general" className="py-2 ps-3 pe-4">
                  <RiEyeLine size={20} className="mr-2" /> Preview
                </Button>
                <Button variant="general" className="py-2 ps-3 pe-4">
                  <RiDownloadLine size={20} className="mr-2" /> Insert
                </Button>
              </div>
            </div>
          </div>
          <div className="mt-4 flex justify-between">
            <div className="ms-1">
              <h3 className="text-lg">{template.title}</h3>
              <div className="flex gap-1.5 items-center mt-1.5">
                <p className="text-label text-sm">{template.category}</p>
                <Dot className="w-2 h-2 text-icon-secondary" strokeWidth={2} />
                <div className="text-label text-sm flex items-center gap-1">
                  <RiDownloadLine />
                  <p>
                    <span>{template.downloaded}</span> Inserts
                  </p>
                </div>
              </div>
            </div>
            <div className="mt-[3px] pe-1.5">
              <Toggle
                aria-label="Toggle bold"
                defaultPressed={template.isFeatured}
                className={`[&[data-state=on]>svg]:fill-[#FF5733] [&[data-state=on]>svg]:stroke-[#FF5733] items-start px-0`}
              >
                <Heart size={20} className="text-icon-secondary" />
              </Toggle>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default TemplateShow;
