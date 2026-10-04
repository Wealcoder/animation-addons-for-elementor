/**
 * Maps the animation-addons.com WP REST `/wp/v2/posts` payload onto the shape
 * the Blogs card renders (see config/data/latestBlogList.js for that shape).
 *
 * Kept separate from the fetching hook so the endpoint's response shape is
 * described in exactly one place, and so a malformed payload degrades to an
 * empty list — which is what tells useRemoteData to keep the static fallback.
 */

/**
 * REST titles arrive HTML-encoded (`Performance &amp; Core Web Vitals`), and
 * occasionally carry inline markup. React renders our output as a text node,
 * so both have to be resolved here or they show up literally.
 *
 * `innerHTML` on a detached <textarea> is the standard entity decode: its
 * content model is text, so nothing in the string can execute, and the element
 * is never attached to the document.
 */
const decodeHtml = (value) => {
  if (typeof value !== "string") return "";

  let decoded = value;
  try {
    const holder = document.createElement("textarea");
    holder.innerHTML = value;
    decoded = holder.value;
  } catch {
    // Leave the raw string; a stray entity reads better than an empty card.
  }

  return decoded.replace(/<[^>]*>/g, "").trim();
};

/**
 * The card is ~280x174, so `medium_large` (768w) is the smallest size that
 * still looks right on a HiDPI screen. Not every attachment has every size
 * registered, hence the walk down to the original.
 */
const THUMBNAIL_SIZES = ["medium_large", "large", "medium", "full"];

const pickThumbnail = (media) => {
  const sizes = media?.media_details?.sizes || {};

  for (const size of THUMBNAIL_SIZES) {
    const url = sizes[size]?.source_url;
    if (url) return url;
  }

  return media?.source_url || "";
};

/**
 * `date` is the site's local time with no offset, which `Date` reads as the
 * viewer's local time. That is off by at most a few hours and only ever
 * matters for a day boundary, which is acceptable for a published-on line —
 * and the alternative (date_gmt shifted back) needs the remote site's
 * timezone, which the payload does not carry.
 */
const formatDate = (value) => {
  if (!value) return "";

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return "";

  return parsed.toLocaleDateString(undefined, {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

/**
 * Note there is no `readingTime`: deriving one needs `content.rendered`, which
 * takes the response from 17 KB to 216 KB for four posts. The Blogs card
 * renders that line conditionally, so remote posts simply show the date while
 * the static fallback keeps its own figure.
 */
export const mapWpPostsToBlogs = (payload) => {
  if (!Array.isArray(payload)) return [];

  return payload
    .map((post) => {
      const media = post?._embedded?.["wp:featuredmedia"]?.[0];

      return {
        title: decodeHtml(post?.title?.rendered),
        url: post?.link || "",
        thumbnail: pickThumbnail(media),
        alt: decodeHtml(media?.alt_text),
        createAt: formatDate(post?.date),
      };
    })
    .filter((blog) => blog.title && blog.url);
};
