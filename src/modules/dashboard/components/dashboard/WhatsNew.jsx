import { __ } from "@wordpress/i18n";
import { cn } from "@/lib/utils";
import { RiArrowRightUpLine } from "react-icons/ri";
import { buttonVariants } from "../ui/button";
import { WhatsNewData } from "@/config/data/whatsNewList";
import { API_ENDPOINTS } from "@/config/api";
import { useRemoteData } from "@/hooks/useRemoteData";
import WhatsNewBell from "../../../../../public/images/whats-new-bell.png";

const WhatsNew = () => {
  const { data } = useRemoteData(API_ENDPOINTS.whatsNew, WhatsNewData);

  return (
    <div className="rounded-2xl bg-[linear-gradient(90deg,#FFF0D8_0%,#FAE3FF_100%)] p-7 flex flex-col sm:flex-row items-center gap-7">
      <div className="shrink-0 mx-auto sm:mx-0 bg-white/80 rounded-3xl flex flex-col items-center justify-center gap-5 px-9 py-10 w-[220px]">
        <div className="w-[46px] h-[46px] rounded-full bg-[#FFF2E6] flex items-center justify-center">
          <img src={WhatsNewBell} alt="" className="w-[26px] h-[22px]" />
        </div>
        <div className="flex flex-col items-center gap-1 text-center">
          <p className="font-serif text-[32px] leading-none text-black">
            V {WCF_ADDONS_ADMIN?.version}
          </p>
          <p className="text-sm text-text-secondary">{data.date}</p>
        </div>
      </div>
      <div className="flex flex-col gap-[30px] flex-1 min-w-0 w-full">
        <div className="flex flex-col gap-[22px]">
          <p className="text-lg font-medium text-text">
            {__("What's New", "animation-addons-for-elementor")}
          </p>
          <ul className="flex flex-col gap-[18px] list-none p-0 m-0">
            {data.notes?.map((note, i) => (
              <li
                key={`whats_new-${i}`}
                className="flex items-center gap-1.5 text-sm text-text-secondary"
              >
                <span className="w-1 h-1 rounded-full bg-[#717784] shrink-0" />
                {note}
              </li>
            ))}
          </ul>
        </div>
        <a
          href={data.changelogUrl}
          target="_blank"
          rel="noreferrer"
          className={cn(buttonVariants({ variant: "secondary" }), "w-fit")}
        >
          {__("View Change log", "animation-addons-for-elementor")}
          <RiArrowRightUpLine
            size={16}
            className="rtl:rotate-360 rtl:scale-x-[-1]"
          />
        </a>
      </div>
    </div>
  );
};

export default WhatsNew;
