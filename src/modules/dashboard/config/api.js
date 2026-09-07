// Set an endpoint here once its REST API exists; leave it null to keep
// using the static fallback data from src/modules/dashboard/config/data/*.
//
// `blogs` reads animation-addons.com's public WP REST API. The query is
// trimmed deliberately: `_fields` without `_links.wp:featuredmedia` drops the
// `_embedded` block (WordPress builds it from the links), and asking for
// `content` as well takes the response from ~17 KB to ~216 KB.
export const API_ENDPOINTS = {
  tutorials: null,
  documentation: null,
  whatsNew: null,
  blogs:
    "https://animation-addons.com/blog/wp-json/wp/v2/posts" +
    "?per_page=4&orderby=date&order=desc" +
    "&_embed=wp%3Afeaturedmedia" +
    "&_fields=id,date,link,title,_links.wp%3Afeaturedmedia,_embedded",
};
