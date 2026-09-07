import ProConfirmDialog from "C/components/shared/ProConfirmDialog";
import { Badge } from "C/components/ui/badge";
import { Button, buttonVariants } from "C/components/ui/button";
import { Toggle } from "C/components/ui/toggle";
import { useActivate, useTNavigation } from "C/hooks/app.hooks";
import { cn } from "C/lib/utils";
import { Dot, Heart } from "lucide-react";
import { useState } from "react";
import { RiDownloadLine, RiEyeLine, RiVipCrown2Fill } from "react-icons/ri";
import {
  TooltipProvider,
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "C/components/ui/tooltip";
import V4ImportDialog from "C/components/shared/V4ImportDialog";
import {
  ATOMIC_IMPORT_AVAILABLE,
  PAGE_MODE_KEEP,
  fetchAtomicImportStatus,
  isV4Template,
} from "C/lib/atomicImport";

const TemplateShow = ({ allTemplate }) => {
  const [open, setOpen] = useState(false);
  const [wishlistData, setWishlistData] = useState(
    WCF_ADDONS_ADMIN.addons_config.wishlist || []
  );
  const { setTabKey } = useTNavigation();
  const { activated } = useActivate();

  // The V4 dialog holds the navigation it interrupted until the user answers,
  // and the mode they pick there rides the URL as `v4mode` -- through
  // Required Features and Demo Importing -- into the importer's
  // `aae_page_mode`.
  const [v4Pending, setV4Pending] = useState(null);
  const [v4Mode, setV4Mode] = useState(PAGE_MODE_KEEP);

  const navigate = (value, slug, id, mode = "") => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    url.searchParams.set("template", slug);
    url.searchParams.set("templateid", id);
    if (mode) url.searchParams.set("v4mode", mode);

    window.history.replaceState({}, "", url);
    setTabKey(value);
  };

  const changeRoute = async (value, template) => {
    const { slug, id, is_pro } = template || {};

    // The licence gate comes first, exactly as before.
    if (is_pro && activated?.product_status?.item_id !== 13) {
      setOpen(value);
      return;
    }

    // A V4 page landing on a site that already holds V4 content: ask how its
    // design should meet what is there. Asked fresh, never from the payload
    // -- see lib/atomicImport.js. A failed request proceeds with the
    // server's default (keep the page's own design).
    if (isV4Template(template)) {
      const status = await fetchAtomicImportStatus();
      if (status?.in_use) {
        setV4Mode(PAGE_MODE_KEEP);
        setV4Pending({ value, slug, id, title: template?.title });
        return;
      }
    }

    navigate(value, slug, id);
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
                  {/* Only V4 is badged; V3 is what every page has been until now. */}
                  {isV4Template(template) && (
                    <div className="absolute top-2.5 left-2.5">
                      <Badge
                        data-aae-v4-badge
                        className="border-0 h-[26px] px-2.5 text-sm font-medium bg-[#5453FD] text-white rounded-full"
                      >
                        V4
                      </Badge>
                    </div>
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
                    {/* A V4 page on a site whose Elementor cannot render
                        atomic elements would import and show nothing, so the
                        button is withheld and the tooltip says why. */}
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <span
                            className="flex"
                            tabIndex={
                              isV4Template(template) && !ATOMIC_IMPORT_AVAILABLE
                                ? 0
                                : -1
                            }
                          >
                            <Button
                              variant="general"
                              data-aae-import-button
                              disabled={
                                isV4Template(template) &&
                                !ATOMIC_IMPORT_AVAILABLE
                              }
                              className={cn(
                                "py-2 ps-3 pe-4",
                                isV4Template(template) &&
                                  !ATOMIC_IMPORT_AVAILABLE &&
                                  "opacity-50 pointer-events-none"
                              )}
                              onClick={() =>
                                changeRoute("required-features", template)
                              }
                            >
                              <RiDownloadLine size={20} className="mr-2" />{" "}
                              Import
                            </Button>
                          </span>
                        </TooltipTrigger>
                        {isV4Template(template) && !ATOMIC_IMPORT_AVAILABLE && (
                          <TooltipContent>
                            <p>Needs Elementor V4 (Atomic) switched on</p>
                          </TooltipContent>
                        )}
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                </div>
              </div>
              <div className="mt-4 flex justify-between">
                <div className="ms-1">
                  <h3 className="text-lg">{template?.title}</h3>
                  <div className="flex gap-1.5 items-center mt-1.5">
                    <div className="flex-1">
                        <p className="text-label text-sm truncate">
                          {template?.categories[0]}
                        </p>
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
      <V4ImportDialog
        open={!!v4Pending}
        setOpen={(isOpen) => {
          if (!isOpen) setV4Pending(null);
        }}
        title={v4Pending?.title}
        mode={v4Mode}
        setMode={setV4Mode}
        onConfirm={(mode) => {
          if (v4Pending)
            navigate(v4Pending.value, v4Pending.slug, v4Pending.id, mode);
        }}
      />
    </>
  );
};

export default TemplateShow;
