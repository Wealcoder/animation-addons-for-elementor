import { RiInformationLine } from "react-icons/ri";
import { V3_IN_USE } from "@/lib/systemVisibility";
import { __ } from "@wordpress/i18n";

/**
 * One quiet line on the V4 widget list: this site's pages still use V3 widgets.
 *
 * Someone working entirely in the atomic list has no way to know that, and it
 * is the fact that makes the V3 switches dangerous — turning them off blanks
 * those pages (see DisableAllV3Dialog). Saying it once, where the person is
 * actually standing, is what turns that dialog from a surprise into a
 * confirmation of something they already knew.
 *
 * ONE LINE, LOW EMPHASIS — no card, no icon badge, no dismiss button. It is
 * context, not a task: nothing is wrong, nothing needs doing, and a notice
 * styled as an alert would say otherwise every time the page is opened.
 *
 * Renders nothing when no v3 content is detected (`V3_IN_USE`, from
 * `Animation_Settings::has_v3_usage()`), so a genuinely v4-only site never sees
 * a line about an era it does not use.
 */
const V3InUseNotice = () => {
  if (!V3_IN_USE) return null;

  return (
    <p
      data-aae-v3-inuse-notice
      className="flex items-center gap-1.5 text-xs text-text-secondary"
    >
      <span className="flex shrink-0">
        <RiInformationLine size={14} />
      </span>
      {__(
        "Some pages on this site still use Elementor V3 widgets.",
        "animation-addons-for-elementor"
      )}
    </p>
  );
};

export default V3InUseNotice;
