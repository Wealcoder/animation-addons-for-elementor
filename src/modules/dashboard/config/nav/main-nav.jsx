import {
  RiApps2AddLine,
  RiCommandLine,
  RiLayoutGridLine,
  RiMagicLine,
  RiShareBoxLine,
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
  /*
   * Settings is the V4 home for the five site-wide chrome features (Preloader,
   * Cursor, Scroll to Top, Scroll Indicator, Popup) that a v3 site configures
   * from Elementor's own Site Settings instead, plus Performance, the GSAP
   * Library and the `legacy_v3` switch.
   *
   * PERMANENT since 2026-09-06, when it took over the sidebar slot "Free vs
   * Pro" held. It was previously gated on `SHOW_ANIMATION_SETTINGS`
   * (`!V3_HAS_ACTIVE`), which left a site with v3 switches ON no in-dashboard
   * route to the screen at all — everything on it was URL-only there, and the
   * SettingsQuickLink that partly covered the gap lived on the V4 view, which
   * such a site need not have either. An unconditional entry closes it, and no
   * other menu item is bought with a rule about what the site uses. (That
   * shortcut has since been removed from both list pages; the component is
   * still on disk, unmounted.)
   *
   * Labelled "Settings", which is what the screen's own sidebar heading has
   * always said. The route, the payload key and the feature's name in the code
   * stay `animation-settings` — renaming those would break bookmarks and every
   * reference in CLAUDE.md for a label change.
   */
  {
    name: "Settings",
    path: "animation-settings",
    role: ["administrator"],
    icon: <RiMagicLine size={20} />,
  },
  /*
   * "Free vs Pro" left the sidebar 2026-09-06 and Settings took its place.
   *
   * Unlike `performance` and `integrations` — screens that moved INTO Settings
   * as tabs and kept their own routes for bookmarks — there is no comparison
   * screen left to route to, so `?tab=free-pro` is resolved to Settings and
   * rewritten instead. See LEGACY_TAB_ALIASES in showFullContent.jsx.
   */
  {
    name: "Starter Template",
    path: "stater-template",
    role: ["administrator"],
    icon: <RiShareBoxLine size={20} />,
    targetBlank: true,
  },
];
