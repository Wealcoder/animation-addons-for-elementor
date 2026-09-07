import { __ } from "@wordpress/i18n";
import { cn } from "@/lib/utils";
import { RiArrowRightUpLine, RiPlayFill, RiVideoLine } from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { Separator } from "../ui/separator";
import { TutorialList } from "@/config/data/tutorialList";
import { API_ENDPOINTS } from "@/config/api";
import { useRemoteData } from "@/hooks/useRemoteData";
import VideoDialog from "./dialog/VideoDialog";
import { useState } from "react";

const Tutorial = () => {
  const { data: tutorials } = useRemoteData(
    API_ENDPOINTS.tutorials,
    TutorialList,
  );
  const [open, setOpen] = useState(false);
  const [activeVideo, setActiveVideo] = useState(tutorials[0]?.videoUrl);

  const handlePlay = (videoUrl) => {
    setActiveVideo(videoUrl);
    setOpen(true);
  };

  return (
    <div className="border rounded-2xl p-5 shadow-common">
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center">
          <RiVideoLine size={20} className="text-brand" />
          <p className="font-medium">
            {__("Tutorial", "animation-addons-for-elementor")}
          </p>
        </div>
        <div>
          <a
            href={"https://www.youtube.com/@AnimationAddons"}
            target="_blank"
            rel="noreferrer"
            className={cn(
              buttonVariants({ variant: "secondary", size: "sm" }),
              "me-1",
            )}
          >
            {__("View All", "animation-addons-for-elementor")}
            <RiArrowRightUpLine
              size={18}
              className="rtl:rotate-360 rtl:scale-x-[-1]"
            />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div>
        {tutorials?.map((el, i) => (
          <div key={`tutorial_list-${i}`}>
            <div className="flex items-center justify-between gap-4">
              <div className="flex items-center gap-[18px] flex-1 min-w-0">
                <img
                  src={el.thumbnail}
                  alt=""
                  className="w-[85px] h-12 rounded-lg object-cover shrink-0"
                />
                <p className="text-sm font-medium text-text line-clamp-2">
                  <span dir="ltr">{el.title}</span>
                </p>
              </div>
              <div className="flex items-center gap-3 shrink-0">
                <span className="hidden sm:inline text-sm text-text-secondary whitespace-nowrap">
                  {el.duration}
                </span>
                <button
                  type="button"
                  onClick={() => handlePlay(el.videoUrl)}
                  aria-label={__(
                    "Play tutorial",
                    "animation-addons-for-elementor",
                  )}
                  className="w-8 h-8 rounded-full border bg-transparent cursor-pointer flex items-center justify-center text-icon-secondary hover:border-brand hover:text-brand"
                >
                  <RiPlayFill size={14} className="rtl:rotate-180" />
                </button>
              </div>
            </div>
            {i + 1 !== tutorials.length ? (
              <Separator className="my-4 bg-border-secondary" />
            ) : (
              ""
            )}
          </div>
        ))}
      </div>
      <VideoDialog open={open} setOpen={setOpen} videoUrl={activeVideo} />
    </div>
  );
};

export default Tutorial;
