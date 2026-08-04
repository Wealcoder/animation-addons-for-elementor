import {
  RiApps2AddLine,
  RiCommandLine,
  RiLayoutGridLine,
  RiMagicLine,
  RiShareBoxLine,
  RiVipCrown2Line,
} from "react-icons/ri";

export const MainNavData = [
  {
    name: "Dashboard",
    path: "dashboard",
    role: ["administrator", "editor"],
    icon: <RiLayoutGridLine size={20} />,
  },
  {
    name: "Widgets",
    path: "widgets",
    role: ["administrator", "editor"],
    icon: <RiCommandLine size={20} />,
  },
  {
    name: "Extensions",
    path: "extensions",
    role: ["administrator", "editor"],
    icon: <RiApps2AddLine size={20} />,
  },
  {
    name: "Animation Settings",
    path: "animation-settings",
    role: ["administrator"],
    icon: <RiMagicLine size={20} />,
  },
  {
    name: "Free vs Pro",
    path: "free-pro",
    role: ["administrator"],
    icon: <RiVipCrown2Line size={20} />,
  },
  /*
   * "Integrations" left the sidebar 2026-08-04 — the Library screen it held
   * now lives as an Animation Settings tab. The `?tab=integrations` route is
   * deliberately still served (showFullContent.jsx) for old bookmarks, the
   * same arrangement Performance has.
   */
  {
    name: "Starter Template",
    path: "stater-template",
    role: ["administrator"],
    icon: <RiShareBoxLine size={20} />,
    targetBlank: true,
  },
];
