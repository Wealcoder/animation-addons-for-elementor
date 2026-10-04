import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "../ui/button";
import { RiAlertLine, RiArrowGoBackLine, RiFileWarningLine } from "react-icons/ri";
import { V3_IN_USE } from "@/lib/systemVisibility";
import { __, sprintf, _n } from "@wordpress/i18n";

/**
 * Confirm before switching EVERY v3 widget or extension off.
 *
 * This is not an "are you sure?" for politeness. Turning the master switch off
 * unregisters the widgets, and **an unregistered widget renders nothing** — no
 * error, no notice, no placeholder, because Elementor emits not even a wrapper
 * for a widget type it does not know. A page built from them goes blank, and
 * nothing in the dashboard says why. It is the exact failure CLAUDE.md's DANGER
 * box is about, and the one case where the dashboard can destroy a live page's
 * output with one click.
 *
 * `maybe_enable_used_v3_widgets()` will NOT undo it either: that heals a site
 * whose option was never written, and an explicit all-off is a written choice.
 *
 * ── Why it is laid out this way ────────────────────────────────────────────
 *
 * The first version inverted its own emphasis: the frightening sentence ("pages
 * will render empty") was plain body text while the merely-contextual one sat
 * in a tinted box. They are now one callout, tinted danger, with the site's own
 * situation as its last and strongest line — a warning should look most like a
 * warning at the point it is most specific.
 *
 * Everything else is deliberately quiet so that callout carries the weight: the
 * count is a subtitle, the reassurance is a muted footnote, and the panel keeps
 * the badge/rounded/shadow idiom the rest of the dashboard already uses. The
 * only saturated things on screen are the alert badge and the confirm button.
 *
 * @param {boolean}  open        Dialog visibility.
 * @param {Function} setOpen     Visibility setter.
 * @param {Function} onConfirm   Applied only if the user goes ahead.
 * @param {string}   kind        'widgets' | 'extensions' — what is being disabled.
 * @param {number}   activeCount How many are on right now; makes the cost concrete.
 */
const DisableAllV3Dialog = ({
  open,
  setOpen,
  onConfirm,
  kind = "widgets",
  activeCount = 0,
}) => {
  const isWidgets = kind === "widgets";

  const title = isWidgets
    ? __("Turn off all Elementor V3 widgets?", "animation-addons-for-elementor")
    : __(
        "Turn off all Elementor V3 extensions?",
        "animation-addons-for-elementor"
      );

  const subtitle = isWidgets
    ? sprintf(
        /* translators: %d: number of active V3 widgets. */
        _n(
          "%d active widget will stop being registered.",
          "%d active widgets will stop being registered.",
          activeCount,
          "animation-addons-for-elementor"
        ),
        activeCount
      )
    : sprintf(
        /* translators: %d: number of active V3 extensions. */
        _n(
          "%d active extension will stop loading.",
          "%d active extensions will stop loading.",
          activeCount,
          "animation-addons-for-elementor"
        ),
        activeCount
      );

  // Widgets and extensions fail differently, so they are warned about
  // differently: an unregistered WIDGET erases content from the page, while an
  // extension only stops the effects it added from running.
  const consequence = isWidgets
    ? __(
        "Any page built with them will render those sections as empty — with no error or notice to explain it.",
        "animation-addons-for-elementor"
      )
    : __(
        "Effects already saved on your pages will stop running.",
        "animation-addons-for-elementor"
      );

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
              <span className="shrink-0 h-11 w-11 rounded-full bg-[#FEF3F2] border border-[#FEE4E2] flex items-center justify-center text-[#D92D20]">
                <RiAlertLine size={22} />
              </span>
              <div className="flex flex-col gap-1 pt-0.5">
                <DialogTitle className="text-base font-medium text-start leading-snug">
                  {title}
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {subtitle}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          {/*
            The callout is the point of the dialog, so it is the only tinted
            block — and the in-use line goes INSIDE it rather than in a box of
            its own, which is what made the old layout read backwards.
          */}
          <div className="mt-5 rounded-xl bg-[#FEF3F2] border border-[#FEE4E2] p-4 text-sm text-[#912018]">
            <p>{consequence}</p>

            {isWidgets && V3_IN_USE && (
              <p
                data-aae-v3-inuse-warning
                className="mt-2.5 pt-2.5 border-t border-[#FEE4E2] flex items-start gap-2 font-medium"
              >
                <span className="flex shrink-0 mt-0.5">
                  <RiFileWarningLine size={16} />
                </span>
                {__(
                  "This site has pages using V3 widgets right now.",
                  "animation-addons-for-elementor"
                )}
              </p>
            )}
          </div>

          <p className="mt-4 flex items-start gap-2 text-xs text-text-secondary">
            <span className="flex shrink-0 mt-0.5">
              <RiArrowGoBackLine size={14} />
            </span>
            {__(
              "Nothing is deleted. Switching them back on restores every page.",
              "animation-addons-for-elementor"
            )}
          </p>
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            data-aae-disable-all-cancel
          >
            {__("Cancel", "animation-addons-for-elementor")}
          </Button>
          {/*
            No `destructive` variant exists in ui/button.jsx and this is not the
            place to invent one — a shared variant is a design-system decision,
            not a side effect of one dialog. Red is applied inline instead.
          */}
          <Button
            className="bg-[#D92D20] text-white hover:bg-[#B42318] hover:text-white"
            data-aae-disable-all-confirm
            onClick={() => {
              setOpen(false);
              onConfirm();
            }}
          >
            {isWidgets
              ? __("Turn off widgets", "animation-addons-for-elementor")
              : __("Turn off extensions", "animation-addons-for-elementor")}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default DisableAllV3Dialog;
