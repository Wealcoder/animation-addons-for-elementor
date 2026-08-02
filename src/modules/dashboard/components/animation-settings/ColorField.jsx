import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { __ } from "@wordpress/i18n";
import { GlobeIcon } from "lucide-react";

/**
 * A colour that is EITHER a literal hex or a reference to one of the Kit's
 * global colours — the "Custom Color / Global Color" pair from the design.
 *
 * The two are stored side by side (`custom` and `global`) with `mode` picking
 * the winner, rather than one overwriting the other. Switching to Global and
 * back has to return the exact hex the user had picked; collapsing to a single
 * field would silently lose it.
 *
 * Value shape mirrors the PHP sanitizer in
 * inc/AnimationSettings/class-animation-settings.php:
 *   { mode: 'custom'|'global', custom: '#rrggbb', global: '<kit color id>' }
 */
const ColorField = ({ label, value, globals = [], disabled, onChange }) => {
  const mode = value?.mode === "global" ? "global" : "custom";
  const custom = value?.custom || "#ffffff";

  const set = (patch) => onChange({ ...value, mode, custom, ...patch });

  // What the swatch should actually show right now.
  const active =
    mode === "global"
      ? globals.find((c) => c.id === value?.global)?.value || custom
      : custom;

  const isPressed = (which) => mode === which;

  const buttonClass = (which) =>
    cn(
      "flex-1 flex items-center justify-center gap-2 h-10 px-3 rounded-[8px] border text-[12px] transition-colors",
      isPressed(which)
        ? "border-[#181B25] bg-[#F5F7FA] text-[var(--900,#181B25)]"
        : "border-[#E1E4EA] bg-white text-[var(--600,#525866)]",
      disabled && "opacity-50 cursor-not-allowed",
    );

  return (
    <div className="mt-5">
      <p className="text-[12px] text-[var(--600,#525866)] mb-2">{label}</p>

      <div className="flex items-center gap-3">
        {/* Custom: the swatch IS the trigger — a native colour input keeps the
            picker accessible and needs no extra dependency. */}
        <label className={buttonClass("custom")}>
          <span
            className="inline-block w-4 h-4 rounded-full border border-[#E1E4EA]"
            style={{ backgroundColor: active }}
            data-aae-color-swatch={label}
          />
          {__("Custom Color", "animation-addons-for-elementor")}
          <input
            type="color"
            className="sr-only"
            value={custom}
            disabled={disabled}
            aria-label={label}
            onChange={(e) => set({ mode: "custom", custom: e.target.value })}
          />
        </label>

        <span className="text-[12px] text-[var(--600,#525866)]">
          {__("or", "animation-addons-for-elementor")}
        </span>

        <div className="flex-1">
          <Select
            value={mode === "global" ? value?.global || "" : ""}
            disabled={disabled || !globals.length}
            onValueChange={(id) => set({ mode: "global", global: id })}
          >
            <SelectTrigger
              className={buttonClass("global")}
              data-aae-global-trigger={label}
            >
              <GlobeIcon size={14} />
              <SelectValue
                placeholder={__(
                  "Global Color",
                  "animation-addons-for-elementor",
                )}
              />
            </SelectTrigger>
            <SelectContent>
              {globals.map((color) => (
                <SelectItem key={color.id} value={color.id}>
                  <span className="flex items-center gap-2">
                    <span
                      className="inline-block w-3 h-3 rounded-full border border-[#E1E4EA]"
                      style={{ backgroundColor: color.value }}
                    />
                    {color.title}
                  </span>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
    </div>
  );
};

export default ColorField;
