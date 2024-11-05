import { RiArrowRightUpLine, RiNewsLine } from "react-icons/ri";
import { Separator } from "../ui/separator";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";
import { LatestBlogList } from "@/config/data/latestBlogList";

const LatestBlog = () => {
  const blogs = LatestBlogList;
  // const location = useLocation();

  // const hashValue = location.hash.replace("#", "");
  const hashValue = "";

  return (
    <div
      className={cn(
        "border rounded-2xl p-5 shadow-common",
        hashValue === "wcf-blog"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : ""
      )}
      id="wcf-blog"
    >
      <div className="flex justify-between gap-11">
        <div className="flex gap-2 items-center">
          <RiNewsLine size={20} color="#47C2FF" />
          <p className="font-medium">Latest Blogs & Articles</p>
        </div>
        <div>
          <a
            href={"#"}
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            View all <RiArrowRightUpLine size={18} className="ml-1" />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div className="grid grid-cols-4 gap-5">
        {blogs?.map((blog, i) => (
          <div key={`latest_blog-${i}`} className="group">
            <div className="overflow-hidden">
              <img
                className="transition-all group-hover:scale-110"
                src={blog.thumbnail}
                alt="Thumbnail"
              />
            </div>
            <div className="mt-3">
              <a href="#">
                <h3 className="text-sm font-medium group-hover:text-brand">
                  {blog.title}
                </h3>
              </a>
              <div className="flex h-5 items-center space-x-1.5 text-xs text-text-secondary mt-2">
                <div>{blog.createAt}</div>
                <Separator
                  orientation="vertical"
                  className="h-3 text-label bg-label"
                />
                <div>{blog.readingTime}</div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default LatestBlog;
