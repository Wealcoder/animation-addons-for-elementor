/**
 * REST client for Pro's `aae-pro/v1/platform/ai/*` routes (cookie +
 * X-WP-Nonce) - the exact same routes Pro's own "AI & Connections" screen
 * calls (src/modules/platform/connections/api.js in the Pro plugin). This is
 * a direct port of that 20-line wrapper, pointed at the base URL/nonce Pro
 * publishes through `WCF_ADDONS_ADMIN.addons_config.ai` instead of Pro's own
 * `window.AAEADDON_PRO_CONNECTIONS` global.
 */
function cfg() {
  return window.WCF_ADDONS_ADMIN?.addons_config?.ai || {};
}

async function request(path, { method = "GET", body } = {}) {
  const c = cfg();
  const res = await fetch(c.rest_url + path.replace(/^\//, ""), {
    method,
    credentials: "same-origin",
    headers: {
      "X-WP-Nonce": c.rest_nonce,
      Accept: "application/json",
      ...(body ? { "Content-Type": "application/json" } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    data = null;
  }
  if (!res.ok) {
    const err = new Error((data && data.message) || `Request failed (${res.status})`);
    err.status = res.status;
    err.code = data && data.code;
    throw err;
  }
  return data;
}

export const aiApi = {
  save: (patch) => request("ai/settings", { method: "POST", body: patch }),
  test: () => request("ai/test", { method: "POST" }),
  overview: () => request("connections"),
  createToken: (name, scopes) => request("tokens", { method: "POST", body: { name, scopes } }),
};
