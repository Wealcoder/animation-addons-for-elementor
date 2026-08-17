import {
  RiApps2AddLine,
  RiCommandLine,
  RiLayoutGridLine,
  RiMagicLine,
  RiShareBoxLine,
  RiVipCrown2Line,
} from "react-icons/ri";
import { SHOW_ANIMATION_SETTINGS } from "@/lib/systemVisibility";

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
  /*
   * Animation Settings is the V4 home for the five site-wide chrome features
   * (Preloader, Cursor, Scroll to Top, Scroll Indicator, Popup) that a v3 site
   * configures from Elementor's own Site Settings instead — so it is offered
   * only to a site with no v3 widgets or extensions left switched on.
   *
   * `visible: false` hides the MENU ITEM, nothing else: showFullContent.jsx
   * still routes `?tab=animation-settings`, which is what keeps the
   * `legacy_v3` switch on that screen reachable for the v3 users it exists
   * for. Same arrangement `performance` and `integrations` already have.
   */
  {
    // Labelled "Settings", which is what the screen's own sidebar heading has
    // always said. The route, the payload key and the feature's name in the
    // code stay `animation-settings` — renaming those would break bookmarks
    // and every reference in CLAUDE.md for a label change.
    name: "Settings",
    path: "animation-settings",
    role: ["administrator"],
    icon: <RiMagicLine size={20} />,
    visible: SHOW_ANIMATION_SETTINGS,
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
