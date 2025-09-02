import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import TemplateSearch from "./TemplateSearch";

const TemplateSearchDialog = ({ metaData, setMetaData, openSearch, setOpenSearch }) => {
  return (
    <Dialog open={openSearch} onOpenChange={setOpenSearch}>

      <DialogContent hideClose className="top-[20%]">
        <DialogHeader className="hidden">
          <DialogTitle></DialogTitle>
          <DialogDescription></DialogDescription>
        </DialogHeader>
        <TemplateSearch metaData={metaData} setMetaData={setMetaData}  setOpenSearch={setOpenSearch} />
      </DialogContent>
    </Dialog>
  );
};

export default TemplateSearchDialog;
