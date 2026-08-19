import { useState } from "react";
import { __ } from "@wordpress/i18n";
import { RiArrowGoBackLine, RiLoader4Line, RiShieldCheckLine } from "react-icons/ri";
import { toast } from "sonner";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { ATOMIC_ACTIVE, ATOMIC_UNDO_AVAILABLE } from "@/lib/systemVisibility";
import { answerAtomicUndo, goToWidgets } from "@/lib/atomicOptIn";

/**
 * The permanent way back for a site that took the atomic offer.
 *
 * `AtomicUndoNotice` covers the accident noticed straight away, but it is
 * snapshot-based and therefore temporary: it ends when the user saves either
 * atomic list by hand, after seven days, and it never existed at all for anyone
 * who accepted before that snapshot was being taken. A return path that can
 * expire is not a return path — CLAUDE.md's Rule 6 is "always ship the manual
 * escape hatch", and this is it.
 *
 * THREE MODES, and the dialog says which one it is about to do, because they
 * do different amounts of work and the user is entitled to know:
 *
 *  - A snapshot is on file → **restore** it. Exact, down to option rows that
 *    had never existed.
 *  - No snapshot, widgets on → **switch off**. Both atomic options are written
 *    empty. Not an exact restore, and it does not claim to be; nothing is
 *    deleted, so it can never re-arm a migration behind the user's back (see
 *    `Atomic::ajax_atomic_optin_undo()`).
 *  - Nothing on at all → **hide the tab**. This is the "Choose manually" shape:
 *    the offer was accepted and no widget was ever enabled, so clearing the
 *    stored answer is the whole reversal and the server writes nothing.
 *
 * NOT STYLED AS A WARNING, unlike `DisableAllV3Dialog`. That one guards the
 * action that can blank a live page — an unregistered v3 widget renders
 * nothing. This one cannot: switching the atomic set off touches no v3 option,
 * and the V4 pages it does affect are the ones the user is telling us they
 * never meant to build on. Dressing it in red would borrow alarm it has not
 * earned, and the reassuring fact — that their V3 site was never modified — is
 * the main thing the dialog is here to say.
 *
 * @param {boolean}  open
 * @param {Function} setOpen
 */
const BackToV3Dialog = ({ open, setOpen }) => {
  const [working, setWorking] = useState(false);

  const confirm = async () => {
    setWorking(true);

    try {
      await answerAtomicUndo(ATOMIC_UNDO_AVAILABLE ? "undo" : "off");

      // Full reload onto the V3 list — see goToWidgets() for why the in-app
      // route cannot show this.
      goToWidgets("v3");
    } catch (error) {
      setWorking(false);
      setOpen(false);
      toast.error(
        __(
          "Could not switch the atomic features off.",
          "animation-addons-for-elementor"
        ),
        { position: "top-right" }
      );
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      {/*
        DialogContent ships `bg-transparent` — a caller that passes no
        background renders as floating controls over the overlay.
      */}
      <DialogContent className="w-[460px] bg-background rounded-2xl overflow-hidden shadow-auth-card">
        <div className="p-6">
          <DialogHeader>
            <div className="flex items-start gap-3.5">
              <span className="shrink-0 h-11 w-11 rounded-full bg-background-secondary border flex items-center justify-center text-text-secondary">
                <RiArrowGoBackLine size={20} />
              </span>
              <div className="flex flex-col gap-1 pt-0.5">
                <DialogTitle className="text-base font-medium text-start leading-snug">
                  {__(
                    "Go back to Elementor V3",
                    "animation-addons-for-elementor"
                  )}
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {ATOMIC_UNDO_AVAILABLE
                    ? __(
                        "Animation Addons' atomic widgets and extensions go back exactly as they were before you switched them on.",
                        "animation-addons-for-elementor"
                      )
                    : ATOMIC_ACTIVE
                      ? __(
                          "Animation Addons' atomic widgets and extensions are switched off, and the V3 list comes back.",
                          "animation-addons-for-elementor"
                        )
                      : __(
                          "The Elementor V4 (Atomic) tab is hidden again and the V3 list comes back. Nothing is switched off, because nothing was ever switched on.",
                          "animation-addons-for-elementor"
                        )}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          {/*
            The reassurance IS the content here — it is the question somebody
            opening this dialog is actually asking — so it gets the tinted block
            rather than a warning.
          */}
          <div className="mt-5 rounded-xl bg-background-secondary border p-4 text-sm text-text-secondary">
            <p className="flex items-start gap-2">
              <span className="flex shrink-0 mt-0.5 text-text">
                <RiShieldCheckLine size={16} />
              </span>
              {__(
                "Your Elementor V3 widgets, extensions and pages were never changed — switching the atomic set on did not touch them, and switching it off will not either.",
                "animation-addons-for-elementor"
              )}
            </p>
          </div>

          {ATOMIC_ACTIVE && (
            <p className="mt-4 text-xs text-text-secondary">
              {__(
                "Any V4 pages you have already built keep their content. The atomic widgets on them stop rendering until you switch them back on.",
                "animation-addons-for-elementor"
              )}
            </p>
          )}
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            disabled={working}
            data-aae-back-to-v3-cancel
          >
            {__("Cancel", "animation-addons-for-elementor")}
          </Button>
          <Button
            onClick={confirm}
            disabled={working}
            data-aae-back-to-v3-confirm
          >
            {working && (
              <RiLoader4Line className="me-1.5 animate-spin" size={15} />
            )}
            {__("Back to V3", "animation-addons-for-elementor")}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default BackToV3Dialog;
