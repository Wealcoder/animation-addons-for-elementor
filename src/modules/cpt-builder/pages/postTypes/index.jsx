import { useState } from "react";
import { Button } from "S/components/ui/button";
import { usePostType, useTab } from "S/hooks/app.hooks";
import ShowAllPostType from "./ShowAllPostType";
import TemplatesModal from "S/components/TemplatesModal";
import AiDescribeModal from "S/components/AiDescribeModal";

const PostTypes = () => {
  const { updateTabKey } = useTab();
  const { setAllPostTypes } = usePostType();
  const [templatesOpen, setTemplatesOpen] = useState(false);
  const [aiOpen, setAiOpen] = useState(false);
  const url = new URL(window.location.href);

  const createPostType = async () => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aaeaddon_add_or_update_new_post_type_builder",
          post_type_title: "New Post Type",
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          setAllPostTypes(return_content?.data);
          const pageQuery = url.searchParams.get("page");
          url.hash = "";
          url.search = `page=${pageQuery}`;
          url.searchParams.set("tab", "post-types-edit");
          url.searchParams.set("postId", return_content?.data?.post);
          window.history.replaceState({}, "", url);

          updateTabKey("post-types-edit");
        });
    } catch (error) {
      console.log(error);
    }
  };

  return (
    <div className="min-h-[70vh] px-8 py-6 border rounded-2xl">
      <div className="pb-6 border-b">
        <div className="flex gap-11 justify-between items-center">
          <div className="flex items-center">
            <h2 className="text-[18px] font-medium ">Post Types</h2>
          </div>
          <div className="flex gap-2">
            <Button variant="secondary" onClick={() => setAiOpen(true)}>
              Describe it to AI
            </Button>
            <Button variant="secondary" onClick={() => setTemplatesOpen(true)}>
              Start from a template
            </Button>
            <Button onClick={() => createPostType()}>Add New</Button>
          </div>
        </div>
      </div>
      <div className="mt-4">
        <ShowAllPostType />
      </div>
      <TemplatesModal open={templatesOpen} onOpenChange={setTemplatesOpen} />
      <AiDescribeModal open={aiOpen} onOpenChange={setAiOpen} />
    </div>
  );
};

export default PostTypes;
