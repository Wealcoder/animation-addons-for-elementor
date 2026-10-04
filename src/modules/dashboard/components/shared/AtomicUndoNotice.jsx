import { useState } from "react";
import { __, sprintf, _n } from "@wordpress/i18n";
import { RiArrowGoBackLine, RiCloseLine, RiLoader4Line } from "react-icons/ri";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import {
  ATOMIC_UNDO_AVAILABLE,
  ATOMIC_UNDO_COUNTS,
} from "@/lib/systemVisibility";
import { answerAtomicUndo, goToWidgets } from "@/lib/atomicOptIn";

/**
 * The way back out of "Enable atomic features".
 *
 * That button switches on everything this site can register in one click. It is
 * the right shape for the offer — and precisely the shape somebody can press by
 * mistake, so it does not get to be one-way.
 *
 * WHAT "BACK" MEANS HERE. Not "switch everything off": the site had a STATE
 * before the click (usually no option rows at all, sometimes a half-built one
 * from an abandoned wizard run) and only restoring that exact state puts the
 * user where they were. PHP snapshots it before the first write and this
 * restores it, down to DELETING the rows that had never existed — an empty
 * `aae_atomic_extensions` is a different site from a missing one (see
 * `Atomic::UNDO_OPTION_NAME`).
 *
 * WHAT WAS NEVER AT RISK, and the notice says so. Accepting the offer does not
 * read or write a single v3 option, so an accidental accept never cost anyone
 * their V3 site — their widgets stayed registered and their pages kept
 * rendering throughout. Someone who has just realised they pressed the wrong
 * button is asking exactly that question, and the answer is reassuring, so it
 * belongs in the copy rather than in a docblock only we will read.
 *
 * WHEN IT SHOWS. `Atomic::atomic_undo_offer()` owns the rule — a snapshot is on
 * file and still inside its window. The offer ends by itself the moment the
 * user saves either atomic list by hand, because from then on the stored state
 * is their own work.
 *
 * The ✕ is not a "hide": it records **keep**, which is a real answer, and ends
 * the offer for good. A dismissal that only hid the bar until the next page
 * load would make it a nag.
 */
const AtomicUndoNotice = () => {
  const [answered, setAnswered] = useState(false);
  const [working, setWorking] = useState("");

  if (!ATOMIC_UNDO_AVAILABLE || answered) return null;

  const undo = async () => {
    setWorking("undo");

    try {
      await answerAtomicUndo("undo");

      // Full reload, and back to the V3 list — that is where someone who just
      // took this back was standing. See goToWidgets().
      goToWidgets("v3");
    } catch (error) {
      setWorking("");
      toast.error(
        __(
          "Could not switch the atomic features back off.",
          "animation-addons-for-elementor"
        ),
        { position: "top-right" }
      );
    }
  };

  /*
   * Optimistic, like the opt-in notice's dismiss: "I meant to do this" should
   * be the fastest thing on the screen, and a failed write only means the offer
   * is made once more.
   */
  const keep = () => {
    setAnswered(true);
    answerAtomicUndo("keep").catch(() => {});
  };

  return (
    <div
      data-aae-atomic-undo-notice
      className="relative flex flex-col gap-3 rounded-[10px] border bg-background-secondary p-5 sm:flex-row sm:items-center sm:gap-5"
    >
      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-button-secondary text-text-secondary">
        <RiArrowGoBackLine size={18} />
      </span>

      <div className="flex flex-col gap-1 pe-8">
        <p className="text-base font-semibold text-text">
          {__(
            "Atomic features are switched on",
            "animation-addons-for-elementor"
          )}
        </p>
        <p className="text-sm text-text-secondary">
          {sprintf(
            /* translators: 1: number of atomic widgets, 2: number of atomic extensions. */
            __(
              "%1$s and %2$s are active for Elementor V4. Your Elementor V3 widgets and extensions were not changed.",
              "animation-addons-for-elementor"
            ),
            sprintf(
              /* translators: %s: number of atomic widgets. */
              _n(
                "%s atomic widget",
                "%s atomic widgets",
                ATOMIC_UNDO_COUNTS.widgets,
                "animation-addons-for-elementor"
              ),
              ATOMIC_UNDO_COUNTS.widgets
            ),
            sprintf(
              /* translators: %s: number of atomic extensions. */
              _n(
                "%s atomic extension",
                "%s atomic extensions",
                ATOMIC_UNDO_COUNTS.extensions,
                "animation-addons-for-elementor"
              ),
              ATOMIC_UNDO_COUNTS.extensions
            )
          )}
        </p>
      </div>

      <div className="flex shrink-0 items-center gap-3 sm:ms-auto">
        <Button
          onClick={undo}
          disabled={!!working}
          size="sm"
          variant="secondary"
        >
          {"undo" === working && (
            <RiLoader4Line className="me-1.5 animate-spin" size={15} />
          )}
          {__("Undo", "animation-addons-for-elementor")}
        </Button>

        <button
          type="button"
          onClick={keep}
          disabled={!!working}
          className="text-xs text-text-secondary underline decoration-dotted underline-offset-2 hover:text-text-secondary-hover disabled:opacity-50"
        >
          {__("Keep them on", "animation-addons-for-elementor")}
        </button>
      </div>

      <button
        type="button"
        onClick={keep}
        aria-label={__("Keep them on", "animation-addons-for-elementor")}
        className="absolute end-3 top-3 flex h-6 w-6 items-center justify-center rounded-full text-text-secondary hover:text-text-secondary-hover"
      >
        <RiCloseLine size={16} />
      </button>
    </div>
  );
};

export default AtomicUndoNotice;
