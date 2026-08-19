import { useState } from "react";
import { __ } from "@wordpress/i18n";
import { RiSparkling2Line } from "react-icons/ri";
import { toast } from "sonner";

import { SHOW_TRY_ATOMIC_LINK } from "@/lib/systemVisibility";
import { goToWidgets, recordAtomicOptIn } from "@/lib/atomicOptIn";

/**
 * "Try Elementor V4" — the way back IN, and the mirror of `BackToV3Link`.
 *
 * WHY IT EXISTS. Dismissing the opt-in notice is a one-way door without it.
 * The ✕ records a real answer (a dismissal that only hid the bar until the
 * next load would be a nag), so the notice never returns for that signal — and
 * with the V4 tab still hidden there is then no route into the atomic registry
 * at all short of typing `?system=atomic`. Somebody who says "not now" and
 * means it should not have to know a query string exists to change their mind.
 *
 * The asymmetry is what makes it worth shipping rather than merely nice: we
 * added a visible **Back to V3** and shipped no visible way forward, so the
 * feature read as easier to leave than to enter.
 *
 * WHEN IT SHOWS is `SHOW_TRY_ATOMIC_LINK`, and it is owned by
 * `lib/systemVisibility.js` rather than by this component — the Extensions page
 * needs the same answer to decide whether to render the tab ROW at all, and a
 * link that gated only itself was mounted inside a row that site never built.
 *
 * ONE CLICK, NO DIALOG — unlike `BackToV3Link`. That one asks first because it
 * WRITES: it can empty both atomic options, and the dialog exists to say which
 * of its three modes is about to run. This writes nothing but the stored answer
 * and switches nothing on, so there is nothing to warn about — and it is
 * reversible by the very link it reveals. Ceremony in front of a reversible,
 * non-destructive reveal reads as a warning the action has not earned.
 *
 * It records `accepted`, which is exactly what **Choose manually** records:
 * same outcome, same one line in the option, reached from the other direction.
 *
 * DELIBERATELY LOW EMPHASIS, matching Legacy (V3), Settings and Back to V3 —
 * 12px muted text, dotted underline, no button. Styled up it would re-advertise
 * an offer this person has already declined once, which is the definition of a
 * nag.
 */
const TryAtomicLink = () => {
  const [working, setWorking] = useState(false);

  if (!SHOW_TRY_ATOMIC_LINK) return null;

  const reveal = async (event) => {
    event.preventDefault();

    if (working) return;
    setWorking(true);

    try {
      await recordAtomicOptIn("accepted");

      // Full reload — see goToWidgets() for why the in-app route cannot show
      // a tab this click just earned.
      goToWidgets("atomic");
    } catch (error) {
      setWorking(false);
      toast.error(
        __(
          "Could not open the Elementor V4 list.",
          "animation-addons-for-elementor"
        ),
        { position: "top-right" }
      );
    }
  };

  return (
    <a
      href="#"
      onClick={reveal}
      data-aae-try-atomic
      title={__(
        "Show Animation Addons' widgets and extensions for Elementor V4 (Atomic)",
        "animation-addons-for-elementor"
      )}
      className="inline-flex items-center gap-1 text-xs text-text-secondary hover:text-text-secondary-hover underline underline-offset-2 decoration-dotted"
    >
      <RiSparkling2Line size={13} />
      {__("Try Elementor V4", "animation-addons-for-elementor")}
    </a>
  );
};

export default TryAtomicLink;
