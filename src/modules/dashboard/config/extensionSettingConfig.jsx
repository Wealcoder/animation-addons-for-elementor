import CustomFontSettings from "@/components/extentions/settings/CustomFontSettings";
import ScrollSmootherSettings from "@/components/extentions/settings/ScrollSmootherSettings";

export const ExtensionSettingConfig = [
  {
    key: "wcf-smooth-scroller",
    component: <ScrollSmootherSettings />,
  },
  {
    key: "custom-fonts",
    component: <CustomFontSettings />,
  },
];
