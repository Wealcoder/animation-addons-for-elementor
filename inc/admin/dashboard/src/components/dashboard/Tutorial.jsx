import { cn } from "@/lib/utils";
import { RiPlayCircleLine } from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import TutorialDialog from "./dialog/TutorialDialog";
import { useState } from "react";
import TutorialThumb from "../../../public/images/tutorial-thumb.png";
import PlayButton from "../../../public/images/play-button.png";

const Tutorial = () => {
  const [open, setOpen] = useState(false);
  return (
    <div className="col-span-2 border border-solid border-border rounded-2xl p-5 ps-6 flex justify-between items-center gap-6 shadow-common">
      <div className="w-[362px]">
        <h2 className="text-xl font-medium ">{`Watch The Beginner's Guide on how to use Animation Addon.`}</h2>
        <p className="text-sm mt-[10px] text-text-secondary">{`Get started with ease by watching our step-by-step beginner's tutorial on the Elementor.`}</p>
        <a
          href={"https://www.youtube.com/"}
          className={cn(buttonVariants({ variant: "secondary" }), "mt-7")}
          target="_blank"
        >
          <span className="me-1.5">
            <RiPlayCircleLine size={20} />
          </span>
          Watch Tutorials
        </a>
      </div>
      <div className="flex-1">
        <div className="relative">
          <img
            className="w-full h-full object-cover"
            src={TutorialThumb}
            alt="thumbnail"
          />
          <div
            className="absolute top-[93px] left-0 right-0 mx-auto w-fit cursor-pointer"
            onClick={() => setOpen(true)}
          >
            <img width={50} height={50} src={PlayButton} alt="play" />
          </div>
        </div>
      </div>
      <TutorialDialog open={open} setOpen={setOpen} />
    </div>
  );
};

export default Tutorial;
