import FeaturePanel from "@/components/animation-settings/FeaturePanel";
import { Badge } from "@/components/ui/badge";
import { __ } from "@wordpress/i18n";
import { useState } from "react";
import { toast } from "sonner";

/**
 * Performance — how AAE's animation runtime is DELIVERED, as opposed to what it
 * does.
 *
 * This SCREEN is the free plugin's; everything behind it is Pro. The settings
 * store, the AJAX endpoints and the script-delivery pipeline all live in
 * pro/inc/Performance/, and the payload reaches us through the
 * `aae/performance/dashboard_payload` filter Pro answers. Without Pro that
 * payload is an empty array, which is exactly how this page knows to render the
 * locked state rather than an empty one.
 *
 * Panels come from the server schema through the same generic FeaturePanel the
 * Animation Settings screen uses, so a field added to Performance::schema()
 * appears here with no JS change. They are laid out side by side rather than
 * tabbed: there are only two, and the whole point of the screen is seeing what
 * is and isn't switched on at a glance.
 */
const LOCKED_FEATURES = [
  {
    label: __("Lazy Animation", "animation-addons-for-elementor"),
    description: __(
      "GSAP and ScrollTrigger alone are about 114 KB. Deferred, they are not fetched until an animated element is close to the viewport — so a page whose animations are all below the fold starts with none of it.",
      "animation-addons-for-elementor",
    ),
  },
  {
    label: __("Reduced Motion", "animation-addons-for-elementor"),
    description: __(
      "Visitors who have asked their operating system to reduce motion get no animation runtime at all — nothing plays, and nothing is downloaded.",
      "animation-addons-for-elementor",
    ),
  },
];

/**
 * Shown when Pro is absent. Deliberately describes the real features rather
 * than an empty frame: the reason to install Pro is the only useful thing this
 * screen can say without it.
 */
const LockedNotice = () => (
  <div className="flex flex-wrap gap-6 mt-6 items-start">
    {LOCKED_FEATURES.map((feature) => (
      <div
        key={feature.label}
        className="bg-background rounded-lg p-6 max-w-[420px]"
        data-aae-locked={feature.label}
      >
        <h3 className="flex items-center gap-2">
          <span className="text-[16px] font-medium text-[var(--900,#181B25)]">
            {feature.label}
          </span>
          <Badge
            className="bg-[linear-gradient(109deg,#ffab472e_0%,#ffab472e_100%)] text-[#717784]"
            variant="pro"
          >
            {__("PRO", "animation-addons-for-elementor")}
          </Badge>
        </h3>

        <p className="text-[12px] text-[var(--600,#525866)] mt-3">
          {feature.description}
        </p>

        <p className="text-[12px] text-[var(--600,#525866)] mt-3">
          {__(
            "Available with Animation Addons Pro.",
            "animation-addons-for-elementor",
          )}
        </p>
      </div>
    ))}
  </div>
);
/**
 * @param {boolean} embedded Rendered inside another screen's tab, which already
 *                           has a page heading — so this one drops its own
 *                           rather than stacking two titles.
 */
const Performance = ({ embedded = false }) => {
  const boot = WCF_ADDONS_ADMIN?.performance || {};

  const [settings, setSettings] = useState(boot.settings || {});
  const [saving, setSaving] = useState(false);

  // The payload is Pro's to supply. No schema means no Pro, and the panels
  // below simply don't render — there is nothing to configure.
  const schema = boot.schema || {};
  const hasPro = Object.keys(schema).length > 0;

  /**
   * Commit a patch. Posts the WHOLE settings object built from the currently
   * committed state, so saving one panel can never write back a stale copy of
   * the other.
   */
  const save = async (patch) => {
    if (!WCF_ADDONS_ADMIN?.nonce || !WCF_ADDONS_ADMIN?.ajaxurl) return;

    const next = { ...settings, ...patch };
    setSaving(true);

    try {
      const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        credentials: "same-origin",
        body: new URLSearchParams({
          action: "aae_save_performance_settings",
          settings: JSON.stringify(next),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });

      const body = await response.json();

      if (!body?.success) {
        throw new Error(body?.data || "save failed");
      }

      // Adopt the SERVER's copy — the sanitiser clamps the load-ahead distance
      // to its own range, and the panel should show what was actually stored.
      setSettings(body.data.settings);
      WCF_ADDONS_ADMIN.performance.settings = body.data.settings;

      toast.success(__("Settings saved", "animation-addons-for-elementor"), {
        position: "top-right",
      });
    } catch (error) {
      toast.error(
        __("Could not save settings", "animation-addons-for-elementor"),
        { position: "top-right", description: String(error?.message || error) },
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      {!embedded && (
        <h2 className="text-[20px] font-medium text-[var(--900,#181B25)]">
          {__("Performance", "animation-addons-for-elementor")}
        </h2>
      )}

      <p className="text-[12px] text-[var(--600,#525866)] mt-2 max-w-[720px]">
        {__(
          "Both options are off by default, because they change when scripts run on a live site. Nothing here removes an animation you have set up — it only changes when the code behind it is fetched.",
          "animation-addons-for-elementor",
        )}
      </p>

      {!hasPro && <LockedNotice />}

      <div className="flex flex-wrap gap-6 mt-6 items-start">
        {Object.entries(schema).map(([key, group]) => (
          <div key={key}>
            <FeaturePanel
              feature={key}
              schema={group}
              value={settings[key] || {}}
              hasPro
              saving={saving}
              onSave={save}
            />

            <p className="text-[12px] text-[var(--600,#525866)] mt-3 max-w-[420px]">
              {key === "lazy_scripts"
                ? __(
                    "GSAP and ScrollTrigger alone are about 114 KB. With this on they are not fetched until an animated element is close to the viewport, so a page whose animations are all below the fold starts with none of it. An element whose script has not arrived yet simply renders without animating — it is never left invisible.",
                    "animation-addons-for-elementor",
                  )
                : __(
                    "Visitors who have asked their operating system to reduce motion get no animation runtime at all — nothing plays, and nothing is downloaded. Everyone else is unaffected.",
                    "animation-addons-for-elementor",
                  )}
            </p>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Performance;
