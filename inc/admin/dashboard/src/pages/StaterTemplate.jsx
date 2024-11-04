import TemplateLeftFilter from "@/components/template/left/TemplateLeftFilter";
import TemplateRightContent from "@/components/template/right/TemplateRightContent";
import { ScrollArea } from "@/components/ui/scroll-area";

const StaterTemplate = () => {
  return (
    <div className="flex">
      <div className="w-[275px] border-r border-border h-[calc(100vh-82px)]">
        <TemplateLeftFilter />
      </div>
      <ScrollArea className="h-[calc(100vh-82px)] flex-1 ">
        <TemplateRightContent />
      </ScrollArea>
    </div>
  );
};

export default StaterTemplate;
