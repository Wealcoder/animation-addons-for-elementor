import { __ } from "@wordpress/i18n";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

/**
 * Turns any YouTube watch / youtu.be / embed URL into an embeddable one.
 * Anything it cannot parse is returned untouched, so a hand-written embed
 * URL still works.
 */
function toYoutubeEmbedUrl(url, { autoplay = false } = {}) {
  if (!url) return url;

  try {
    const parsed = new URL(url);
    let embedUrl;

    if (parsed.pathname.startsWith("/embed/")) {
      embedUrl = new URL(url);
    } else {
      let videoId = null;
      if (parsed.hostname === "youtu.be") {
        videoId = parsed.pathname.slice(1);
      } else if (parsed.pathname === "/watch") {
        videoId = parsed.searchParams.get("v");
      }

      if (!videoId) return url;
      embedUrl = new URL(`https://www.youtube.com/embed/${videoId}`);
    }

    if (autoplay) embedUrl.searchParams.set("autoplay", "1");

    return embedUrl.toString();
  } catch {
    return url;
  }
}

/**
 * The same dialog the old TutorialDialog rendered, except the video is a prop
 * instead of a hardcoded id — the tutorial list and the video banner each play
 * a different clip through it.
 *
 * The iframe is only mounted while `open`, so closing the dialog stops
 * playback instead of leaving audio running behind it.
 */
const VideoDialog = ({
  open,
  setOpen,
  videoUrl = "https://youtu.be/tRbvgq2gJF4",
  title = __("Video player", "animation-addons-for-elementor"),
}) => {
  const embedUrl = toYoutubeEmbedUrl(videoUrl, { autoplay: true });

  return (
    <Dialog open={open} onOpenChange={(value) => setOpen(value)}>
      <DialogContent className={"max-w-[1000px]"} hideClose>
        <DialogHeader>
          <DialogTitle className="hidden"></DialogTitle>
          <DialogDescription>
            {open && (
              <iframe
                key={embedUrl}
                width="100%"
                height="100%"
                src={embedUrl}
                title={title}
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerPolicy="strict-origin-when-cross-origin"
                allowFullScreen
                className="rounded-md aspect-video"
              ></iframe>
            )}
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
};

export default VideoDialog;
