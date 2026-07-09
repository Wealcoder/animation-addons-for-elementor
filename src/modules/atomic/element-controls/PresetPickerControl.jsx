/* eslint-env browser */

/**
 * PresetPickerControl — the "Presets" element-control for AAE atomic widgets.
 *
 * Registered into Elementor's shared controlsRegistry under the type id
 * 'aae-preset-picker' (see ./index.js) and rendered by the editing panel
 * wherever the PHP side places an AAE_A_Preset_Picker_Control.
 *
 * This is an ACTION control, not a prop-bound control: it carries no stored
 * value. It lists the bundled presets for the selected element's type and, on
 * choosing one, REPLACES the selected element in place with the preset's design
 * (settings + styles + children). A special "Reset to Default" entry rebuilds the
 * element with its plain default children instead of a preset.
 *
 * The heavy lifting (sanitize / unwrap / regenerate style ids / type-rewrite /
 * replace-in-place) lives in ./preset-apply.js so the editor's auto-preset module
 * can reuse the exact same transform.
 *
 * Presets are provided by PHP as window.AAE_WIDGET_PRESETS, keyed by element
 * type. See class-atomic.php::get_widget_presets().
 */

import * as React from "react";
import { useElement } from "@elementor/editor-editing-panel";
import { Stack, Typography, MenuItem, Select } from "@elementor/ui";

import {
  getPresetsForType,
  applyPresetModel,
} from "./preset-apply";
import { DEFAULT_CHILD_MODELS, applyResetToDefault } from "./preset-defaults";

// The sentinel value for the "Reset to Default" menu entry (not a preset id).
const RESET_VALUE = "__aae_reset_default__";

export function PresetPickerControl({ label }) {
  const { element } = useElement();
  const elementId = element.id;
  const type = element.type || element.model?.get?.("elType");

  const presets = getPresetsForType(type);
  const canReset = !!DEFAULT_CHILD_MODELS[type];

  const onPick = (value) => {
    if (value === RESET_VALUE) {
      applyResetToDefault(elementId, type);
      return;
    }
    const preset = presets.find((p) => p.id === value);
    if (preset?.model) {
      applyPresetModel(preset.model, elementId, type, {
        title: "Preset",
        subtitle: `Applied "${preset.name}"`,
      });
    }
  };

  // Nothing to offer at all — no presets AND no reset target.
  if (!presets.length && !canReset) {
    return null;
  }

  return (
    <Stack gap={1}>
      <Typography
        variant="caption"
        sx={{ fontWeight: 500, color: "text.secondary" }}
      >
        {label || "Presets"}
      </Typography>
      <Select
        size="tiny"
        displayEmpty
        value=""
        onChange={(e) => onPick(e.target.value)}
      >
        <MenuItem value="" disabled>
          {"Apply a preset…"}
        </MenuItem>
        {canReset && (
          <MenuItem value={RESET_VALUE}>{"Reset to Default (clear styles)"}</MenuItem>
        )}
        {presets.map((p) => (
          <MenuItem key={p.id} value={p.id}>
            {p.name}
          </MenuItem>
        ))}
      </Select>
    </Stack>
  );
}
