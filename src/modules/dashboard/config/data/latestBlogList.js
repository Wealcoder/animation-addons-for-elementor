/**
 * Fallback for the Blogs card, shown whenever the live read from
 * animation-addons.com fails, times out, or answers with something
 * unrenderable — see hooks/useRemoteData.js and lib/blogService.js.
 *
 * Thumbnails are imported, not linked, so this path stays intact on a site
 * that cannot reach the blog at all: a remote URL here would fail for exactly
 * the same reason the API call just did.
 *
 * `createAt` uses the long month form on purpose — `mapWpPostsToBlogs()`
 * formats remote dates with `toLocaleDateString(… month: "long" …)`, so the
 * two paths render identically rather than the fallback being spottable.
 *
 * Image basenames match each post's own slug, so the pairing needs no lookup.
 */
import CompatibilityGuide from "../../../../../public/images/fallback-blogs/elementor-v4-addons-compatibility-guide.webp";
import CleanDom from "../../../../../public/images/fallback-blogs/clean-dom-elementor-v4.webp";
import AnimationPlugins from "../../../../../public/images/fallback-blogs/best-wordpress-animation-plugins.webp";
import V4Widgets from "../../../../../public/images/fallback-blogs/best-elementor-v4-widgets.webp";

export const LatestBlogList = [
  {
    title:
      "Elementor V4 Compatible Addons: Complete 2026 Compatibility Guide",
    thumbnail: CompatibilityGuide,
    createAt: "September 3, 2026",
    readingTime: "14 min read",
    url: "https://animation-addons.com/blog/elementor-v4-addons-compatibility-guide/",
  },
  {
    title:
      "Elementor V4 Clean DOM: How It Improves Performance & Core Web Vitals",
    thumbnail: CleanDom,
    createAt: "September 3, 2026",
    readingTime: "16 min read",
    url: "https://animation-addons.com/blog/clean-dom-elementor-v4/",
  },
  {
    title: "Best WordPress Animation Plugins (2026)",
    thumbnail: AnimationPlugins,
    createAt: "August 27, 2026",
    readingTime: "23 min read",
    url: "https://animation-addons.com/blog/best-wordpress-animation-plugins/",
  },
  {
    title:
      "Best Elementor V4 Widgets for Building Faster WordPress Websites",
    thumbnail: V4Widgets,
    createAt: "August 23, 2026",
    readingTime: "22 min read",
    url: "https://animation-addons.com/blog/best-elementor-v4-widgets/",
  },
];
