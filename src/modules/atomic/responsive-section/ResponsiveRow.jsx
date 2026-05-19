/* eslint-env browser */

import * as React from "react";
import { Stack, Tooltip, Typography, styled } from "@elementor/ui";

import { SelectInput } from "./inputs/SelectInput";
import { NumberInput } from "./inputs/NumberInput";
import { SwitchInput } from "./inputs/SwitchInput";
import { TextInput } from "./inputs/TextInput";
import { ColorInput } from "./inputs/Color";
import { PlayButtonInput } from "./inputs/PlayButtonInput";
import { RepeaterInput } from "./inputs/RepeaterInput";
import { BorderInput } from "./inputs/BorderInput";
import { TextareaInput } from "./inputs/TextareaInput";
import { SliderInput } from "./inputs/SliderInput";
import { DimensionInput, DimensionsInput } from "./inputs/Dimension";
import { useCellValue } from "./use-cell-value";
import { usePlainValue } from "./use-plain-value";
import { useArrayCellValue } from "./use-array-cell-value";

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
  select: { Component: SelectInput, innerType: "string" },
  text: { Component: TextInput, innerType: "string" },
  textarea: { Component: TextareaInput, innerType: "string" },
  number: { Component: NumberInput, innerType: "number" },
  slider: { Component: SliderInput, innerType: "number" },
  dimension: { Component: DimensionInput, innerType: "object" },
  dimensions: { Component: DimensionsInput, innerType: "object" },
  switch: { Component: SwitchInput, innerType: "boolean" },
  color: { Component: ColorInput, innerType: "string" },
  border: { Component: BorderInput, innerType: "object" },
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
  play_group
}) {
  if (control === "play-button") {
    return (
      <Stack direction="row" justifyContent="flex-end" sx={{ mb: 1 }}>
        <PlayButtonInput play_group={play_group} />
      </Stack>
    );
  }

  if (control === "repeater") {
    return (
      <RepeaterRow
        bind={bind}
        label={label}
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
    />
  ) : (
       
      <PlainRow
        Component={Component}
        innerType={innerType}
        bind={bind}
        label={label}
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
      />
    
  );
}

/* ---------- responsive row (with dot) ---------- */

function ResponsiveCellRow({
  Component,
  bind,
  label,
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
          sx={{ flex: 1, minWidth: 0 }}
        >
          {label}
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
      />
    </Stack>
  );
}

/* ---------- repeater row (whole-list per-bp, with dot) ---------- */

function RepeaterRow({
  bind,
  label,
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
          sx={{ flex: 1, minWidth: 0 }}
        >
          {label}
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
          sx={{ flex: 1, minWidth: 0 }}
        >
          {label}
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
      />
    </Stack>
  );
}
