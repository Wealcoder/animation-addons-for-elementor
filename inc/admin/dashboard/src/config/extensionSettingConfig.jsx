import CustomFontSettings from "@/components/extentions/settings/CustomFontSettings";
import ScrollSmootherSettings from "@/components/extentions/settings/scrollSmootherSettings";

export const ExtensionSettingConfig = [
  {
    key: "wcf-smooth-scroller",
    component: <ScrollSmootherSettings />,
  },
  {
    key: "custom-fonts",
    component: <CustomFontSettings />,
  },
  // {
  //   key: "scroll-trigger",
  //   component: <></>,
  // },
  // {
  //   key: "draw-svg",
  //   component: <></>,
  // },
  // {
  //   key: "flip",
  //   component: <></>,
  // },
];
