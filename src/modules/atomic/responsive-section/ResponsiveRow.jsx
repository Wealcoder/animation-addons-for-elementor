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
import { TextShadowInput } from "./inputs/TextShadowInput";
import { TextareaInput } from "./inputs/TextareaInput";
import { SliderInput } from "./inputs/SliderInput";
import { DimensionInput, DimensionsInput } from "./inputs/Dimension";
import { ChooseInput } from "./inputs/ChooseInput";
import { MediaInput } from "./inputs/MediaInput";
import { StaggerInput } from "./inputs/StaggerInput";
import { LinkInput } from "./inputs/LinkInput";
import { useCellValue } from "./use-cell-value";
import { usePlainValue } from "./use-plain-value";
import { useArrayCellValue } from "./use-array-cell-value";
import { CodeInput } from "./inputs/CodeInput";
import { triggerAnimationReplay } from "../editor-bridge/settings-bridge";
import { __privateRunCommandSync as runCommandSync } from '@elementor/editor-v1-adapters';
import { getContainer } from '@elementor/editor-elements';

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
  text_shadow:{ Component: TextShadowInput, innerType: "string" },
  choose:     { Component: ChooseInput,     innerType: "string" },
  // Media picker — value: { id, url, size, sizes } | null
  media:      { Component: MediaInput,      innerType: "object" },
  stagger:    { Component: StaggerInput,    innerType: "object" },
  link:       { Component: LinkInput,       innerType: "string" },
  code:       { Component: CodeInput,       innerType: "string" },
};

const PRESETS = {
  custom: { start: {}, end: {} },
  fadeUp: { start: { opacity: 0, y: 80 }, end: { opacity: 1, y: 0 } },
  blurReveal: { start: { opacity: 0, filter: "blur(20px)", y: 40 }, end: { opacity: 1, filter: "blur(0px)", y: 0 } },
  skewUp: { start: { opacity: 0, y: 100, skewY: 12 }, end: { opacity: 1, y: 0, skewY: 0 } },
  clipReveal: { start: { clipPath: "polygon(0 0, 0 0, 0 100%, 0% 100%)" }, end: { clipPath: "polygon(0 0, 100% 0, 100% 100%, 0 100%)" } },
  scaleIn: { start: { opacity: 0, scale: 0.6 }, end: { opacity: 1, scale: 1 } },
  zoomOut: { start: { opacity: 0, scale: 1.5, filter: "blur(15px)" }, end: { opacity: 1, scale: 1, filter: "blur(0px)" } },
  flipUp3D: { start: { opacity: 0, rotationX: -90, transformOrigin: "50% 100%" }, end: { opacity: 1, rotationX: 0 } },
  swingDrop: { start: { opacity: 0, rotationX: -90, transformOrigin: "50% 0%" }, end: { opacity: 1, rotationX: 0 } },
  elasticPop: { start: { opacity: 0, scale: 0.2, rotation: -15 }, end: { opacity: 1, scale: 1, rotation: 0 } },
  flipY: { start: { opacity: 0, rotationY: 90, transformOrigin: "50% 50%" }, end: { opacity: 1, rotationY: 0 } },
  spinIn: { start: { opacity: 0, rotation: 180, scale: 0.5 }, end: { opacity: 1, rotation: 0, scale: 1 } },
  slideRight: { start: { opacity: 0, x: -100 }, end: { opacity: 1, x: 0 } },
  cinematicFocus: { start: { opacity: 0, scale: 1.15, filter: "blur(12px) brightness(1.5)" }, end: { opacity: 1, scale: 1, filter: "blur(0px) brightness(1)" } },
  maskRevealUp: { start: { clipPath: "inset(100% 0% 0% 0%)", y: 40 }, end: { clipPath: "inset(0% 0% 0% 0%)", y: 0 } },
  perspectiveFall: { start: { opacity: 0, z: 400, rotationX: 25, y: -80 }, end: { opacity: 1, z: 0, rotationX: 0, y: 0 } },
  unfold3D: { start: { opacity: 0, rotationX: -90, scale: 0.9, transformOrigin: "50% 0%" }, end: { opacity: 1, rotationX: 0, scale: 1 } },
  magneticSlide: { start: { opacity: 0, x: -100, skewX: 15 }, end: { opacity: 1, x: 0, skewX: 0 } },
  luxDrift: { start: { opacity: 0, y: 30, filter: "grayscale(100%)" }, end: { opacity: 1, y: 0, filter: "grayscale(0%)" } },
  saasDashboard: { start: { opacity: 0, y: 60, scale: 0.95 }, end: { opacity: 1, y: 0, scale: 1 } },
  ecomUnbox: { start: { clipPath: "inset(20% 20% 20% 20% round 30px)", scale: 1.15, opacity: 0 }, end: { clipPath: "inset(0% 0% 0% 0% round 16px)", scale: 1, opacity: 1 } },
  neonPulse: { start: { opacity: 0, scale: 0.85, boxShadow: "0 0 60px 10px rgba(99, 102, 241, 0.6)" }, end: { opacity: 1, scale: 1, boxShadow: "0 0 0px 0px rgba(99, 102, 241, 0)" } },
  floatIn: { start: { opacity: 0, y: 40, rotation: -2 }, end: { opacity: 1, y: 0, rotation: 0 } }
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
  live_change,
  help,
  settings
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
        play_group={play_group}
        live_change={live_change}
        settings={settings}
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
      live_change={live_change}
      settings={settings}
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
        live_change={live_change}
        settings={settings}
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
  live_change,
  settings,
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

  const handleValueChange = (newVal) => {
    setValue(newVal);

    if (bind === 'aae_anim_effect') {
      let fromProps = [];
      let toProps = [];
      let methodToSet = null;
      const preset = PRESETS[newVal];
      const customPropsBind = 'aae_anim_custom_props';
      const currentProps = settings ? settings[customPropsBind] : null;
      const map = (currentProps && typeof currentProps === 'object' && currentProps.$$type === 'aae-rj')
        ? (currentProps.value || {})
        : {};
      const desktopProps = map['desktop'];

      if (preset) {
        fromProps = Object.entries(preset.start || {}).map(([property, value]) => ({ property, value: String(value) }));
        toProps = Object.entries(preset.end || {}).map(([property, value]) => ({ property, value: String(value) }));
        methodToSet = 'fromTo';
      } else if (newVal === 'custom' && (!desktopProps || desktopProps.length === 0)) {
        fromProps = [
          { property: 'x', value: '80' },
          { property: 'y', value: '80' },
          { property: 'delay', value: '0' },
          { property: 'duration', value: '1.5' }
        ];
      }

      if (fromProps.length > 0 || preset) {
        const customPropsToBind = 'aae_anim_custom_props_to';
        const methodBind = 'aae_anim_method';
        const currentPropsTo = settings ? settings[customPropsToBind] : null;
        const currentMethod = settings ? settings[methodBind] : null;
        const mapTo = (currentPropsTo && typeof currentPropsTo === 'object' && currentPropsTo.$$type === 'aae-rj') ? (currentPropsTo.value || {}) : {};
        
        const nextSettingsToUpdate = {
          [customPropsBind]: { $$type: 'aae-rj', value: { ...map, 'desktop': fromProps } },
          [customPropsToBind]: { $$type: 'aae-rj', value: { ...mapTo, 'desktop': toProps } }
        };

        if (methodToSet) {
          const methodMap = (currentMethod && typeof currentMethod === 'object' && currentMethod.$$type === 'aae-rj') ? (currentMethod.value || {}) : {};
          nextSettingsToUpdate[methodBind] = { $$type: 'aae-rj', value: { ...methodMap, 'desktop': methodToSet } };
        }

        const container = getContainer(elementId);
        if (container) {
          runCommandSync('document/elements/settings', {
            container,
            settings: nextSettingsToUpdate,
            options: { external: true, render: false, renderUI: false },
          });
        }
      }
    }

    if (live_change && play_group) {
      console.log(newVal, play_group);
      setTimeout(() => triggerAnimationReplay(play_group), 50);
    }
  };

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
                if (live_change && play_group) {
                  setTimeout(() => triggerAnimationReplay(play_group), 50);
                }
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
        onChange={handleValueChange}
        options={options}
        placeholder={placeholder}
        min={min}
        max={max}
        step={step}
        units={units}
        defaultUnit={defaultUnit}
        datalist={datalist}
        play_group={play_group}
        live_change={live_change}
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
  play_group,
  live_change,
  settings,
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

  const handleValueChange = (newVal) => {
    setValue(newVal);
    if (live_change && play_group) {
      setTimeout(() => triggerAnimationReplay(play_group), 50);
    }
  };

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
                if (live_change && play_group) {
                  setTimeout(() => triggerAnimationReplay(play_group), 50);
                }
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
        onChange={handleValueChange}
        cells={cells}
        addLabel={addLabel}
        rowDefaults={rowDefaults}
        settings={settings}
        activeBp={activeBp}
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
  live_change,
  settings,
}) {
  const { value, setValue } = usePlainValue({
    propValue,
    bind,
    innerType,
    elementId,
    defaultValue,
  });

  const handleValueChange = (newVal) => {
    setValue(newVal);

    if (bind === 'aae_anim_effect') {
      let fromProps = [];
      let toProps = [];
      let methodToSet = null;
      const preset = PRESETS[newVal];
      const customPropsBind = 'aae_anim_custom_props';
      const currentProps = settings ? settings[customPropsBind] : null;
      const map = (currentProps && typeof currentProps === 'object' && currentProps.$$type === 'aae-rj')
        ? (currentProps.value || {})
        : {};
      const desktopProps = map['desktop'];

      if (preset) {
        fromProps = Object.entries(preset.start || {}).map(([property, value]) => ({ property, value: String(value) }));
        toProps = Object.entries(preset.end || {}).map(([property, value]) => ({ property, value: String(value) }));
        methodToSet = 'fromTo';
      } else if (newVal === 'custom' && (!desktopProps || desktopProps.length === 0)) {
        fromProps = [
          { property: 'x', value: '80' },
          { property: 'y', value: '80' },
          { property: 'delay', value: '0' },
          { property: 'duration', value: '1.5' }
        ];
      }

      if (fromProps.length > 0 || preset) {
        const customPropsToBind = 'aae_anim_custom_props_to';
        const methodBind = 'aae_anim_method';
        const currentPropsTo = settings ? settings[customPropsToBind] : null;
        const currentMethod = settings ? settings[methodBind] : null;
        const mapTo = (currentPropsTo && typeof currentPropsTo === 'object' && currentPropsTo.$$type === 'aae-rj') ? (currentPropsTo.value || {}) : {};
        
        const nextSettingsToUpdate = {
          [customPropsBind]: { $$type: 'aae-rj', value: { ...map, 'desktop': fromProps } },
          [customPropsToBind]: { $$type: 'aae-rj', value: { ...mapTo, 'desktop': toProps } }
        };

        if (methodToSet) {
          const methodMap = (currentMethod && typeof currentMethod === 'object' && currentMethod.$$type === 'aae-rj') ? (currentMethod.value || {}) : {};
          nextSettingsToUpdate[methodBind] = { $$type: 'aae-rj', value: { ...methodMap, 'desktop': methodToSet } };
        }

        const container = getContainer(elementId);
        if (container) {
          runCommandSync('document/elements/settings', {
            container,
            settings: nextSettingsToUpdate,
            options: { external: true, render: false, renderUI: false },
          });
        }
      }
    }

    if (live_change && play_group) {
      setTimeout(() => triggerAnimationReplay(play_group), 50);
    }
  };

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
        onChange={handleValueChange}
        options={options}
        placeholder={placeholder}
        min={min}
        max={max}
        step={step}
        units={units}
        defaultUnit={defaultUnit}
        datalist={datalist}
        play_group={play_group}
        live_change={live_change}
      />
    </Stack>
  );
}
