import { useState } from "react";
import { __ } from "@wordpress/i18n";
import { RiArrowGoBackLine } from "react-icons/ri";

import BackToV3Dialog from "@/components/shared/BackToV3Dialog";
import { ATOMIC_OPTED_IN, V3_PRESENT } from "@/lib/systemVisibility";

/**
 * "Back to V3" — the permanent escape hatch on the V4 tab row.
 *
 * WHY IT EXISTS BESIDE THE V3 TAB. The tab switches which list you are LOOKING
 * at; this switches the atomic set back OFF and returns you to V3. Someone who
 * moved by mistake wants the second and finds only the first, which reads as
 * "there is no way back". Same distinction `LegacyRevealLink` and
 * `SettingsQuickLink` already keep from each other — three low-emphasis links
 * on one row, none of them doing another's job.
 *
 * WHEN IT SHOWS. Two conditions:
 *
 *  - `ATOMIC_OPTED_IN` — this site took the notice's offer. A site that has
 *    always run both eras (from the wizard, say) never asked to move and should
 *    not find a new "go back" control appearing on it.
 *  - `V3_PRESENT` — there is a V3 to go back TO. On a V4-only site this offers
 *    to leave the user with no widgets at all, which is not an escape hatch.
 *
 * DELIBERATELY **NOT** gated on anything being switched on. That was the first
 * version and it hid the link from the exact person who needs it: "Choose
 * manually" accepts the offer and enables NOTHING, so the V4 tab appears with
 * 0 active widgets — a real change to the dashboard, with no snapshot to undo
 * (nothing was written) and, under an is-anything-active gate, no way back
 * either. The thing being reversed is the OPT-IN, not the widget count.
 *
 * Unlike `AtomicUndoNotice` it never expires, which is the point: the undo bar
 * is snapshot-based and temporary, and a return path that can time out is not
 * one. The dialog handles the difference — exact restore while a snapshot
 * exists, plain switch-off after.
 *
 * DELIBERATELY LOW EMPHASIS, matching its two neighbours — 12px muted text,
 * dotted underline, no button. It is a way out for the few who go looking, and
 * styled up it would read as advice to leave.
 */
const BackToV3Link = () => {
  const [open, setOpen] = useState(false);

  if (!ATOMIC_OPTED_IN || !V3_PRESENT) return null;

  return (
    <>
      <a
        href="#"
        onClick={(event) => {
          event.preventDefault();
          setOpen(true);
        }}
        data-aae-back-to-v3
        title={__(
          "Switch Animation Addons' atomic set off and return to the Elementor V3 list",
          "animation-addons-for-elementor"
        )}
        className="inline-flex items-center gap-1 text-xs text-text-secondary hover:text-text-secondary-hover underline underline-offset-2 decoration-dotted"
      >
        <RiArrowGoBackLine size={13} />
        {__("Back to V3", "animation-addons-for-elementor")}
      </a>

      <BackToV3Dialog open={open} setOpen={setOpen} />
    </>
  );
};

export default BackToV3Link;
