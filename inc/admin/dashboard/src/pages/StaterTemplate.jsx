import TemplateLeftFilter from "@/components/template/left/TemplateLeftFilter";
import TemplateRightContent from "@/components/template/right/TemplateRightContent";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useEffect, useRef, useState, useCallback } from "react";
import { debounceFn } from "@/lib/utils";

const StaterTemplate = () => {
  const viewportRef = useRef(null);
  const [hasReachedBottom, setHasReachedBottom] = useState(false);
  const [searchKey, setSearchKey] = useState("");
  const [filterKey, setFilterKey] = useState("");
  const [allTemplate, setAllTemplate] = useState({});
  const [pageNum, setPageNum] = useState(1);
  const [loading, setLoading] = useState(false);
  const [types, setTypes] = useState([]);
  const [license, setLicense] = useState("");
  const [selectedCategory, setSelectedCategory] = useState([]);

  useEffect(() => {
    const meta = {
      searchKey,
      filterKey,
      pageNum,
      types,
      license,
      selectedCategory,
      allTemplate,
    };
    getAllTemplate(meta);
  }, [searchKey, filterKey, pageNum, types, license, selectedCategory]);

  const getAllTemplate = useCallback(
    debounceFn(async (meta) => {
      if (loading) return;
      setLoading(true);
      try {
        console.log(meta);
        const url = new URL(
          `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
        );

        if (meta.searchKey) {
          url.searchParams.append("s", meta.searchKey);
        }
        if (meta.pageNum) {
          url.searchParams.append("page", meta.pageNum);
          url.searchParams.append("per_page", 8);
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
        }

        if (meta.license) {
          url.searchParams.append("premium", meta.license === 'pro' ? 'yes': 'no');
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

      if (scrollTop + clientHeight >= scrollHeight - 5 && !hasReachedBottom) {
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
  }, [hasReachedBottom]);

  const resetFlag = () => setHasReachedBottom(false);

  return (
    <div className="flex">
      <div className="w-[278px] border-r border-border h-[calc(100vh-85px)]">
        <TemplateLeftFilter
          types={types}
          setTypes={setTypes}
          license={license}
          setLicense={setLicense}
          selectedCategory={selectedCategory}
          setSelectedCategory={setSelectedCategory}
          setPageNum={setPageNum}
        />
      </div>
      <ScrollArea
        className="h-[calc(100vh-85px)] flex-1"
        ref={viewportRef}
        onScroll={resetFlag}
      >
        <>
          <TemplateRightContent
            searchKey={searchKey}
            setSearchKey={setSearchKey}
            filterKey={filterKey}
            setFilterKey={setFilterKey}
            setPageNum={setPageNum}
            allTemplate={allTemplate}
          />
          <div className="flex justify-center items-center h-[10vh]">
            {loading ? <p className="text-lg font-semibold">Loading...</p> : ""}
          </div>
        </>
      </ScrollArea>
    </div>
  );
};

export default StaterTemplate;
