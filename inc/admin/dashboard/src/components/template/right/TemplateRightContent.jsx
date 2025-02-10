import { useCallback, useEffect, useState } from "react";
import TemplateTopBar from "./TemplateTopBar";
import TemplateShow from "./TemplateShow";
import TemplatePagination from "./TemplatePagination";
import { debounceFn } from "@/lib/utils";

const TemplateRightContent = () => {
  const [searchKey, setSearchKey] = useState("");
  const [filterKey, setFilterKey] = useState("");
  const [allTemplate, setAllTemplate] = useState({});

  useEffect(() => {
    const meta = {
      searchKey,
      filterKey,
    };
    getAllTemplate(meta);
  }, [searchKey, filterKey]);

  const getAllTemplate = useCallback(
    debounceFn(async (meta) => {
      try {
      
        const url = new URL(
          `${WCF_ADDONS_ADMIN?.st_template_domain}wp-json/wp/v2/starter-templates`
        );

        if (meta.searchKey) {
          url.searchParams.append("s", meta.searchKey);
        }
        if (meta.filterKey) {
          if (meta.filterKey === "popular") {
            url.searchParams.append("popular", 1);
          } else {
            url.searchParams.append("orderby", "date");
          }
        }

        await fetch(url.toString())
          .then((response) => response.json())
          .then((data) => {
            setAllTemplate(data);
            window.AAEADDON_STARTER_TPLS = data;
            console.log(data);
          });
      } catch (error) {
        console.log(error);
      }
    }),
    []
  );

  return (
    <div className="mx-8">
      <div className="mt-6 mb-8">
        <TemplateTopBar
          filterKey={filterKey}
          setFilterKey={setFilterKey}
          searchKey={searchKey}
          setSearchKey={setSearchKey}
        />
      </div>
      <div className="mb-10">
        <TemplateShow allTemplate={allTemplate} />
      </div>
      {/* <div className="py-5">
        <TemplatePagination />
      </div> */}
    </div>
  );
};

export default TemplateRightContent;
