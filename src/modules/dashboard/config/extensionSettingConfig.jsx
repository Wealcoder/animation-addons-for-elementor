import CustomFontSettings from "@/components/extentions/settings/CustomFontSettings";
import ScrollSmootherSettings from "@/components/extentions/settings/ScrollSmootherSettings";
import ModuleSettings, {
  moduleSettingsSpec,
} from "@/components/extentions/settings/ModuleSettings";

const builtIn = [
  {
    key: "wcf-smooth-scroller",
    component: <ScrollSmootherSettings />,
  },
  {
    key: "custom-fonts",
    component: <CustomFontSettings />,
  },
];

/**
 * Gears that another plugin declares through the dashboard payload
 * (`addons_config.extension_settings`, a spec per extension slug — see
 * ModuleSettings.jsx). Read once at module load, like every other dashboard
 * snapshot: the payload is printed with the page. A built-in entry for the
 * same slug wins.
 */
const declared = Object.keys(
  window.WCF_ADDONS_ADMIN?.addons_config?.extension_settings || {}
)
  .filter((slug) => moduleSettingsSpec(slug) && !builtIn.some((b) => b.key === slug))
  .map((slug) => ({ key: slug, component: <ModuleSettings slug={slug} /> }));

export const ExtensionSettingConfig = [...builtIn, ...declared];
