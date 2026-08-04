import ColorField from "@/components/animation-settings/ColorField";
import ConditionsField from "@/components/animation-settings/ConditionsField";
import InfoNote, { InfoToggle } from "@/components/shared/InfoToggle";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";

/**
 * One settings panel, rendered entirely from the schema the server ships in
 * `WCF_ADDONS_ADMIN.animation_settings.schema`.
 *
 * Deliberately generic rather than one component per feature: the schema
 * already has to exist server-side (it drives defaults, sanitising, the Kit
 * import and Pro's render bridge), so hand-writing the same field list again in
 * React would just be a second copy free to drift. A field added to
 * Animation_Settings::schema() appears here with no JS change at all — and its
 * control type, label, range and options are guaranteed to match what the
 * sanitiser will actually accept.
 *
 * Draft state lives here so Cancel can throw it away; the committed value stays
 * with the parent, which is also what the legacy toggle writes through.
 */
const FeaturePanel = ({
  feature,
  schema,
  value,
  globals = [],
  hasPro,
  saving,
  onSave,
  note = null,
}) => {
  const [draft, setDraft] = useState(value);

  // What `note` says about this panel, revealed from the ⓘ beside its title.
  const [noteOpen, setNoteOpen] = useState(false);

  // Re-sync when the parent commits (save, or an external reset).
  useEffect(() => setDraft(value), [value]);

  const set = (key, next) => setDraft((prev) => ({ ...prev, [key]: next }));

  // A group can declare itself free (`pro: false` in the server schema) — the
  // Performance panels are, since asset delivery ships in the free plugin.
  // Anything that doesn't say otherwise is treated as Pro, so Animation
  // Settings keeps its badge without having to opt in.
  const isPro = schema?.pro !== false;

  // Without Pro nothing renders these on the front end, so the panel is
  // readable but inert rather than hidden — the point is the upsell.
  const locked = isPro && !hasPro;
  const dirty = JSON.stringify(draft) !== JSON.stringify(value);

  const fields = Object.entries(schema?.fields || {});

  // The enable switch gets the header row; everything else stacks below it.
  const enableField = fields.find(([key]) => key === "enable");
  const rest = fields.filter(([key]) => key !== "enable");

  const renderField = ([key, field]) => {
    const current = draft?.[key];

    // A bool that isn't `enable` — that one owns the header row instead.
    if (field.type === "bool") {
      return (
        <div
          className="flex flex-row items-center justify-between gap-3 mt-5"
          key={key}
        >
          <span className="text-[13px] text-[var(--900,#181B25)]">
            {field.label}
          </span>
          <Switch
            checked={!!current}
            disabled={locked}
            onCheckedChange={(next) => set(key, next)}
            sx={{ marginTop: "0" }}
            data-aae-field={`${feature}.${key}`}
          />
        </div>
      );
    }

    if (field.type === "color") {
      return (
        <ColorField
          key={key}
          label={field.label}
          value={current}
          globals={globals}
          disabled={locked}
          onChange={(next) => set(key, next)}
        />
      );
    }

    if (field.type === "enum") {
      return (
        <div className="mt-5" key={key}>
          <p className="text-[12px] text-[var(--600,#525866)] mb-2">
            {field.label}
          </p>
          <Select
            // Radix treats "" as "no selection", but an empty string is a real
            // choice here ("Default"), so it travels as a sentinel.
            value={current === "" ? "__default__" : current ?? ""}
            disabled={locked}
            onValueChange={(next) =>
              set(key, next === "__default__" ? "" : next)
            }
          >
            <SelectTrigger className="h-10" data-aae-field={`${feature}.${key}`}>
              <SelectValue
                placeholder={__("Select", "animation-addons-for-elementor")}
              />
            </SelectTrigger>
            <SelectContent>
              {Object.entries(field.options || {}).map(([optKey, label]) => (
                <SelectItem
                  key={optKey || "__default__"}
                  value={optKey === "" ? "__default__" : optKey}
                >
                  {label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      );
    }

    // A plain scalar, as opposed to `size`'s {size, unit}. Mirrors the v3
    // controls that are NUMBER rather than SLIDER — see the schema.
    if (field.type === "number") {
      return (
        <div className="mt-5" key={key}>
          <p className="text-[12px] text-[var(--600,#525866)] mb-2">
            {field.label}
          </p>
          <Input
            type="number"
            min={field.min}
            max={field.max}
            disabled={locked}
            value={current ?? ""}
            data-aae-field={`${feature}.${key}`}
            onChange={(e) =>
              set(key, e.target.value === "" ? "" : Number(e.target.value))
            }
          />
        </div>
      );
    }

    // Per-device on/off + a number, for the smooth scroller. The device list
    // comes from the server so it cannot drift from what the sanitiser accepts
    // — the runtime indexes its payload by exactly these keys.
    if (field.type === "devices") {
      const devices = Object.entries(field.devices || {});
      const rows = current || {};

      const setDevice = (device, patch) =>
        set(key, {
          ...rows,
          [device]: { ...(rows[device] || {}), ...patch },
        });

      return (
        <div className="mt-6" key={key}>
          <InfoNote label={field.label} testid={`${feature}.${key}.help`}>
            {field.help}
          </InfoNote>

          <div className="mt-3 rounded-md border border-[#E1E4EA] divide-y divide-[#E1E4EA]">
            {devices.map(([device, label]) => {
              const row = rows[device] || {};

              return (
                <div
                  className="flex items-center gap-3 px-3 py-2.5"
                  key={device}
                >
                  <Switch
                    checked={!!row.enabled}
                    disabled={locked}
                    onCheckedChange={(next) =>
                      setDevice(device, { enabled: next })
                    }
                    sx={{ marginTop: "0" }}
                    data-aae-field={`${feature}.${key}.${device}.enabled`}
                  />
                  <span className="text-[13px] text-[var(--900,#181B25)] flex-1">
                    {label}
                  </span>
                  <Input
                    type="number"
                    className="h-9 w-24"
                    min={field.min}
                    max={field.max}
                    step={field.step}
                    // Disabled rather than hidden when the device is off: the
                    // value is still stored, so hiding it would make switching
                    // the device back on look like it lost the setting.
                    disabled={locked || !row.enabled}
                    value={row.level ?? ""}
                    data-aae-field={`${feature}.${key}.${device}.level`}
                    onChange={(e) =>
                      setDevice(device, {
                        level:
                          e.target.value === "" ? "" : Number(e.target.value),
                      })
                    }
                  />
                </div>
              );
            })}
          </div>
        </div>
      );
    }

    // Include/exclude rules over locations. Its own component — the "Specific
    // pages" rows need hooks, which cannot live in a render helper that is
    // called conditionally.
    if (field.type === "conditions") {
      return (
        <ConditionsField
          key={key}
          name={key}
          feature={feature}
          field={field}
          value={current}
          disabled={locked}
          onChange={(next) => set(key, next)}
        />
      );
    }

    if (field.type === "size") {
      return (
        <div className="mt-5" key={key}>
          <p className="text-[12px] text-[var(--600,#525866)] mb-2">
            {field.label}
            {field.unit ? ` (${field.unit})` : ""}
          </p>
          <Input
            type="number"
            min={field.min}
            max={field.max}
            disabled={locked}
            value={current?.size ?? ""}
            data-aae-field={`${feature}.${key}`}
            onChange={(e) =>
              set(key, {
                size: e.target.value === "" ? "" : Number(e.target.value),
                unit: current?.unit ?? field.unit ?? "px",
              })
            }
          />
        </div>
      );
    }

    return null;
  };

  // Panels are a single narrow column of controls, except where the schema says
  // otherwise — display-condition rows are two selects side by side and do not
  // fit in 420px.
  const width = schema?.width === "wide" ? "max-w-[720px]" : "max-w-[420px]";

  return (
    <div className={`bg-background rounded-lg p-6 ${width}`}>
      <h3 className="flex items-center gap-2">
        <span className="text-[16px] font-medium text-[var(--900,#181B25)]">
          {schema?.label}
        </span>
        {isPro && (
          <Badge
            className="bg-[linear-gradient(109deg,#ffab472e_0%,#ffab472e_100%)] text-[#717784]"
            variant="pro"
          >
            {__("PRO", "animation-addons-for-elementor")}
          </Badge>
        )}
        {note && (
          <InfoToggle
            open={noteOpen}
            onToggle={setNoteOpen}
            controls={`aae-note-${feature}`}
            name={schema?.label || feature}
            testid={`${feature}.note`}
          />
        )}
      </h3>

      {/* w-0 min-w-full — see InfoToggle: the note must wrap to the panel's
          width, not decide it. */}
      {note && noteOpen && (
        <div className="w-0 min-w-full">
          <p
            id={`aae-note-${feature}`}
            className="text-[12px] text-[var(--600,#525866)] mt-2"
          >
            {note}
          </p>
        </div>
      )}

      {locked && (
        <p className="text-[12px] text-[var(--600,#525866)] mt-2">
          {__(
            "Animation Addons Pro renders this. Settings here are saved, but nothing shows on the front end until Pro is active.",
            "animation-addons-for-elementor",
          )}
        </p>
      )}

      {enableField && (
        <div className="flex flex-row items-center justify-between gap-3 mt-6">
          <span className="text-[13px] text-[var(--900,#181B25)]">
            {enableField[1].label}
          </span>
          <span className="flex items-center gap-2">
            <Switch
              checked={!!draft?.enable}
              disabled={locked}
              onCheckedChange={(next) => set("enable", next)}
              sx={{ marginTop: "0" }}
              data-aae-field={`${feature}.enable`}
            />
            <span className="text-[12px] text-[var(--600,#525866)]">
              {__("Enable", "animation-addons-for-elementor")}
            </span>
          </span>
        </div>
      )}

      {rest.map(renderField)}

      <div className="flex gap-2.5 items-center mt-8">
        <Button
          className="p-[20px] rounded-[8px]"
          variant="secondary"
          disabled={!dirty || saving}
          onClick={() => setDraft(value)}
        >
          {__("Cancel", "animation-addons-for-elementor")}
        </Button>
        <Button
          className="p-[20px] rounded-[8px]"
          disabled={saving}
          onClick={() => onSave({ [feature]: draft })}
          data-aae-save={feature}
        >
          {saving
            ? __("Saving…", "animation-addons-for-elementor")
            : __("Save Settings", "animation-addons-for-elementor")}
        </Button>
      </div>
    </div>
  );
};

export default FeaturePanel;
