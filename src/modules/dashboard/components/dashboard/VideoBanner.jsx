import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { RiPlayFill } from "react-icons/ri";
import VideoDialog from "./dialog/VideoDialog";
import VideoThumbnail from "../../../../../public/images/youtube_thumbnail.jpg";

/**
 * The 40% half of the top row: a click-to-play tile sitting beside the hero.
 *
 * The play badge is kept on top of the thumbnail — the artwork carries no play
 * affordance of its own, so without it the tile reads as a static banner.
 */
const VideoBanner = ({
  thumbnail = VideoThumbnail,
  videoUrl = "https://youtu.be/tRbvgq2gJF4",
  title = __(
    "Create Stunning Animated Website",
    "animation-addons-for-elementor",
  ),
}) => {
  const [open, setOpen] = useState(false);

  return (
    <>
      <div
        role="button"
        tabIndex={0}
        aria-label={title}
        onClick={() => setOpen(true)}
        onKeyDown={(e) => {
          if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            setOpen(true);
          }
        }}
        className="group relative h-full min-h-[220px] cursor-pointer overflow-hidden rounded-[10px]"
      >
        <img
          src={thumbnail}
          className="w-full h-full object-cover rounded-[10px]"
          alt={title}
        />
        <span
          aria-hidden="true"
          className="absolute inset-0 flex items-center justify-center"
        >
          <span className="flex h-14 w-14 items-center justify-center rounded-full bg-brand text-white shadow-pro transition-transform group-hover:scale-110">
            <RiPlayFill size={24} className="ms-0.5 rtl:rotate-180" />
          </span>
        </span>
      </div>

      <VideoDialog
        open={open}
        setOpen={setOpen}
        videoUrl={videoUrl}
        title={title}
      />
    </>
  );
};

export default VideoBanner;
