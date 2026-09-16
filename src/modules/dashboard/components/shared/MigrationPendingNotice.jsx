import { useTNavigation } from "@/hooks/app.hooks";
import { __ } from "@wordpress/i18n";
import { RiExchangeLine } from "react-icons/ri";

/**
 * One line at the top of the Dashboard, Widgets and Extensions screens while
 * the 4.2 storage-name migration is waiting for consent (or needs a retry).
 *
 * The wp-admin notice the Notices framework prints never reaches these
 * screens: the dashboard removes every admin notice from its own chrome
 * (`Aaeaddon_Admin_Init::remove_all_notices()`), and WordPress.org guideline 11
 * keeps it off unrelated screens anyway. So the pointer has to live INSIDE the
 * app — here — plus the sidebar item and the plugin-row line on the Plugins
 * screen. The Migration page itself carries the full explanation; this says
 * only that the site is fine and where to go.
 *
 * Reads the status the page shipped with (a snapshot, like every other rule
 * on these screens) and renders nothing on a fresh or completed site.
 */
const STATUS = window.WCF_ADDONS_ADMIN?.addons_config?.migration?.status;

const MigrationPendingNotice = () => {
  const { setTabKey } = useTNavigation();
  if (STATUS !== "awaiting_consent" && STATUS !== "needs_action") return null;

  const open = (event) => {
    event.preventDefault();
    const url = new URL(window.location.href);
    const pageQuery = url.searchParams.get("page");
    url.search = `page=${pageQuery}`;
    url.hash = "";
    url.searchParams.set("tab", "migration");
    window.history.replaceState({}, "", url);
    setTabKey("migration");
  };

  return (
    <div
      data-aae-migration-pending-notice
      className="flex flex-wrap items-center gap-2 rounded-lg border border-[#FFE3A3] bg-[#FFF8EB] px-4 py-3 text-sm text-[var(--900,#181B25)]"
    >
      <RiExchangeLine size={16} className="shrink-0" />
      <span>
        {STATUS === "needs_action"
          ? __(
              "The storage-name migration needs your attention. Your site is working normally.",
              "animation-addons-for-elementor",
            )
          : __(
              "Animation Addons moved its settings to new storage names. Your site is working normally — nothing is copied until you start the migration, and nothing is deleted.",
              "animation-addons-for-elementor",
            )}
      </span>
      <a
        href="#"
        onClick={open}
        className="ml-auto font-medium text-brand underline underline-offset-2 decoration-dotted"
      >
        {__("Open migration", "animation-addons-for-elementor")}
      </a>
    </div>
  );
};

export default MigrationPendingNotice;
