import TemplateLeftFilter from "@/components/template/left/TemplateLeftFilter";
import TemplateRightContent from "@/components/template/right/TemplateRightContent";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useEffect, useRef, useState, useCallback } from "react";
import { debounceFn } from "@/lib/utils";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { StaterTemplateHeader } from "../components/header/StaterTemplateHeader";
import { RiArrowLeftLine } from "react-icons/ri";
import StarterTemplateFilter from "../components/header/StarterTemplateFilter";

const StaterTemplate = () => {
  const viewportRef = useRef(null);
  const prevScrollTop = useRef(0); // Track previous scroll position
  const [hasReachedBottom, setHasReachedBottom] = useState(false);
  const [searchKey, setSearchKey] = useState("");
  const [filterKey, setFilterKey] = useState("");
  const [allTemplate, setAllTemplate] = useState({});
  const [pageNum, setPageNum] = useState(1);
  const [loading, setLoading] = useState(true);
  const [types, setTypes] = useState([]);
  const [license, setLicense] = useState("");
  const [selectedCategory, setSelectedCategory] = useState([]);
  const [openSidebar, setOpenSidebar] = useState(false);

  useEffect(() => {
    const meta = {
      searchKey,
      filterKey,
      pageNum,
      types,
      license,
      selectedCategory,
      allTemplate,
      wishlist: WCF_ADDONS_ADMIN.addons_config.wishlist.toString(),
    };

    getAllTemplate(meta);
  }, [searchKey, filterKey, pageNum, types, license, selectedCategory]);

  const getAllTemplate = useCallback(
    debounceFn(async (meta) => {
      setLoading(true);
      try {
        const url = new URL(
          `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
        );

        if (meta.searchKey) {
          url.searchParams.append("s", meta.searchKey);
        }
        if (meta.pageNum) {
          url.searchParams.append("page", meta.pageNum);
          url.searchParams.append("per_page", 40);
        }
        if (meta.filterKey) {
          if (meta.filterKey === "popular") {
            url.searchParams.append("popular", 1);
          } else {
            url.searchParams.append("orderby", "date");
          }
        }

        if (meta.selectedCategory && meta.selectedCategory.length) {
          url.searchParams.append("st-cat", meta.selectedCategory.toString());
        }

        if (meta?.types?.includes("favorites")) {
          url.searchParams.append("favourites", 1);
        } else if (meta?.types?.includes("wishlist")) {
          url.searchParams.append("wishlist", meta.wishlist);
        }

        if (meta.license) {
          url.searchParams.append(
            "premium",
            meta.license === "pro" ? "yes" : "no"
          );
        }

        await fetch(url.toString())
          .then((response) => response.json())
          .then((data) => {
            if (meta.pageNum === 1) {
              setAllTemplate(data);
              window.AAEADDON_STARTER_TPLS = data;
            } else {
              const updateData = {
                ...data,
                templates: [...meta?.allTemplate?.templates, ...data.templates],
              };
              setAllTemplate(updateData);
              window.AAEADDON_STARTER_TPLS = updateData;
            }
          });
      } catch (error) {
        console.log(error);
      } finally {
        setLoading(false);
      }
    }),
    []
  );

  useEffect(() => {
    const handleScroll = () => {
      const viewport = viewportRef.current?.querySelector(
        "[data-radix-scroll-area-viewport]"
      );

      if (!viewport) return;

      const { scrollTop, scrollHeight, clientHeight } = viewport;

      // Check if scrolling down
      const isScrollingDown = scrollTop > prevScrollTop.current;

      // Update previous scroll position
      prevScrollTop.current = scrollTop;

      // Only trigger if scrolling down and near the bottom
      if (
        isScrollingDown &&
        scrollTop + clientHeight >= scrollHeight - 5 &&
        !hasReachedBottom &&
        allTemplate?.templates?.length !== allTemplate?.total
      ) {
        setHasReachedBottom(true);
        setPageNum((prev) => prev + 1);
      }
    };

    const viewport = viewportRef.current?.querySelector(
      "[data-radix-scroll-area-viewport]"
    );

    if (viewport) {
      viewport.addEventListener("scroll", handleScroll);
    }

    return () => {
      if (viewport) {
        viewport.removeEventListener("scroll", handleScroll);
      }
    };
  }, [hasReachedBottom, allTemplate]);

  // Reset the hasReachedBottom flag after loading more content
  useEffect(() => {
    if (hasReachedBottom) {
      setHasReachedBottom(false);
    }
  }, [allTemplate]);

  const changeRoute = (value) => {
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = "";
    url.hash = "";
    url.search = `page=${pageQuery}`;

    url.searchParams.set("tab", value);
    window.history.replaceState({}, "", url);
    window.location.reload();
  };

  return (
    <ScrollArea className="h-screen" ref={viewportRef}>
      <div className="px-5 py-[54px] min-h-screen relative starter-template-dashboard">
        <div className="fixed top-5 left-0 px-8 w-full z-50">
          <div className="flex items-center gap-[30px] bg-white shadow-[0_0_15px_10px_rgba(0,0,0,0.05)] px-[25px] rounded-[10px] min-h-[74px]">
            <div
              onClick={() => changeRoute("dashboard")}
              className="cursor-pointer flex w-11 h-11 px-[3px] py-1 justify-center items-center bg-[#F6502C] rounded-full"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 14 14"
                fill="none"
                className="w-[14px] h-[14px] flex-shrink-0"
              >
                <path
                  d="M3.34988 6.1001H14V7.90001H3.34988L8.04335 12.7273L6.80593 14L0 7.00005L6.80593 0L8.04335 1.27273L3.34988 6.1001Z"
                  fill="#ffffff"
                />
              </svg>
            </div>
            <StaterTemplateHeader />
            <div className="bg-[#1212121A] w-[1px] h-6"></div>
            <StarterTemplateFilter />
          </div>
        </div>
        <div className="px-8 py-[80px] bg-[#F9F9F9] rounded-[30px]">
          {loading && !allTemplate?.templates?.length ? (
            <div className="flex justify-center items-center h-screen">
              <p className="text-lg font-semibold">Loading...</p>
            </div>
          ) : (
            <TemplateRightContent
              searchKey={searchKey}
              setSearchKey={setSearchKey}
              filterKey={filterKey}
              setFilterKey={setFilterKey}
              setPageNum={setPageNum}
              allTemplate={allTemplate}
              setOpenSidebar={setOpenSidebar}
            />
          )}
          {loading && allTemplate?.templates?.length ? (
            <div className="flex justify-center items-center h-[25vh]">
              <p className="text-lg font-semibold">Loading...</p>
            </div>
          ) : (
            ""
          )}
        </div>
      </div>
    </ScrollArea>
  );
};

export default StaterTemplate;
