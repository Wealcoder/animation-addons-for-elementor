/**
 * A generic "which parts of this extension run" dialog, drawn from a spec the
 * OWNING plugin ships in the dashboard payload
 * (`addons_config.extension_settings[<slug>]`). This file knows nothing about
 * any one extension: the presets, the switches, their wording and the save
 * action all arrive in the spec, so a Pro-owned card gets a gear on a
 * free-owned screen without the free plugin carrying a copy of its choices.
 *
 * Spec shape (every string already translated by the owner):
 *   { title, description, presets: [{key,label,description}],
 *     modules: [{key,label,description,group}], groups: {g:{label,note}},
 *     values: {preset, modules:{key:bool}}, action, card_note }
 *
 * Save posts `settings` (JSON) to admin-ajax `action` with the dashboard
 * nonce; the reply's `data` is the stored value and replaces the spec's
 * `values` so a re-open shows what was saved.
 */
import { __ } from "@wordpress/i18n";
import { useMemo, useRef, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { DialogClose } from "@/components/ui/dialog";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { AlertTriangle } from "lucide-react";

/** The spec for a slug, read lazily — the payload is printed with the page. */
export const moduleSettingsSpec = (slug) => {
  const all = window.WCF_ADDONS_ADMIN?.addons_config?.extension_settings;
  const spec = all && typeof all === "object" ? all[slug] : null;
  return spec && Array.isArray(spec.modules) ? spec : null;
};

const ModuleSettings = ({ slug }) => {
  const spec = useMemo(() => moduleSettingsSpec(slug), [slug]);
  const closeRef = useRef(null);
  const [saving, setSaving] = useState(false);
  const [preset, setPreset] = useState(spec?.values?.preset || "full");
  const [modules, setModules] = useState(() => {
    const out = {};
    (spec?.modules || []).forEach((m) => {
      out[m.key] = spec?.values?.modules?.[m.key] !== false;
    });
    return out;
  });

  if (!spec) return null;

  const customOn = preset === "custom";
  const groups = spec.groups || {};
  const groupKeys = [
    ...new Set((spec.modules || []).map((m) => m.group || "default")),
  ];

  // What a switch reads as under the chosen preset: everything on under
  // "full"; the owner's preset rule is not re-derived here — the dialog only
  // greys the switches and shows them as on, the server decides.
  const effective = (m) => (customOn ? modules[m.key] : true);

  const save = async () => {
    if (!WCF_ADDONS_ADMIN?.ajaxurl || !WCF_ADDONS_ADMIN?.nonce) return;
    setSaving(true);
    try {
      const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: spec.action,
          nonce: WCF_ADDONS_ADMIN.nonce,
          settings: JSON.stringify({ preset, modules }),
        }),
      });
      const json = await res.json();
      if (!res.ok || !json?.success) {
        throw new Error(
          json?.data?.message ||
            __("Could not save.", "animation-addons-for-elementor")
        );
      }
      spec.values = json.data;
      toast.success(__("Save Successful", "animation-addons-for-elementor"), {
        position: "top-right",
      });
      closeRef.current?.click();
    } catch (e) {
      toast.error(e.message, { position: "top-right" });
    } finally {
      setSaving(false);
    }
  };

  const offFront = (spec.modules || []).filter(
    (m) => m.group === "front" && customOn && !modules[m.key]
  );

  return (
    <div className="pt-6" data-module-settings={slug}>
      <div className="px-6 pb-4 border-b border-[#F2F5F8]">
        <h2 className="text-xl text-text font-medium">{spec.title}</h2>
        {spec.description && (
          <p className="text-sm text-text-secondary mt-1">{spec.description}</p>
        )}
      </div>

      <div className="px-6 py-5 space-y-5 border-b border-[#F2F5F8] max-h-[60vh] overflow-y-auto">
        {Array.isArray(spec.presets) && spec.presets.length > 0 && (
          <RadioGroup
            value={preset}
            onValueChange={setPreset}
            className="gap-2"
            data-module-presets
          >
            {spec.presets.map((p) => (
              <label
                key={p.key}
                data-module-preset={p.key}
                className={cn(
                  "flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors",
                  preset === p.key
                    ? "border-brand bg-brand/5"
                    : "border-[#E6E8EC] hover:border-[#CACFD8]"
                )}
              >
                <RadioGroupItem value={p.key} className="mt-0.5" />
                <span className="space-y-0.5">
                  <span className="block text-sm font-medium text-[#0E121B]">
                    {p.label}
                  </span>
                  {p.description && (
                    <span className="block text-xs text-text-secondary">
                      {p.description}
                    </span>
                  )}
                </span>
              </label>
            ))}
          </RadioGroup>
        )}

        {groupKeys.map((g) => (
          <div key={g} className="space-y-2" data-module-group={g}>
            {groups[g]?.label && (
              <div>
                <h3 className="text-xs font-semibold uppercase tracking-wide text-text-secondary">
                  {groups[g].label}
                </h3>
                {groups[g].note && (
                  <p className="text-xs text-text-secondary mt-0.5">
                    {groups[g].note}
                  </p>
                )}
              </div>
            )}
            <ul className="list-none m-0 p-0 space-y-1">
              {(spec.modules || [])
                .filter((m) => (m.group || "default") === g)
                .map((m) => (
                  <li
                    key={m.key}
                    data-module={m.key}
                    className={cn(
                      "flex items-start justify-between gap-3 rounded-md px-3 py-2",
                      customOn ? "bg-[#F7F8FA]" : "opacity-70"
                    )}
                  >
                    <div className="space-y-0.5">
                      <Label
                        htmlFor={`aae-module-${slug}-${m.key}`}
                        className="text-sm text-[#0E121B]"
                      >
                        {m.label}
                      </Label>
                      {m.description && (
                        <p className="text-xs text-text-secondary">
                          {m.description}
                        </p>
                      )}
                    </div>
                    <Switch
                      id={`aae-module-${slug}-${m.key}`}
                      disabled={!customOn}
                      checked={effective(m)}
                      onCheckedChange={(v) =>
                        setModules((prev) => ({ ...prev, [m.key]: !!v }))
                      }
                    />
                  </li>
                ))}
            </ul>
          </div>
        ))}

        {offFront.length > 0 && (
          <div
            className="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"
            data-module-warning
          >
            <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
            <span>
              {__(
                "Pages that use these will show nothing from them until you switch them back on. Nothing is deleted.",
                "animation-addons-for-elementor"
              )}
            </span>
          </div>
        )}

        {spec.card_note && (
          <p className="text-xs text-text-secondary">{spec.card_note}</p>
        )}
      </div>

      <div className="px-8 pt-4 pb-6 flex gap-3 justify-end items-center">
        <DialogClose asChild ref={closeRef}>
          <Button
            variant="secondary"
            className="h-11 shadow-common-2 text-base px-[18px]"
          >
            {__("Cancel", "animation-addons-for-elementor")}
          </Button>
        </DialogClose>
        <Button
          type="button"
          onClick={save}
          disabled={saving}
          className="h-11 shadow-common-2 text-base px-6"
          data-module-save
        >
          {__("Save", "animation-addons-for-elementor")}
        </Button>
      </div>
    </div>
  );
};

export default ModuleSettings;
