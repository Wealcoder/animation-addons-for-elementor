import ProConfirmDialog from "@/components/shared/ProConfirmDialog";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import { Toggle } from "@/components/ui/toggle";
import { useActivate, useTNavigation } from "@/hooks/app.hooks";
import { cn } from "@/lib/utils";
import { Dot, Heart } from "lucide-react";
import { useState } from "react";
import { RiDownloadLine, RiEyeLine, RiVipCrown2Fill } from "react-icons/ri";

const TemplateShow = ({ allTemplate }) => {
  const [open, setOpen] = useState(false);
  const [wishlistData, setWishlistData] = useState(
    WCF_ADDONS_ADMIN.addons_config.wishlist || []
  );
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
            setWishlistData(return_content.data);
            WCF_ADDONS_ADMIN.addons_config.wishlist = return_content.data;
          }
        });
      fetch(
        `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/starter-templates/v1/favourites?tpl_id=${data}`
      );
    } catch (error) {}
  };

  return (
    <>
      {allTemplate?.templates?.length ? (
        <div className="grid md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-x-5 gap-y-8">
          {allTemplate?.templates?.map((template, i) => (
            <div
              key={`all_template-${i}`}
              className="group"
              id={template?.slug}
            >
              <div
                className="rounded-[12px] overflow-hidden border border-[#ededed] bg-no-repeat aspect-[380/330]"
                style={{
                  backgroundImage: `url(${template?.template_preview})`,
                  backgroundSize: "100%",
                }}
              >
                <div className="w-full h-full group-hover:bg-[#0E121B]/40 relative">
                  {template?.is_pro ? (
                    <div className="absolute top-2.5 right-2.5">
                      <Badge variant={"tPro"} className={"ps-2"}>
                        <RiVipCrown2Fill size={14} className="mr-1.5" /> PRO
                      </Badge>
                    </div>
                  ) : (
                    ""
                  )}
                  <div className="w-full h-full hidden group-hover:flex justify-center items-center gap-2">
                    <a
                      href={template?.demo_link}
                      className={cn(
                        buttonVariants({ variant: "general" }),
                        "py-2 ps-3 pe-4"
                      )}
                      target="_blank"
                    >
                      <RiEyeLine size={20} className="mr-2" /> Preview
                    </a>
                    <Button
                      variant="general"
                      className="py-2 ps-3 pe-4"
                      onClick={() =>
                        changeRoute(
                          "required-features",
                          template?.slug,
                          template?.id,
                          template?.is_pro
                        )
                      }
                    >
                      <RiDownloadLine size={20} className="mr-2" /> Import
                    </Button>
                  </div>
                </div>
              </div>
              <div className="mt-4 flex justify-between">
                <div className="ms-1">
                  <h3 className="text-lg">{template?.title}</h3>
                  <div className="flex gap-1.5 items-center mt-1.5">
                    <div className="flex gap-1 items-center">
                      {template?.categories?.slice(0, 2)?.map((el, i) => (
                        <p key={el + i} className="text-label text-sm">
                          {el}
                          {i === 0 ? ", " : ""}
                        </p>
                      ))}
                    </div>

                    <Dot
                      className="w-2 h-2 text-icon-secondary"
                      strokeWidth={2}
                    />
                    <div className="text-label text-sm flex items-center gap-1">
                      <RiDownloadLine />
                      <p>
                        <span>{template?.downloads}</span> Imports
                      </p>
                    </div>
                  </div>
                </div>
                <div className="mt-[3px] pe-1.5">
                  <Toggle
                    aria-label="Toggle bold"
                    pressed={wishlistData.includes(template.id.toString())}
                    onPressedChange={(value) => saveWishlist(template.id)}
                    className={`[&[data-state=on]>svg]:fill-[#FF5733] [&[data-state=on]>svg]:stroke-[#FF5733] items-start px-0 cursor-pointer`}
                  >
                    <Heart size={20} className="text-icon-secondary" />
                  </Toggle>
                </div>
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
