document.addEventListener("DOMContentLoaded", function () {
  // Bail if nothing to send
  if (!AAE_ACTIVATION.activation_count || !AAE_ACTIVATION.last_activated) return;

  const payload = {
    plugin_slug: AAE_ACTIVATION.plugin_slug,
    event: "activated"  
  };

  // 1) Send to your remote collector
  fetch('https://data.animation-addons.com/wp-json/wmd/v1/org/install/daily/increment?plugin_slug=animation-addons-for-elementor&event=activated', {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then((res) => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json().catch(() => ({}));
    })
    .then(() => {
      // 2) On success, ask WP (admin-ajax) to delete the options
      const form = new URLSearchParams();
      form.set("action", "aae_admin_plugin_activation_count_remove");
      form.set("_ajax_nonce", AAE_ACTIVATION.nonce);

      return fetch(AAE_ACTIVATION.ajax_url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: form.toString(),
        credentials: "same-origin",
      });
    })
    .then((res) => {
      if (!res) return; // previous step may have thrown
      if (!res.ok) throw new Error("Cleanup HTTP " + res.status);
      return res.json();
    })
    .then((json) => {
      if (json && json.success) {
        console.log("✅ Options cleaned up:", json.data);
      } else if (json) {
        console.warn("⚠️ Cleanup error:", json.data || json);
      }
    })
    .catch((err) => {
      console.warn("❌ Activation flow error:", err.message);
    });
});
