import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const TutorialDialog = ({ open, setOpen }) => {
  return (
    <Dialog open={open} onOpenChange={(value) => setOpen(value)}>
      <DialogContent className={"max-w-[1000px]"}>
        <DialogHeader>
          <DialogTitle></DialogTitle>
          <DialogDescription>
            <iframe
              width="100%"
              height="100%"
              src="https://www.youtube.com/embed/Zb2kBjp2m4I?si=ngqdMyOSFjIxytFD&amp;controls=0"
              title="YouTube video player"
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerPolicy="strict-origin-when-cross-origin"
              allowFullScreen
              className="rounded-md aspect-video"
            ></iframe>
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
};

export default TutorialDialog;
