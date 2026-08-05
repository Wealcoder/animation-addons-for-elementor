import { Info } from "lucide-react";
import { __ } from "@wordpress/i18n";
import { useId, useState } from "react";

/**
 * Explanatory copy that stays out of the way until it is asked for.
 *
 * The settings screens carry a lot of it — every panel, every conditions field
 * and the Performance page itself explains what it does before showing a single
 * control — and read end to end that is a wall of grey text between the user and
 * the switch they came for. Behind an ⓘ the explanation is still one click away
 * for whoever needs it, and invisible to whoever does not.
 *
 * Two shapes, because the label row differs by call site:
 *
 * - `InfoToggle` is the bare icon button. Use it where the label already exists
 *   and has its own markup (a heading with a PRO badge next to it, say) — the
 *   caller owns the open state and renders the paragraph itself.
 * - `InfoNote` is the whole thing: label, icon and the disclosure below it. Use
 *   it anywhere the label is plain text, and from render helpers that cannot
 *   hold hooks of their own (FeaturePanel's renderField is called
 *   conditionally, so its branches must delegate state to a component).
 */

/**
 * The icon button on its own. `open` / `onToggle` are the caller's.
 *
 * @param {boolean}  open     Whether the description it controls is showing.
 * @param {Function} onToggle Called with the next open state.
 * @param {string}   controls id of the element being disclosed, for aria.
 * @param {string}   name     What the description is about, for the aria-label.
 */
export const InfoToggle = ({ open, onToggle, controls, name = "", testid }) => (
  <button
    type="button"
    className="inline-flex items-center justify-center shrink-0 rounded-full text-[var(--600,#525866)] hover:text-[var(--900,#181B25)] transition-colors cursor-pointer"
    aria-expanded={open}
    aria-controls={controls}
    aria-label={
      name
        ? // translators: %s is the label the description belongs to.
          `${__("About", "animation-addons-for-elementor")} ${name}`
        : __("Show description", "animation-addons-for-elementor")
    }
    data-aae-info={testid}
    data-aae-info-open={open ? "true" : "false"}
    onClick={() => onToggle(!open)}
  >
    <Info className="w-[15px] h-[15px]" aria-hidden="true" />
  </button>
);

/**
 * A label with an ⓘ, and the description it reveals.
 *
 * `label` takes a node as happily as a string, so a caller that needs its own
 * heading markup can pass it in rather than reaching for the primitive above.
 */
const InfoNote = ({
  label,
  children,
  className = "",
  labelClassName = "text-[13px] text-[var(--900,#181B25)]",
  noteClassName = "text-[12px] text-[var(--600,#525866)] mt-2 max-w-[640px] space-y-2",
  name,
  testid,
}) => {
  const [open, setOpen] = useState(false);
  const id = useId();

  // Nothing to disclose — render the label alone rather than an icon that opens
  // an empty box.
  if (!children) {
    return label ? (
      <div className={className}>
        <span className={labelClassName}>{label}</span>
      </div>
    ) : null;
  }

  return (
    <div className={className}>
      <div className="flex items-center gap-1.5">
        {label && <span className={labelClassName}>{label}</span>}
        <InfoToggle
          open={open}
          onToggle={setOpen}
          controls={id}
          name={name ?? (typeof label === "string" ? label : "")}
          testid={testid}
        />
      </div>

      {/*
        `w-0 min-w-full` so the note wraps to whatever width the panel already
        has instead of setting it. Panels are flex items sized by their content,
        so a long paragraph appearing inside one would otherwise stretch it to
        its max width and shove its neighbours across the screen — the layout
        jumping on every ⓘ click.
      */}
      {open && (
        <div className="w-0 min-w-full">
          <div id={id} className={noteClassName}>
            {children}
          </div>
        </div>
      )}
    </div>
  );
};

export default InfoNote;
