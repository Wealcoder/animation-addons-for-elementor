import { useState } from "react";
import { __, sprintf, _n } from "@wordpress/i18n";
import { RiCloseLine, RiLoader4Line, RiMagicLine } from "react-icons/ri";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { useAtomicExtensions, useAtomicWidgets } from "@/hooks/app.hooks";
import { countAtomicWidgets } from "@/lib/atomicWidgetService";
import { countAtomicExtensions } from "@/lib/atomicExtensionService";
import {
  ATOMIC_EXPERIMENT_ON,
  ATOMIC_IN_USE_COUNT,
  ATOMIC_SIGNAL,
  SHOW_ATOMIC_OPTIN_NOTICE,
} from "@/lib/systemVisibility";
import { goToWidgets, recordAtomicOptIn } from "@/lib/atomicOptIn";

/**
 * "This site is on Elementor V4 — Animation Addons has a set for that."
 *
 * THE PROBLEM IT SOLVES. The dashboard hides the era a site does not use
 * (`lib/systemVisibility.js`), and it decides "does not use V4" from whether
 * anything atomic is switched on. That is right for a settled site and blind at
 * exactly the wrong moment: someone who has just turned Elementor's V4 on has
 * nothing atomic switched on yet — that is what arriving looks like — so the
 * tab that would introduce AAE's atomic registry is hidden from the one person
 * actively looking for it. This notice is the way in, and it is the ONLY new
 * route: nothing about the V3 lists, the V3 toggles or the existing visibility
 * rules changes, on this screen or any other.
 *
 * WHERE IT SHOWS. Above everything else on the Dashboard, Widgets and
 * Extensions screens. All three, deliberately: the Widgets and Extensions pages
 * are where somebody stands when they go looking for atomic widgets and cannot
 * find them, so a notice that only lived on the Dashboard would be missing from
 * the two screens where the question is actually being asked.
 *
 * WHEN IT SHOWS. `Atomic::atomic_optin_signal()` owns every condition (a V4
 * signal exists, nothing atomic is active yet, and the user has not already
 * answered at this signal strength). Do not add a second condition here.
 *
 * THE TWO SIGNALS ARE NOT THE SAME CLAIM, so they do not get the same sentence:
 *
 *   - `usage`      — V4 elements are saved in this site's pages. A fact about
 *                    their work, and the stronger reason to care.
 *   - `experiment` — Elementor's V4 switch is on. A fact about a setting.
 *
 * Saying the second while the first is true reads as though we had not looked.
 *
 * AND THEY CAN BOTH BE TRUE, which gets its own line rather than being collapsed
 * into the stronger one. "You switched V4 on and you are already building with
 * it" is a different, more specific observation than either half, and it is the
 * commonest case — someone turns the experiment on and then uses it.
 * ATOMIC_SIGNAL alone cannot express it: it carries only the strongest signal,
 * because that is what the ranked dismissal has to compare against.
 *
 * THE TWO BUTTONS.
 *
 *   - **Enable** writes the recommended set through the same endpoints the
 *     Widgets list saves through, then reloads onto the atomic list.
 *   - **Choose manually** enables NOTHING and only records the answer, which is
 *     enough to reveal the V4 tab (`ATOMIC_OPTED_IN`). It exists because
 *     "switch on ~100 widgets for me" is a reasonable thing to decline, and
 *     declining it should not also cost you the list.
 *
 * Both count as `accepted`: both mean this person asked to see the atomic
 * lists. Only the ✕ records a dismissal.
 *
 * NEITHER IS ONE-WAY. Enable takes a snapshot of the previous atomic state
 * first, and `AtomicUndoNotice` offers it back — a click that changes a hundred
 * things has to be reversible. Nothing v3 is read or written on this path at
 * all, so even before the undo, an accidental accept could not cost anyone
 * their existing site.
 */

/**
 * The opening sentence -- the whole reason the notice is on screen.
 *
 * THE COUNT IS THE POINT WHEN WE HAVE IT. "Pages on this site are built with
 * V4" is equally true of one abandoned test page and of a site that has moved
 * wholesale, and those two readers want opposite things from this notice. The
 * number is the entire difference, so it is stated rather than implied.
 *
 * A count of 0 means PHP DID NOT COUNT, not "none" -- it only pays for that
 * query when a notice is going on screen and the signal is usage -- so the
 * countless wording is kept as a real fallback rather than treated as dead
 * code. If it ever renders, it is still true.
 *
 * Each count variant is a WHOLE sentence through _n() rather than a noun
 * phrase interpolated into one. "%s pages ... are built" with a stitched-in
 * "1 page" reads as broken English in English and is unfixable in a language
 * whose verb agreement differs from ours -- the translator needs the sentence.
 */
const leadLine = () => {
  if ("usage" !== ATOMIC_SIGNAL) {
    return __(
      "Elementor's V4 atomic elements are available in your editor.",
      "animation-addons-for-elementor"
    );
  }

  if (ATOMIC_IN_USE_COUNT > 0) {
    return sprintf(
      ATOMIC_EXPERIMENT_ON
        ? _n(
            /* translators: %s: number of pages. */
            "Elementor V4 (Atomic) is switched on, and %s page on this site is already built with its elements.",
            "Elementor V4 (Atomic) is switched on, and %s pages on this site are already built with its elements.",
            ATOMIC_IN_USE_COUNT,
            "animation-addons-for-elementor"
          )
        : _n(
            /* translators: %s: number of pages. */
            "%s page on this site is built with Elementor's V4 atomic elements.",
            "%s pages on this site are built with Elementor's V4 atomic elements.",
            ATOMIC_IN_USE_COUNT,
            "animation-addons-for-elementor"
          ),
      ATOMIC_IN_USE_COUNT.toLocaleString()
    );
  }

  return ATOMIC_EXPERIMENT_ON
    ? __(
        "Elementor V4 (Atomic) is switched on, and pages on this site are already built with its elements.",
        "animation-addons-for-elementor"
      )
    : __(
        "Pages on this site are built with Elementor's V4 atomic elements.",
        "animation-addons-for-elementor"
      );
};

const AtomicOptInNotice = () => {
  // A snapshot, like every other rule in systemVisibility.js. `answered` is the
  // local half — the notice has to leave the screen on click, and the payload
  // that would agree does not arrive until the next page load.
  const [answered, setAnswered] = useState(false);
  const [working, setWorking] = useState("");

  const { allAtomicWidgets } = useAtomicWidgets();
  const { allAtomicExtensions } = useAtomicExtensions();

  if (!SHOW_ATOMIC_OPTIN_NOTICE || answered) return null;

  const widgetCount = countAtomicWidgets(allAtomicWidgets).total;
  const extensionCount = countAtomicExtensions(allAtomicExtensions).total;

  const enable = async () => {
    setWorking("enable");

    try {
      /*
       * ONE request, which is what makes the undo possible: PHP has to take its
       * snapshot and write both options together, because the ordinary save
       * endpoints deliberately END the undo offer. It also means this can never
       * half-land — widgets on, extensions off, no decision recorded — from a
       * tab closed mid-flight.
       */
      await recordAtomicOptIn("accepted", {
        widgets: allAtomicWidgets,
        extensions: allAtomicExtensions,
      });

      // Reload rather than re-render: see goToWidgets().
      goToWidgets("atomic");
    } catch (error) {
      setWorking("");
      toast.error(
        __(
          "Could not switch the atomic features on.",
          "animation-addons-for-elementor"
        ),
        { position: "top-right" }
      );
    }
  };

  const chooseManually = async () => {
    setWorking("manual");

    try {
      await recordAtomicOptIn("accepted");

      goToWidgets("atomic");
    } catch (error) {
      setWorking("");
      toast.error(
        __("Could not open the atomic widgets.", "animation-addons-for-elementor"),
        { position: "top-right" }
      );
    }
  };

  /*
   * Dismissal is optimistic — the notice goes as soon as it is clicked, and a
   * failed write only means it is offered again next time. Blocking a dismiss
   * on a round trip would make "no thanks" the slowest thing on the screen.
   */
  const dismiss = () => {
    setAnswered(true);
    recordAtomicOptIn("dismissed").catch(() => {});
  };

  const headline =
    "usage" === ATOMIC_SIGNAL
      ? __(
          "This site is already using Elementor V4 (Atomic)",
          "animation-addons-for-elementor"
        )
      : __(
          "Elementor V4 (Atomic) is switched on for this site",
          "animation-addons-for-elementor"
        );

  const lead = leadLine();

  return (
    <div
      data-aae-atomic-optin-notice
      data-aae-atomic-signal={ATOMIC_SIGNAL}
      className="relative flex flex-col gap-3 rounded-[10px] border bg-background-secondary p-5 sm:flex-row sm:items-center sm:gap-5"
    >
      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-button text-button-text">
        <RiMagicLine size={20} />
      </span>

      <div className="flex flex-col gap-1 pe-8">
        <p className="text-base font-semibold text-text">{headline}</p>
        <p className="text-sm text-text-secondary">
          {lead}{" "}
          {sprintf(
            /* translators: 1: number of atomic widgets, 2: number of atomic extensions. */
            __(
              "Animation Addons ships %1$s and %2$s built for them, and none of them are switched on yet.",
              "animation-addons-for-elementor"
            ),
            sprintf(
              /* translators: %s: number of atomic widgets. */
              _n(
                "%s atomic widget",
                "%s atomic widgets",
                widgetCount,
                "animation-addons-for-elementor"
              ),
              widgetCount
            ),
            sprintf(
              /* translators: %s: number of atomic extensions. */
              _n(
                "%s atomic extension",
                "%s atomic extensions",
                extensionCount,
                "animation-addons-for-elementor"
              ),
              extensionCount
            )
          )}
        </p>
      </div>

      <div className="flex shrink-0 items-center gap-3 sm:ms-auto">
        <Button onClick={enable} disabled={!!working} size="sm">
          {"enable" === working && (
            <RiLoader4Line className="me-1.5 animate-spin" size={15} />
          )}
          {__("Enable atomic features", "animation-addons-for-elementor")}
        </Button>

        <button
          type="button"
          onClick={chooseManually}
          disabled={!!working}
          className="text-xs text-text-secondary underline decoration-dotted underline-offset-2 hover:text-text-secondary-hover disabled:opacity-50"
        >
          {__("Choose manually", "animation-addons-for-elementor")}
        </button>
      </div>

      <button
        type="button"
        onClick={dismiss}
        aria-label={__("Dismiss", "animation-addons-for-elementor")}
        className="absolute end-3 top-3 flex h-6 w-6 items-center justify-center rounded-full text-text-secondary hover:text-text-secondary-hover"
      >
        <RiCloseLine size={16} />
      </button>
    </div>
  );
};

export default AtomicOptInNotice;
