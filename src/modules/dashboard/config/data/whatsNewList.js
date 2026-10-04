/**
 * The "What's New" card's copy. The version number itself is read live from
 * WCF_ADDONS_ADMIN.version, so only the date and the highlight notes below
 * need updating on each release.
 *
 * WHY THIS IS NOT FETCHED from changelogUrl. That page is a separate WP
 * install (animation-addons.com/docs/, page id 14347) and it is reachable —
 * .../docs/wp-json/wp/v2/pages/14347 returns parseable version/date pairs.
 * It is still the wrong source: every version listed there is 2.x (2.5.5 up
 * to 2.6.6), while this plugin is 4.x, so there is no row to match
 * WCF_ADDONS_ADMIN.version against. Fetching it would print the live "V 4.1.0"
 * beside v2.6.6's date and notes — a wrong answer that looks authoritative.
 * Revisit once the changelog page actually carries 4.x releases.
 *
 * TODO: `notes` are still placeholders, not the shipped 4.1.0 changelog —
 * replace them with the real highlights. `date` is set by hand per release.
 */
export const WhatsNewData = {
  date: "30 August 2026",
  changelogUrl: "https://animation-addons.com/docs/changelogs/",
  notes: [
    "Security hardening across the whole plugin.",
    "Performance improvements and unused code removed.",
  ],
};
