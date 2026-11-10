/**
 * Create a post type from a template or an AI proposal, then route into the
 * normal edit screen. This is the same two admin-ajax calls a manual
 * "Add New" plus a first Save already makes (create the hidden post, then
 * write its meta) - chained together so the user lands on an already-filled
 * form instead of an empty one. There is no separate write path.
 */
async function ajax(body) {
  const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams(body),
  });
  return response.json();
}

export async function applyPostType({
  pluralLabel,
  meta,
  setAllPostTypes,
  updateTabKey,
}) {
  const created = await ajax({
    action: "aaeaddon_add_or_update_new_post_type_builder",
    post_type_title: pluralLabel,
    nonce: WCF_ADDONS_ADMIN.nonce,
  });
  const newId = created?.data?.post;
  if (!newId) {
    throw new Error("Could not create the post type.");
  }

  const saved = await ajax({
    action: "aaeaddon_add_or_update_new_post_type_builder",
    post_type_id: newId,
    post_type_title: pluralLabel,
    post_meta: JSON.stringify(meta),
    nonce: WCF_ADDONS_ADMIN.nonce,
  });
  setAllPostTypes(saved?.data);

  const url = new URL(window.location.href);
  const pageQuery = url.searchParams.get("page");
  url.hash = "";
  url.search = `page=${pageQuery}`;
  url.searchParams.set("tab", "post-types-edit");
  url.searchParams.set("postId", newId);
  window.history.replaceState({}, "", url);
  updateTabKey("post-types-edit");

  return newId;
}
