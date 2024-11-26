import { RiArrowRightUpLine, RiNewsLine } from "react-icons/ri";
import { Separator } from "../ui/separator";
import { cn } from "@/lib/utils";
import { buttonVariants } from "../ui/button";
import { LatestBlogList } from "@/config/data/latestBlogList";
import SampleImage from "../../../public/images/latest-blog/b1.png";

const LatestBlog = () => {
  const blogs = LatestBlogList;
  const hash = window.location.hash;
  const hashValue = hash?.replace("#", "");

  return (
    <div
      className={cn(
        "border rounded-2xl p-5",
        hashValue === "wcf-blog"
          ? "shadow-[0px_0px_0px_2px_rgba(252,104,72,0.25),0px_1px_2px_0px_rgba(10,13,20,0.03)]"
          : "shadow-common"
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
            href={"https://crowdytheme.com/blog"}
            target="_blank"
            className={cn(buttonVariants({ variant: "secondary", size: "sm" }))}
          >
            View all <RiArrowRightUpLine size={18} className="ml-1" />
          </a>
        </div>
      </div>
      <Separator className="mt-4 mb-5" />
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        {blogs?.map((blog, i) => (
          <div key={`latest_blog-${i}`} className="group">
            <div className="overflow-hidden h-[170px] rounded-lg">
              <img
                className="transition-all group-hover:scale-110 h-[170px] object-cover"
                src={blog.thumbnail}
                onError={(e) => {
                  e.currentTarget.src = SampleImage;
                }}
                alt="Thumbnail"
              />
            </div>
            <div className="mt-3">
              <a href={blog.url} target="_blank">
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
