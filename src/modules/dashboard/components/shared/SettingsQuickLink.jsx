import { useTNavigation } from "@/hooks/app.hooks";
import { __ } from "@wordpress/i18n";
import { RiSettings3Line } from "react-icons/ri";

/**
 * Shortcut from the V4 lists to the Settings screen (`?tab=animation-settings`).
 *
 * That screen is where the site-wide V4 chrome lives — Preloader, Cursor,
 * Scroll to Top, Scroll Indicator, Popup, plus the Performance and GSAP Library
 * tabs — so it is the natural next stop after switching atomic widgets on, and
 * a shortcut beats going back up to the main menu.
 *
 * It also closes most of the gap the visibility rules opened: a site with v3
 * switches ON has the Settings item hidden from the menu
 * (lib/systemVisibility.js), and this is the only in-dashboard route left to it.
 * Still uncovered is a site with NO V4 tab at all — there is no V4 view to hang
 * this on there.
 *
 * Not to be confused with LegacyRevealLink, which sits beside it and does the
 * opposite job: that one reveals the V3 LIST and opens no settings at all.
 *
 * LOW EMPHASIS, matching its neighbour — muted 12px text, no button.
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
