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
          <DialogTitle className="hidden"></DialogTitle>
          <DialogDescription>
            {/* <iframe
              width="100%"
              height="100%"
              src="https://www.youtube.com/embed/Zb2kBjp2m4I?si=ngqdMyOSFjIxytFD&amp;controls=0"
              title="YouTube video player"
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerPolicy="strict-origin-when-cross-origin"
              allowFullScreen
              className="rounded-md aspect-video"
            ></iframe> */}
            <div style={{ padding: "56.25% 0 0 0", position: "relative" }}>
              <iframe
                src="https://player.vimeo.com/video/1065400040?h=7d54677ea1&badge=0&autopause=0&player_id=0&app_id=58479/embed"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
                frameborder="0"
                className="absolute top-0 left-0 w-full h-full"
              ></iframe>
            </div>
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
};

export default TutorialDialog;
