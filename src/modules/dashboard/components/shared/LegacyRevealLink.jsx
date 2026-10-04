import { __ } from "@wordpress/i18n";
import { RiArrowRightSLine } from "react-icons/ri";

/**
 * Bring the hidden Elementor V3 tab back, from the V4 list.
 *
 * The dashboard hides the era a site does not use (lib/systemVisibility.js), so
 * a V4-only site has no V3 tab at all. `?system=v3` always worked as an escape
 * hatch, but a URL parameter is not something anyone discovers — this is the
 * visible one, and it lives on the V4 view precisely because that is where
 * someone stands when they cannot find V3.
 *
 * It does NOT open the Settings screen. An earlier revision linked there and
 * called itself "Legacy settings", which was wrong twice over: the destination
 * is the V4 settings home (Preloader, Cursor, Performance, Library — it merely
 * also carries the `legacy_v3` switch), and what someone looking for their old
 * widgets actually wants is the V3 list, not a settings page.
 *
 * DELIBERATELY LOW EMPHASIS — muted 12px text, no button, no card. It is a way
 * back for the few who go looking; styled up it would re-advertise the era this
 * site has already moved off, which is the opposite of why the tab was hidden.
 *
 * @param {Function} onReveal Called when clicked — the page reveals and selects
 *                            its V3 tab.
 */
const LegacyRevealLink = ({ onReveal }) => (
  <a
    href="#"
    onClick={(event) => {
      event.preventDefault();
      onReveal();
    }}
    data-aae-legacy-reveal
    title={__(
      "Show the Elementor V3 (legacy) list",
      "animation-addons-for-elementor"
    )}
    className="inline-flex items-center gap-0.5 text-xs text-text-secondary hover:text-text-secondary-hover underline underline-offset-2 decoration-dotted"
  >
    {__("Legacy (V3)", "animation-addons-for-elementor")}
    <RiArrowRightSLine size={14} />
  </a>
);

export default LegacyRevealLink;
