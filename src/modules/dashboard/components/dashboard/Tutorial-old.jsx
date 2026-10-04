import { __ } from "@wordpress/i18n";
import { cn } from "@/lib/utils";
import { RiPlayCircleLine } from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import TutorialDialog from "./dialog/TutorialDialog";
import { useState } from "react";
import TutorialThumb from "../../../../../public/images/tutorial-thumb.png";
import PlayButton from "../../../../../public/images/play-button.png";

const Tutorial = () => {
  const [open, setOpen] = useState(false);
  return (
    <div className="col-span-2 border rounded-2xl p-5 ps-6 flex justify-between items-center gap-6 shadow-common">
      <div className="w-[362px]">
        <h2 className="text-xl font-medium ">
          <span dir="ltr">
            {__("Watch The Beginner's Guide on How to Use Animation Addons.", "animation-addons-for-elementor")}
          </span>
        </h2>
        <p className="text-sm mt-[10px] text-text-secondary">
          <span dir="ltr">
            {__("Get started with ease by watching our step-by-step beginner's tutorial on Elementor.", "animation-addons-for-elementor")}
          </span>
        </p>
        <a
          href={"https://www.youtube.com/@AnimationAddonsforElementor"}
          className={cn(buttonVariants({ variant: "secondary" }), "mt-7")}
          target="_blank"
        >
          <span className="me-1.5 flex">
            <RiPlayCircleLine size={20} />
          </span>
          {__("Watch Tutorials", "animation-addons-for-elementor")}
        </a>
      </div>
      <div className="flex-1">
        <div className="relative">
          <img
            className="w-full h-full object-cover"
            src={TutorialThumb}
            alt={__("thumbnail", "animation-addons-for-elementor")}
          />
          <div
            className="absolute top-[93px] left-0 right-0 mx-auto w-fit cursor-pointer"
            onClick={() => setOpen(true)}
          >
            <img width={50} height={50} src={PlayButton} alt={__("play", "animation-addons-for-elementor")} />
          </div>
        </div>
      </div>
      <TutorialDialog open={open} setOpen={setOpen} />
    </div>
  );
};

export default Tutorial;
