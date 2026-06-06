/* eslint-env browser */

import * as React from "react";
import { Stack, Tooltip, Typography, styled } from "@elementor/ui";

import { SelectInput } from "./inputs/SelectInput";
import { NumberInput } from "./inputs/NumberInput";
import { SwitchInput } from "./inputs/SwitchInput";
import { TextInput } from "./inputs/TextInput";
import { ColorInput } from "./inputs/Color";
import { PlayButtonInput } from "./inputs/PlayButtonInput";
import { SaveButtonInput } from "./inputs/SaveButtonInput";
import { RepeaterInput } from "./inputs/RepeaterInput";
import { BorderInput } from "./inputs/BorderInput";
import { TextareaInput } from "./inputs/TextareaInput";
import { SliderInput } from "./inputs/SliderInput";
import { DimensionInput, DimensionsInput } from "./inputs/Dimension";
import { ChooseInput } from "./inputs/ChooseInput";
import { MediaInput } from "./inputs/MediaInput";
import { LinkInput } from "./inputs/LinkInput";
import { useCellValue } from "./use-cell-value";
import { usePlainValue } from "./use-plain-value";
import { useArrayCellValue } from "./use-array-cell-value";
import { CodeInput } from "./inputs/CodeInput";

const HelpIcon = () => (
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ opacity: 0.6, cursor: "help" }}>
    <circle cx="12" cy="12" r="10"></circle>
    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
    <line x1="12" y1="17" x2="12.01" y2="17"></line>
  </svg>
);

const HelpTooltip = ({ title, children }) => (
  <Tooltip
    title={title}
    componentsProps={{
      tooltip: {
        sx: {
          bgcolor: '#2b2d30',
          color: '#e5e5e5',
          px: 1.5,
          py: 1,
          lineHeight: 1.5,
          fontSize: '11.5px',
          fontWeight: 400,
          borderRadius: 1,
          maxWidth: 240,
          boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
        }
      }
    }}
  >
    {children}
  </Tooltip>
);

/**
 * Map config `control` strings → input components. Single source of truth
 * so config.js stays declarative.
 *
 * After the PropTypes consolidation, every responsive prop stores under the
 * same `aae-rj` envelope — so we no longer carry per-control
 * inner type / responsive key. JS owns the value shape end to end.
 *
 * `innerType` is still carried only for the non-responsive PlainRow path —
 * Elementor's primitive prop types DO expect a transformable wrap on save.
 */
const CONTROL_REGISTRY = {
  select:     { Component: SelectInput,     innerType: "string" },
  text:       { Component: TextInput,       innerType: "string" },
  textarea:   { Component: TextareaInput,   innerType: "string" },
  number:     { Component: NumberInput,     innerType: "number" },
  slider:     { Component: SliderInput,     innerType: "number" },
  dimension:  { Component: DimensionInput,  innerType: "object" },
  dimensions: { Component: DimensionsInput, innerType: "object" },
  switch:     { Component: SwitchInput,     innerType: "boolean" },
  color:      { Component: ColorInput,      innerType: "string" },
  border:     { Component: BorderInput,     innerType: "object" },
  choose:     { Component: ChooseInput,     innerType: "string" },
  // Media picker — value: { id, url, size, sizes } | null
  media:      { Component: MediaInput,      innerType: "object" },
  link:       { Component: LinkInput,       innerType: "string" },
  code:       { Component: CodeInput,       innerType: "string" },
};

/* ---------- dot indicator ---------- */

const Dot = styled("button", {
  shouldForwardProp: (prop) => prop !== "isOverride",
})(({ theme, isOverride }) => ({
  width: 6,
  height: 6,
  padding: 0,
  border: 0,
  borderRadius: "50%",
  background: isOverride
    ? theme.palette.warning.light
    : theme.palette.text.disabled,
  cursor: isOverride ? "pointer" : "default",
  flexShrink: 0,
  "&:hover": {
    opacity: isOverride ? 0.85 : 1,
  },
  "&:focus-visible": {
    outline: `2px solid ${theme.palette.primary.main}`,
    outlineOffset: 2,
  },
}));

/**
 * One row inside a <ResponsiveSection>. Renders its own label + input. The
 * dot indicator appears only for responsive rows. Three modes:
 *
 *   - 'play-button' control: pure action button, no prop binding. Used for
 *     Play Animation rows.
 *   - responsive:false : non-responsive primitive (Boolean / String / Number).
 *     No dot indicator; reads/writes the plain transformable envelope.
 *   - default          : per-breakpoint responsive prop with dot indicator
 *     and breakpoint cascade via useCellValue.
 */
export function ResponsiveRow({
  bind,
  label,
  control,
  options,
  placeholder,
  min,
  max,
  step,
  units,
  defaultUnit,
  datalist,
  cells,
  addLabel,
  rowDefaults,
  defaultValue,
  responsive = true,
  propValue,
  activeBp,
  elementId,
  play_group,
  help
}) {
  if (control === "play-button") {
    return (
      <Stack direction="row" justifyContent="flex-end" sx={{ mb: 1 }}>
        <PlayButtonInput play_group={play_group} />
      </Stack>
    );
  }

  if (control === "save-button") {
    return (
      <Stack direction="row" justifyContent="flex-end" sx={{ mb: 1 }}>
        <SaveButtonInput play_group={play_group} />
      </Stack>
    );
  }

  if (control === "repeater") {
    return (
      <RepeaterRow
        bind={bind}
        label={label}
        help={help}
        cells={cells}
        addLabel={addLabel}
        rowDefaults={rowDefaults}
        defaultValue={defaultValue}
        propValue={propValue}
        activeBp={activeBp}
        elementId={elementId}
      />
    );
  }

  const entry = CONTROL_REGISTRY[control];
  if (!entry) {
    // eslint-disable-next-line no-console
    console.warn(
      `[AAE] ResponsiveRow: unknown control type "${control}" for bind "${bind}"`,
    );
    return null;
  }

  const { Component, innerType } = entry;

  return responsive ? (
    <ResponsiveCellRow
      Component={Component}
      bind={bind}
      label={label}
      help={help}
      options={options}
      placeholder={placeholder}
      min={min}
      max={max}
      step={step}
      units={units}
      defaultUnit={defaultUnit}
      datalist={datalist}
      defaultValue={defaultValue}
      propValue={propValue}
      activeBp={activeBp}
      elementId={elementId}
      play_group={play_group}
    />
  ) : (
       
      <PlainRow
        Component={Component}
        innerType={innerType}
        bind={bind}
        label={label}
        help={help}
        options={options}
        placeholder={placeholder}
        min={min}
        max={max}
        step={step}
        units={units}
        defaultUnit={defaultUnit}
        datalist={datalist}
        defaultValue={defaultValue}
        propValue={propValue}
        elementId={elementId}
        play_group={play_group}
      />
    
  );
}

/* ---------- responsive row (with dot) ---------- */

function ResponsiveCellRow({
  Component,
  bind,
  label,
  help,
  options,
  placeholder,
  min,
  max,
  step,
  units,
  defaultUnit,
  datalist,
  defaultValue,
  propValue,
  activeBp,
  elementId,
  play_group,
}) {
  const { value, ownValue, setValue, resetValue } = useCellValue({
    propValue,
    bind,
    activeBp,
    elementId,
    defaultValue,
  });

  const hasOverride = ownValue !== null && activeBp !== "desktop";
  const tooltipText = hasOverride
    ? `Reset ${activeBp} value to inherit from parent breakpoint`
    : "";

  return (
    <Stack direction="column" sx={{ width: "100%", mb: 1 }}>
      <Stack direction="row" alignItems="center" gap={0.5} sx={{ mb: 0.5 }}>
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{ flex: 1, minWidth: 0, display: "flex", alignItems: "center", gap: 0.5 }}
        >
          {label}
          {help && (
            <HelpTooltip title={help}>
              <span style={{ display: "inline-flex" }}><HelpIcon /></span>
            </HelpTooltip>
          )}
        </Typography>
        {hasOverride ? (
          <Tooltip title={tooltipText}>
            <Dot
              type="button"
              isOverride
              onClick={(e) => {
                e.stopPropagation();
                resetValue();
              }}
              aria-label={tooltipText}
            />
          </Tooltip>
        ) : (
          <Dot as="span" />
        )}
      </Stack>
      <Component
        value={value}
        onChange={setValue}
        options={options}
        placeholder={placeholder}
        min={min}
        max={max}
        step={step}
        units={units}
        defaultUnit={defaultUnit}
        datalist={datalist}
        play_group={play_group}
      />
    </Stack>
  );
}

/* ---------- repeater row (whole-list per-bp, with dot) ---------- */

function RepeaterRow({
  bind,
  label,
  help,
  cells,
  addLabel,
  rowDefaults,
  defaultValue,
  propValue,
  activeBp,
  elementId,
}) {
  const { value, ownValue, setValue, resetValue } = useArrayCellValue({
    propValue,
    bind,
    activeBp,
    elementId,
    defaultValue,
  });

  const hasOverride = ownValue !== null && activeBp !== "desktop";
  const tooltipText = hasOverride
    ? `Reset ${activeBp} rows to inherit from parent breakpoint`
    : "";

  return (
    <Stack direction="column" sx={{ width: "100%", mb: 1 }}>
      <Stack direction="row" alignItems="center" gap={0.5} sx={{ mb: 0.5 }}>
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{ flex: 1, minWidth: 0, display: "flex", alignItems: "center", gap: 0.5 }}
        >
          {label}
          {help && (
            <HelpTooltip title={help}>
              <span style={{ display: "inline-flex" }}><HelpIcon /></span>
            </HelpTooltip>
          )}
        </Typography>
        {hasOverride ? (
          <Tooltip title={tooltipText}>
            <Dot
              type="button"
              isOverride
              onClick={(e) => {
                e.stopPropagation();
                resetValue();
              }}
              aria-label={tooltipText}
            />
          </Tooltip>
        ) : (
          <Dot as="span" />
        )}
      </Stack>
      <RepeaterInput
        value={value}
        onChange={setValue}
        cells={cells}
        addLabel={addLabel}
        rowDefaults={rowDefaults}
      />
    </Stack>
  );
}

/* ---------- non-responsive row (no dot) ---------- */

function PlainRow({
  Component,
  innerType,
  bind,
  label,
  help,
  options,
  placeholder,
  min,
  max,
  step,
  units,
  defaultUnit,
  datalist,
  defaultValue,
  propValue,
  elementId,
  play_group,
}) {
  const { value, setValue } = usePlainValue({
    propValue,
    bind,
    innerType,
    elementId,
    defaultValue,
  });

  return (
    <Stack direction="column" sx={{ width: "100%", mb: 1 }}>
      <Stack direction="row" alignItems="center" sx={{ mb: 0.5 }}>
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{ flex: 1, minWidth: 0, display: "flex", alignItems: "center", gap: 0.5 }}
        >
          {label}
          {help && (
            <HelpTooltip title={help}>
              <span style={{ display: "inline-flex" }}><HelpIcon /></span>
            </HelpTooltip>
          )}
        </Typography>
      </Stack>
      <Component
        value={value}
        onChange={setValue}
        options={options}
        placeholder={placeholder}
        min={min}
        max={max}
        step={step}
        units={units}
        defaultUnit={defaultUnit}
        datalist={datalist}
        play_group={play_group}
      />
    </Stack>
  );
}
