import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Toggle } from "@/components/ui/toggle";
import { useTNavigation } from "@/hooks/app.hooks";
import { storeWisList } from "@/lib/wishlistStoreFunction";
import { Dot, Heart } from "lucide-react";
import { RiDownloadLine, RiEyeLine, RiVipCrown2Fill } from "react-icons/ri";

const TemplateShow = ({ allTemplate }) => {
  const { setTabKey } = useTNavigation();

  const changeRoute = (value, slug, id) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    url.searchParams.set("template", slug);
    url.searchParams.set("templateid", id);
    window.history.replaceState({}, "", url);
    setTabKey(value);
  };

  const saveWishlist = async (data) => {
    try {
      console.log(data);
      console.log(allTemplate);
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
          console.log(return_content);
        });
    } catch (error) {}
  };

  return (
    <div className="grid md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-x-5 gap-y-8">
      {allTemplate?.templates?.map((template, i) => (
        <div key={`all_template-${i}`} className="group" id={template?.slug}>
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
                <Button variant="general" className="py-2 ps-3 pe-4">
                  <RiEyeLine size={20} className="mr-2" /> Preview
                </Button>
                <Button
                  variant="general"
                  className="py-2 ps-3 pe-4"
                  onClick={() =>
                    changeRoute(
                      "required-features",
                      template?.slug,
                      template?.id
                    )
                  }
                >
                  <RiDownloadLine size={20} className="mr-2" /> Insert
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

                <Dot className="w-2 h-2 text-icon-secondary" strokeWidth={2} />
                <div className="text-label text-sm flex items-center gap-1">
                  <RiDownloadLine />
                  <p>
                    <span>{template?.downloads}</span> Inserts
                  </p>
                </div>
              </div>
            </div>
            <div className="mt-[3px] pe-1.5">
              <Toggle
                aria-label="Toggle bold"
                defaultPressed={template.isFavorite}
                pressed={template.isFavorite}
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
  );
};

export default TemplateShow;
