import MainHeader from "S/components/header/MainHeader";
import { usePostType, useTab, useTaxonomy } from "S/hooks/app.hooks";
import Taxonomy from "S/pages/taxonomy";
import EditTaxonomy from "S/pages/taxonomy/EditTaxonomy";
import { useEffect, lazy, Suspense } from "react";

const PostTypes = lazy(() => import("S/pages/postTypes"));
const EditPostType = lazy(() => import("S/pages/postTypes/EditPostType"));

const MainLayout = () => {
  const { tabKey, updateTabKey } = useTab();
  const { setAllPostTypes } = usePostType();
  const { setAllTaxonomy } = useTaxonomy();

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get("tab");
    if (tabValue) {
      updateTabKey(tabValue);
    } else {
      updateTabKey("post-types");
    }
  }, []);

  const showContent = (tabKey) => {
    switch (tabKey) {
      case "post-types":
        return <PostTypes />;
      case "post-types-edit":
        return <EditPostType />;
      case "taxonomy":
        return <Taxonomy />;
      case "taxonomy-edit":
        return <EditTaxonomy />;
      default:
        return <></>;
    }
  };

  const getAllPostType = async () => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_post_type_builder_list",
          wcf_nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          if (return_content.success) setAllPostTypes(return_content?.data);
        });
    } catch (error) {
      console.log(error);
    }
  };

  const getAllTaxonomy = async () => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aae_taxonomy_builder_list",
          wcf_nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          if (return_content.success) setAllTaxonomy(return_content?.data);
        });
    } catch (error) {
      console.log(error);
    }
  };

  useEffect(() => {
    getAllPostType();
    getAllTaxonomy();
  }, []);

  return (
    <div className="wcfcb2025-wrapper">
      <div className="wcfcb2024-style container overflow-x-hidden bg-background rounded-[10px]">
        <MainHeader />
        <div className="px-5 2xl:px-24 py-8">
          <Suspense fallback={<p>Loading...</p>}>
            {showContent(tabKey)}
          </Suspense>
        </div>
      </div>
    </div>
  );
};

export default MainLayout;
