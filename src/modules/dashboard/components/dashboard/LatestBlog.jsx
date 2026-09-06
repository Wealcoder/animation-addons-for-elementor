import { __ } from "@wordpress/i18n";
import { RiArrowRightUpLine } from "react-icons/ri";
import { Separator } from "../ui/separator";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";
import { LatestBlogList } from "@/config/data/latestBlogList";
import { API_ENDPOINTS } from "@/config/api";
import { useRemoteData } from "@/hooks/useRemoteData";
import { mapWpPostsToBlogs } from "@/lib/blogService";
import SampleImage from "../../../../../public/images/latest-blog/b1.png";

const GRID = "grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6";

const BlogSkeleton = () => (
  <div className={GRID}>
    {Array.from({ length: 4 }).map((_, i) => (
      <div key={`blog_skeleton-${i}`} className="flex flex-col gap-[19px]">
        <div className="w-full h-[174px] shrink-0 rounded-lg bg-background-secondary animate-pulse" />
        <div className="flex flex-col gap-[18px]">
          <div className="h-4 w-11/12 rounded bg-background-secondary animate-pulse" />
          <div className="h-3 w-2/5 rounded bg-background-secondary animate-pulse" />
        </div>
      </div>
    ))}
  </div>
);

const LatestBlog = () => {
  /*
   * Live posts from animation-addons.com, with config/data/latestBlogList.js
   * as the fallback. The hook keeps the fallback on screen whenever the
   * request fails, times out, or answers with something unrenderable (a WP
   * REST error is valid JSON, so a 200 is not proof of anything).
   */
  const { data: blogs, loading } = useRemoteData(
    API_ENDPOINTS.blogs,
    LatestBlogList,
    {
      transform: mapWpPostsToBlogs,
      cacheKey: "aae_dashboard_blogs",
    },
  );

  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  return (
    <div
      className={cn(
        "border rounded-2xl p-5",
        hashValue === "wcf-blog"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : "shadow-common",
      )}
      id="wcf-blog"
    >
      <div className="flex justify-between gap-11">
        <p className="font-medium text-text">
          {__("Blogs", "animation-addons-for-elementor")}
        </p>
        <div>
          <a
            href={"https://animation-addons.com/blog"}
            target="_blank"
            rel="noreferrer"
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            {__("View all", "animation-addons-for-elementor")}
            <RiArrowRightUpLine
              size={18}
              className="rtl:rotate-360 rtl:scale-x-[-1]"
            />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      {loading ? (
        <BlogSkeleton />
      ) : (
        <div className={GRID}>
          {blogs?.map((blog, i) => (
            <div
              key={blog.url || `latest_blog-${i}`}
              className="group flex flex-col gap-[19px]"
            >
              {/*
               * Figma: 330x174, radius 8px, `align-self: stretch` + `flex: none`.
               * 330 is just the measured width in that frame — the grid is
               * responsive, so stretch is `w-full`. `shrink-0` is `flex: none`:
               * without it a title that wraps to two lines squeezes the 174px.
               *
               * object-cover, not scale-down: the sources run 1.69-2.00 against
               * a 1.897 box, so cover crops 0.5-11% while scale-down would
               * letterbox each card by a different amount and stop the radius
               * hugging the artwork.
               */}
              <div className="w-full h-[174px] shrink-0 overflow-hidden rounded-lg">
                <img
                  className="w-full h-full object-cover object-center transition-all group-hover:scale-110"
                  src={blog.thumbnail || SampleImage}
                  onError={(e) => {
                    e.currentTarget.src = SampleImage;
                  }}
                  alt={blog.alt || ""}
                />
              </div>
              <div className="flex flex-col gap-[18px]">
                <a href={blog.url} target="_blank" rel="noreferrer">
                  <h3 className="text-sm font-medium text-text group-hover:text-brand line-clamp-2">
                    <span dir="ltr">{blog.title}</span>
                  </h3>
                </a>
                <div className="flex items-center gap-1.5 text-sm text-text-secondary">
                  <span>{blog.createAt}</span>
                  {blog.readingTime && (
                    <>
                      <span className="w-1 h-1 rounded-full bg-[#717784] shrink-0" />
                      <span>{blog.readingTime}</span>
                    </>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default LatestBlog;
