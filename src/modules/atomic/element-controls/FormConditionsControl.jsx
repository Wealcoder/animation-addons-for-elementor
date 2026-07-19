/* eslint-env browser */

/**
 * FormConditionsControl — the "Conditional Display" element-control for AAE
 * Form field widgets (pro feature; this is only the editor UI — free ships
 * it dormant, exactly like the Integrations tab).
 *
 * Registered under the type id 'aae-form-conditions' (see ./index.js). The
 * PRO plugin's FormConditions\Controls injects the section (General tab, on
 * each field widget) with an enable switch bound to `aae_cond_enable` and
 * this control. The dialog reads/writes three props on the SELECTED field:
 *
 *   aae_cond_action  'show' | 'hide'         (string prop)
 *   aae_cond_logic   'all'  | 'any'          (string prop)
 *   aae_cond_rules   JSON [{field, operator, value}, …]  (string prop)
 *
 * Rule semantics (mirrored EXACTLY by the pro runtime + PHP engine):
 * a field value list passes equals/contains/… per rule; 'all'/'any'
 * combines; action 'show' reveals on pass, 'hide' conceals on pass.
 *
 * Writes use the documented envelope pattern — string props saved as
 * { $$type: 'string', value } via $e.run('document/elements/settings').
 */

import * as React from "react";
import { __ } from "@wordpress/i18n";
import { useElement } from "@elementor/editor-editing-panel";
import { getContainer } from "@elementor/editor-elements";
import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControl,
  IconButton,
  InputLabel,
  MenuItem,
  Select,
  Stack,
  TextField,
  Typography,
} from "@elementor/ui";

import { collectFieldTags } from "./FormActionsControl";

const { useState } = React;

const TD = "animation-addons-for-elementor";

const BRAND = "#F6502C";
const BRAND_HOVER = "#E04524";
const BRAND_GRADIENT = "linear-gradient(135deg, #FFA184 0%, #F2754F 100%)";

const OPERATORS = [
  { value: "equals", label: __("Is", TD) },
  { value: "not_equals", label: __("Is not", TD) },
  { value: "not_empty", label: __("Is not empty", TD) },
  { value: "empty", label: __("Is empty", TD) },
  { value: "contains", label: __("Contains", TD) },
  { value: "greater_than", label: __("Greater than", TD) },
  { value: "less_than", label: __("Less than", TD) },
];

const NO_VALUE_OPERATORS = ["empty", "not_empty"];

/** Unwrap a possibly-enveloped prop value ({$$type,value} → value). */
const unwrap = (raw) =>
  raw && typeof raw === "object" && "value" in raw ? raw.value : raw;

const asString = (raw) => {
  let value = unwrap(raw);
  if (value && typeof value === "object") {
    value = unwrap(value);
  }
  return value === null || value === undefined || typeof value === "object"
    ? ""
    : String(value);
};

/** Nearest e-aae-a-form ancestor container of an editor container. */
const parentFormOf = (container) => {
  let current = container?.parent;
  while (current) {
    const model = current.model;
    const elType = model?.get?.("elType");
    const type = "widget" === elType ? model?.get?.("widgetType") : elType;
    if ("e-aae-a-form" === type) {
      return current;
    }
    current = current.parent;
  }
  return null;
};

const readState = (container) => {
  const settings = container?.settings?.toJSON?.() || {};
  const action = asString(settings.aae_cond_action) || "show";
  const logic = asString(settings.aae_cond_logic) || "all";

  let rules = [];
  try {
    const parsed = JSON.parse(asString(settings.aae_cond_rules) || "[]");
    if (Array.isArray(parsed)) {
      rules = parsed
        .filter((rule) => rule && typeof rule === "object")
        .map((rule) => ({
          field: String(rule.field || ""),
          operator: String(rule.operator || "equals"),
          value: String(rule.value ?? ""),
        }));
    }
  } catch (_e) {
    /* corrupt JSON — start empty */
  }

  return { action, logic, rules };
};

const TrashIcon = () => (
  <svg
    width={15}
    height={15}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth={1.8}
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M3 6h18" />
    <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
  </svg>
);

export function FormConditionsControl({ label }) {
  const { element } = useElement();
  const [open, setOpen] = useState(false);
  const [action, setAction] = useState("show");
  const [logic, setLogic] = useState("all");
  const [rules, setRules] = useState([]);
  const [fieldTags, setFieldTags] = useState([]);
  const [noForm, setNoForm] = useState(false);

  const openDialog = () => {
    const container = getContainer(element.id);
    const state = readState(container);
    setAction(state.action);
    setLogic(state.logic);
    setRules(
      state.rules.length
        ? state.rules
        : [{ field: "", operator: "equals", value: "" }]
    );

    const form = parentFormOf(container);
    setNoForm(!form);
    setFieldTags(form ? collectFieldTags(form) : []);
    setOpen(true);
  };

  const patchRule = (index, key, value) =>
    setRules((current) =>
      current.map((rule, i) => (i === index ? { ...rule, [key]: value } : rule))
    );

  const removeRule = (index) =>
    setRules((current) => current.filter((_rule, i) => i !== index));

  const addRule = () =>
    setRules((current) => [...current, { field: "", operator: "equals", value: "" }]);

  const save = () => {
    const container = getContainer(element.id);
    const $e = window.$e;
    if (!container || !$e?.run) {
      return;
    }

    const cleanRules = rules.filter((rule) => rule.field);

    $e.run("document/elements/settings", {
      container,
      settings: {
        aae_cond_action: { $$type: "string", value: action },
        aae_cond_logic: { $$type: "string", value: logic },
        aae_cond_rules: { $$type: "string", value: JSON.stringify(cleanRules) },
      },
      options: { external: true },
    });
    setOpen(false);
  };

  return (
    <Stack gap={1}>
      <Button variant="outlined" size="small" fullWidth onClick={openDialog}>
        {label || __("Edit Conditions", TD)}
      </Button>

      <Dialog
        open={open}
        onClose={() => setOpen(false)}
        maxWidth="sm"
        fullWidth
        sx={{ "& .MuiDialog-container": { justifyContent: "flex-start" } }}
        PaperProps={{ sx: { ml: "310px", maxHeight: "calc(100% - 48px)" } }}
      >
        <DialogTitle sx={{ pb: 1.5 }}>
          <Stack direction="row" alignItems="center" gap={1.5}>
            <Stack
              alignItems="center"
              justifyContent="center"
              sx={{
                width: 36,
                height: 36,
                borderRadius: 2,
                flexShrink: 0,
                color: "#fff",
                background: BRAND_GRADIENT,
                boxShadow: "0 2px 8px rgba(246, 80, 44, 0.35)",
              }}
            >
              <svg
                width={18}
                height={18}
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={1.8}
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3" />
                <path d="M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3" />
                <path d="m9 12 2 2 4-5" />
              </svg>
            </Stack>
            <Stack>
              <Typography variant="subtitle1" sx={{ fontWeight: 600, lineHeight: 1.25 }}>
                {__("Conditional Display", TD)}
              </Typography>
              <Typography
                variant="caption"
                sx={{
                  color: "text.secondary",
                  textTransform: "uppercase",
                  letterSpacing: "0.06em",
                  fontSize: 10,
                }}
              >
                {__("AAE Form Builder", TD)}
              </Typography>
            </Stack>
          </Stack>
        </DialogTitle>

        <DialogContent dividers>
          <Stack gap={1.5}>
            {noForm && (
              <Alert severity="warning">
                {__("This field is not inside an AAE Form — conditions have nothing to react to.", TD)}
              </Alert>
            )}

            <Stack direction="row" gap={1}>
              <FormControl fullWidth size="small">
                <InputLabel id="aae-cond-action">{__("Action", TD)}</InputLabel>
                <Select
                  labelId="aae-cond-action"
                  label={__("Action", TD)}
                  value={action}
                  onChange={(e) => setAction(e.target.value)}
                >
                  <MenuItem value="show">{__("Show this field when…", TD)}</MenuItem>
                  <MenuItem value="hide">{__("Hide this field when…", TD)}</MenuItem>
                </Select>
              </FormControl>
              <FormControl fullWidth size="small">
                <InputLabel id="aae-cond-logic">{__("Match", TD)}</InputLabel>
                <Select
                  labelId="aae-cond-logic"
                  label={__("Match", TD)}
                  value={logic}
                  onChange={(e) => setLogic(e.target.value)}
                >
                  <MenuItem value="all">{__("All rules match", TD)}</MenuItem>
                  <MenuItem value="any">{__("Any rule matches", TD)}</MenuItem>
                </Select>
              </FormControl>
            </Stack>

            {rules.map((rule, index) => (
              // eslint-disable-next-line react/no-array-index-key
              <Stack key={index} direction="row" gap={1} alignItems="center">
                <FormControl size="small" sx={{ flex: 1.4, minWidth: 0 }}>
                  <InputLabel id={`aae-cond-field-${index}`}>{__("Field", TD)}</InputLabel>
                  <Select
                    labelId={`aae-cond-field-${index}`}
                    label={__("Field", TD)}
                    value={rule.field}
                    onChange={(e) => patchRule(index, "field", e.target.value)}
                  >
                    {fieldTags.map(({ key, label: fieldLabel }) => (
                      <MenuItem key={key} value={key}>
                        {fieldLabel || key}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <FormControl size="small" sx={{ flex: 1, minWidth: 0 }}>
                  <InputLabel id={`aae-cond-op-${index}`}>{__("Operator", TD)}</InputLabel>
                  <Select
                    labelId={`aae-cond-op-${index}`}
                    label={__("Operator", TD)}
                    value={rule.operator}
                    onChange={(e) => patchRule(index, "operator", e.target.value)}
                  >
                    {OPERATORS.map(({ value, label: opLabel }) => (
                      <MenuItem key={value} value={value}>
                        {opLabel}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <TextField
                  size="small"
                  sx={{ flex: 1, minWidth: 0 }}
                  label={__("Value", TD)}
                  value={rule.value}
                  disabled={NO_VALUE_OPERATORS.includes(rule.operator)}
                  onChange={(e) => patchRule(index, "value", e.target.value)}
                />

                <IconButton
                  size="small"
                  title={__("Remove rule", TD)}
                  onClick={() => removeRule(index)}
                  sx={{ color: "text.secondary", "&:hover": { color: BRAND } }}
                >
                  <TrashIcon />
                </IconButton>
              </Stack>
            ))}

            <Button size="small" variant="text" onClick={addRule} sx={{ alignSelf: "flex-start" }}>
              {__("+ Add Rule", TD)}
            </Button>

            <Typography variant="caption" sx={{ color: "text.secondary" }}>
              {__("Hidden fields never block submit and their values are not sent. The same rules are re-checked on the server.", TD)}
            </Typography>
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button size="small" color="secondary" onClick={() => setOpen(false)}>
            {__("Cancel", TD)}
          </Button>
          <Button
            size="small"
            variant="contained"
            onClick={save}
            sx={{
              backgroundColor: BRAND,
              color: "#fff",
              "&:hover": { backgroundColor: BRAND_HOVER },
            }}
          >
            {__("Save Conditions", TD)}
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
}
