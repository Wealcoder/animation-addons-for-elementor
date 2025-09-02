import { formatNumber } from "@//lib/utils";
import ProConfirmDialog from "@/components/shared/ProConfirmDialog";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import { Toggle } from "@/components/ui/toggle";
import { useActivate, useTNavigation } from "@/hooks/app.hooks";
import { cn } from "@/lib/utils";
import { useState } from "react";
import {
  TooltipProvider,
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";

const TemplateShow = ({ allTemplate, metaData, setMetaData }) => {
  const [open, setOpen] = useState(false);

  const { setTabKey } = useTNavigation();
  const { activated } = useActivate();

  const changeRoute = (value, slug, id, is_pro) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    url.searchParams.set("template", slug);
    url.searchParams.set("templateid", id);

    if (is_pro) {
      if (activated?.product_status?.item_id === 13) {
        window.history.replaceState({}, "", url);
        setTabKey(value);
      } else {
        setOpen(value);
      }
    } else {
      window.history.replaceState({}, "", url);
      setTabKey(value);
    }
  };

  const saveWishlist = async (data) => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },

        body: new URLSearchParams({
          action: "aaeaddon_wishlist_option",
          wishlist: JSON.stringify(data),
          nonce: WCF_ADDONS_ADMIN.nonce,
          settings: "wcf_save_widgets",
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          if (return_content.success) {
            setMetaData((pre) => ({
              ...pre,
              wishlist: return_content.data,
              pageNum: 1,
            }));
            WCF_ADDONS_ADMIN.addons_config.wishlist = return_content.data;
          }
        });
    } catch (error) {}
  };

  return (
    <>
      {allTemplate?.templates?.length ? (
        <div className="grid md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-x-5 gap-y-8">
          {allTemplate?.templates?.map((template, i) => (
            <div
              key={`all_template-${i}`}
              className="group bg-white rounded-[12px] overflow-hidden"
              id={template?.slug}
            >
              <div
                className="overflow-hidden bg-no-repeat aspect-[348/375]"
                style={{
                  backgroundImage: `url(${template?.template_preview})`,
                  backgroundSize: "100%",
                }}
              >
                <div className="w-full h-full group-hover:bg-[#000000]/70 relative">
                  <div className="absolute top-2.5 right-2.5">
                    <Badge
                      className={
                        "px-2.5 rounded-[4px] bg-[#F6502C] h-6 uppercase text-white border-none text-sm font-bold"
                      }
                    >
                      {template?.is_pro ? "Pro" : "Free"}
                    </Badge>
                  </div>

                  <div className="w-full h-full hidden group-hover:flex flex-col justify-center items-center gap-[15px]">
                    <a
                      href={template?.demo_link}
                      className={cn(
                        buttonVariants(),
                        "h-9 w-[150px] bg-transparent hover:bg-transparent border border-white text-white rounded-full text-base font-medium capitalize gap-1"
                      )}
                      target="_blank"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                        fill="none"
                      >
                        <path
                          d="M10 8C10 6.8954 9.1046 6 8 6C6.8954 6 6 6.8954 6 8C6 9.1046 6.8954 10 8 10C9.1046 10 10 9.1046 10 8Z"
                          stroke="white"
                        />
                        <path
                          d="M7.99989 3.33203C10.7878 3.33203 13.1759 6.0075 14.1714 7.30683C14.4875 7.71956 14.4875 8.27783 14.1714 8.69056C13.1759 9.9899 10.7878 12.6654 7.99989 12.6654C5.21204 12.6654 2.82387 9.9899 1.82844 8.69056C1.51226 8.27783 1.51227 7.71956 1.82844 7.30683C2.82387 6.0075 5.21204 3.33203 7.99989 3.33203Z"
                          stroke="white"
                          stroke-linejoin="round"
                        />
                      </svg>{" "}
                      Preview
                    </a>
                    <Button
                      className="h-9 w-[150px] bg-[#F6502C] hover:bg-[#F6502C] border border-[#F6502C]  text-white rounded-full text-base font-medium capitalize gap-1"
                      onClick={() =>
                        changeRoute(
                          "required-features",
                          template?.slug,
                          template?.id,
                          template?.is_pro
                        )
                      }
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                        fill="none"
                      >
                        <path
                          d="M14 11.332V12.6654C14 13.4018 13.4031 13.9987 12.6667 13.9987H3.33333C2.59696 13.9987 2 13.4018 2 12.6654V11.332"
                          stroke="white"
                          stroke-linecap="square"
                        />
                        <path
                          d="M5 7.66667L8.00003 10.6667L11 7.66667M8.00003 10V2"
                          stroke="white"
                          stroke-linecap="square"
                        />
                      </svg>{" "}
                      Import
                    </Button>
                    <Toggle
                      aria-label="Wishlist"
                      pressed={metaData?.wishlist?.includes(
                        template.id.toString()
                      )}
                      onPressedChange={(value) => saveWishlist(template.id)}
                      className={`data-[state=on]:bg-[#F6502C] data-[state=on]:border-[#F6502C]  cursor-pointer h-9 w-[150px] bg-transparent hover:bg-transparent border border-white text-white rounded-full text-base font-medium capitalize gap-1`}
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="17"
                        height="16"
                        viewBox="0 0 17 16"
                        fill="none"
                      >
                        <path
                          d="M7.46695 13.6555C5.63312 12.2904 2 9.16953 2 6.36108C2 4.50481 3.36842 3 5.25 3C6.225 3 7.2 3.32353 8.5 4.61764C9.8 3.32353 10.775 3 11.75 3C13.6316 3 15 4.50481 15 6.36108C15 9.16953 11.3669 12.2904 9.53304 13.6555C8.91593 14.1148 8.08406 14.1148 7.46695 13.6555Z"
                          stroke="white"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                        />
                      </svg>{" "}
                      Wishlist
                    </Toggle>
                  </div>
                </div>
              </div>
              <div className="p-[25px]">
                <div className="flex justify-between items-center">
                  <h3 className="text-base font-medium text-[#202020] capitalize">
                    {template?.title}
                  </h3>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger className="rounded-[4px]">
                        <Badge className="px-2 h-5 gap-1 bg-[#EAEAFF] text-[#5453FD] rounded-[4px] border-none">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            viewBox="0 0 12 12"
                            fill="none"
                          >
                            <path
                              d="M4 8.5H8"
                              stroke="#5453FD"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                            />
                            <path
                              d="M4 6.5H6"
                              stroke="#5453FD"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                            />
                            <path
                              d="M6.5 1.25V1.5C6.5 2.91422 6.5 3.62132 6.93935 4.06066C7.3787 4.5 8.0858 4.5 9.5 4.5H9.75M10 5.32845V7C10 8.8856 10 9.82845 9.4142 10.4142C8.82845 11 7.8856 11 6 11C4.11438 11 3.17157 11 2.58578 10.4142C2 9.82845 2 8.8856 2 7V4.72792C2 3.10541 2 2.29416 2.44303 1.74467C2.53254 1.63366 2.63365 1.53254 2.74466 1.44304C3.29415 1 4.10541 1 5.7279 1C6.0807 1 6.25705 1 6.4186 1.05701C6.4522 1.06886 6.4851 1.0825 6.51725 1.09788C6.6718 1.17178 6.7965 1.2965 7.04595 1.54594L9.4142 3.91422C9.70325 4.20325 9.84775 4.34776 9.9239 4.53153C10 4.7153 10 4.91968 10 5.32845Z"
                              stroke="#5453FD"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                            />
                          </svg>{" "}
                          {template?.page_count ?? 0}
                        </Badge>
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>Pages</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <div className="flex justify-between gap-1.5 items-center mt-2">
                  <p className="text-[#636363] text-sm truncate">
                    {template?.categories[0]}
                  </p>

                  <Badge className="px-2 h-5 gap-1 bg-[#EFFBEC] text-[#34A311] rounded-[4px] border-none">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="12"
                      height="12"
                      viewBox="0 0 12 12"
                      fill="none"
                    >
                      <path
                        d="M1.5 8.5C1.5 8.965 1.5 9.1975 1.55111 9.38825C1.68981 9.90585 2.09413 10.3102 2.61177 10.4489C2.80252 10.5 3.03501 10.5 3.5 10.5H8.5C8.965 10.5 9.1975 10.5 9.38825 10.4489C9.90585 10.3102 10.3102 9.90585 10.4489 9.38825C10.5 9.1975 10.5 8.965 10.5 8.5"
                        stroke="#34A311"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                      <path
                        d="M8.25002 5.75002C8.25002 5.75002 6.59292 8.00002 5.99997 8.00002C5.40707 8.00002 3.75 5.75002 3.75 5.75002M5.99997 7.50002V1.5"
                        stroke="#34A311"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>{" "}
                    {formatNumber(template?.downloads) ?? 0}
                  </Badge>
                </div>
                {/* <div className="mt-[3px] pe-1.5">
                  <Toggle
                    aria-label="Toggle bold"
                    pressed={wishlistData.includes(template.id.toString())}
                    onPressedChange={(value) => saveWishlist(template.id)}
                    className={`[&[data-state=on]>svg]:fill-[#FF5733] [&[data-state=on]>svg]:stroke-[#FF5733] items-start px-0 cursor-pointer`}
                  >
                    <Heart size={20} className="text-icon-secondary" />
                  </Toggle>
                </div> */}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="flex justify-center items-center h-[10vh]">
          <p className="text-lg font-semibold">No Item Found</p>
        </div>
      )}
      <ProConfirmDialog open={open} setOpen={setOpen} />
    </>
  );
};

export default TemplateShow;
