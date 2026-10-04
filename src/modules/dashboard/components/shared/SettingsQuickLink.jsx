import { useTNavigation } from "@/hooks/app.hooks";
import { __ } from "@wordpress/i18n";
import { RiSettings3Line } from "react-icons/ri";

/**
 * Shortcut from the V4 lists to the Settings screen (`?tab=animation-settings`).
 *
 * NOT MOUNTED ANYWHERE since 2026-09-06 — kept on disk, unused. It was
 * right-aligned on the Widgets and Extensions tab rows and was removed from
 * both once Settings became a permanent menu item (it took the Free vs Pro
 * slot, see config/nav/main-nav.jsx). Its load-bearing reason was that a site
 * with v3 switches ON had the Settings item hidden from the menu, leaving this
 * the only in-dashboard route to the screen; with the menu item unconditional
 * that route exists on every screen, and a shortcut to a permanent menu item
 * one row below it is noise on a row that already carries three links.
 *
 * Kept rather than deleted because the OTHER reason still stands and could
 * bring it back: that screen is where the site-wide V4 chrome lives —
 * Preloader, Cursor, Scroll to Top, Scroll Indicator, Popup, plus Performance
 * and GSAP Library — so it is the natural next stop after switching atomic
 * widgets on.
 *
 * Not to be confused with LegacyRevealLink, which sat beside it and does the
 * opposite job: that one reveals the V3 LIST and opens no settings at all.
 *
 * LOW EMPHASIS, matching its neighbours — muted 12px text, no button.
 */
const SettingsQuickLink = () => {
  const { setTabKey } = useTNavigation();

  // Same shape MainNav.changeRoute uses: rewrite the query in place and let the
  // router state follow, so there is no full page reload and the URL stays
  // shareable.
  const openSettings = (event) => {
    event.preventDefault();

    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");

    url.search = `page=${pageQuery}`;
    url.hash = "";
    url.searchParams.set("tab", "animation-settings");

    window.history.replaceState({}, "", url);
    setTabKey("animation-settings");
  };

  return (
    <a
      href="#"
      onClick={openSettings}
      data-aae-settings-quicklink
      title={__(
        "Animation, performance and GSAP library settings",
        "animation-addons-for-elementor"
      )}
      className="inline-flex items-center gap-1 text-xs text-text-secondary hover:text-text-secondary-hover underline underline-offset-2 decoration-dotted"
    >
      <RiSettings3Line size={13} />
      {__("Settings", "animation-addons-for-elementor")}
    </a>
  );
};

export default SettingsQuickLink;
