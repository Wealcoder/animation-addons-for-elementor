/* eslint-env browser */

import { presetRowPatch } from './presets';

/**
 * Image Advanced Animation section — REPEATER architecture, mirrors
 * ImageAnimation's config.js exactly (effect + trigger + timing + per-effect
 * fields per row). Ported from the prototype's 8 cinematic presets
 * (z_temp/Image Animation/script.js): cinematicMask, scaleAnimation,
 * sliceShutter, mosaicDepth, liquidClip, orbitTilt, zoomTunnel,
 * scrollParallax. Each preset only exposes the fields it actually uses
 * (`when` predicates below mirror the prototype's per-preset `controls`
 * list); selecting a preset auto-fills its signature defaults via
 * presetRowPatch().
 */

const EFFECT_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'cinematicMask', label: '1. Cinematic Mask' },
	{ value: 'scaleAnimation', label: '2. Scale Animation' },
	{ value: 'sliceShutter', label: '3. Slice Shutter' },
	{ value: 'mosaicDepth', label: '4. Mosaic Depth' },
	{ value: 'liquidClip', label: '5. Liquid Clip' },
	{ value: 'orbitTilt', label: '6. 3D Orbit Tilt' },
	{ value: 'zoomTunnel', label: '7. Zoom Tunnel' },
	{ value: 'scrollParallax', label: '8. Scroll Parallax' },
];

const TRIGGER_OPTIONS = [
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'click', label: 'On Click' },
	{ value: 'mouseover', label: 'On Hover' },
	{ value: 'on_slide_change', label: 'On Slide Change' },
];

const WRAPPER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom', label: 'Custom' },
];

const EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'power3.out', label: 'Power3.out' },
	{ value: 'power4.out', label: 'Power4.out' },
	{ value: 'expo.out', label: 'Expo Out' },
	{ value: 'expo.inOut', label: 'Expo In Out' },
	{ value: 'back.out(1.35)', label: 'Back Out' },
	{ value: 'sine.inOut', label: 'Sine In Out' },
	{ value: 'none', label: 'Linear' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
];

const DIRECTION_OPTIONS = [
	{ value: 'bottomToTop', label: 'Bottom to top' },
	{ value: 'topToBottom', label: 'Top to bottom' },
	{ value: 'leftToRight', label: 'Left to right' },
	{ value: 'rightToLeft', label: 'Right to left' },
	{ value: 'centerOut', label: 'Center out' },
];

const MOVE_DIRECTION_OPTIONS = [
	{ value: 'none', label: 'No movement' },
	{ value: 'bottomToTop', label: 'Bottom to top' },
	{ value: 'topToBottom', label: 'Top to bottom' },
	{ value: 'leftToRight', label: 'Left to right' },
	{ value: 'rightToLeft', label: 'Right to left' },
];

const ORBIT_DIRECTION_OPTIONS = [
	{ value: 'left', label: 'From left' },
	{ value: 'right', label: 'From right' },
	{ value: 'top', label: 'From top' },
	{ value: 'bottom', label: 'From bottom' },
];

const PARALLAX_DIRECTION_OPTIONS = [
	{ value: 'up', label: 'Up' },
	{ value: 'down', label: 'Down' },
	{ value: 'left', label: 'Left' },
	{ value: 'right', label: 'Right' },
	{ value: 'diagonalUp', label: 'Diagonal up' },
	{ value: 'diagonalDown', label: 'Diagonal down' },
];

const SLICE_AXIS_OPTIONS = [
	{ value: 'vertical', label: 'Vertical strips' },
	{ value: 'horizontal', label: 'Horizontal bands' },
];

const SLICE_DIRECTION_OPTIONS = [
	{ value: 'alternate', label: 'Alternate' },
	{ value: 'bottomToTop', label: 'Bottom to top' },
	{ value: 'topToBottom', label: 'Top to bottom' },
	{ value: 'leftToRight', label: 'Left to right' },
	{ value: 'rightToLeft', label: 'Right to left' },
];

const ORIGIN_OPTIONS = [
	{ value: 'center', label: 'Center' },
	{ value: 'top', label: 'Top' },
	{ value: 'bottom', label: 'Bottom' },
	{ value: 'left', label: 'Left' },
	{ value: 'right', label: 'Right' },
	{ value: 'topLeft', label: 'Top left' },
	{ value: 'topRight', label: 'Top right' },
	{ value: 'bottomLeft', label: 'Bottom left' },
	{ value: 'bottomRight', label: 'Bottom right' },
];

const TILE_ORDER_OPTIONS = [
	{ value: 'random', label: 'Random' },
	{ value: 'center', label: 'Center' },
	{ value: 'edges', label: 'Edges' },
	{ value: 'start', label: 'Start' },
	{ value: 'end', label: 'End' },
];

/* ---------- per-row predicates (flat rowData) ---------- */

const SCROLL_TRIGGERS = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];

const rowEffect = (r) => r?.effect || 'none';
const rowIsAnimated = (r) => rowEffect(r) !== 'none';
const rowTrigger = (r) => r?.trigger || 'on_scroll';
const rowIsScroll = (r) => SCROLL_TRIGGERS.includes(rowTrigger(r));
const rowIsSelector = (r) => SELECTOR_TRIGGERS.includes(rowTrigger(r));
const rowWrapperCustom = (r) => r?.wrapper === 'custom';

const isOneOf = (...effects) => (r) => effects.includes(rowEffect(r));

const usesDirection = isOneOf('cinematicMask', 'liquidClip');
const usesStartEndScale = isOneOf('cinematicMask', 'scaleAnimation', 'liquidClip', 'orbitTilt', 'zoomTunnel', 'scrollParallax');
const usesImageShift = isOneOf('cinematicMask', 'scaleAnimation', 'liquidClip');
const usesTravel = isOneOf('cinematicMask', 'orbitTilt');
const usesTilt = isOneOf('cinematicMask', 'zoomTunnel');
const usesBlur = isOneOf('scaleAnimation', 'liquidClip');
const usesBrightness = isOneOf('sliceShutter', 'mosaicDepth', 'orbitTilt', 'zoomTunnel');
const usesSaturation = isOneOf('sliceShutter', 'mosaicDepth', 'liquidClip', 'orbitTilt');
const usesDepth = isOneOf('sliceShutter', 'mosaicDepth', 'orbitTilt', 'zoomTunnel');
const usesStagger = isOneOf('sliceShutter', 'mosaicDepth');
const usesSweep = isOneOf('cinematicMask', 'liquidClip', 'orbitTilt');
const usesShadeOpacity = isOneOf('cinematicMask', 'scrollParallax');
const usesOrigin = isOneOf('scaleAnimation', 'zoomTunnel');
const usesRotationX = isOneOf('orbitTilt', 'scrollParallax');

/* ---------- per-row field schema ---------- */

const ROW_FIELDS = [
	{
		bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'cinematicMask',
		// Selecting a preset auto-fills the fields it uses with its signature defaults.
		onSet: (_row, val) => presetRowPatch(val),
	},
	{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: rowIsAnimated },

	{
		bind: 'trigger_selector', label: 'Trigger Selector', control: 'text', placeholder: '.my-class',
		when: (r) => rowIsAnimated(r) && rowIsSelector(r),
	},
	{
		bind: 'wrapper', label: 'Wrapper', control: 'select', options: WRAPPER_OPTIONS, defaultValue: 'default',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},
	{
		bind: 'start_trigger', label: 'Start Trigger Selector', control: 'text', placeholder: '.my-start-el',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r) && rowWrapperCustom(r),
	},
	{
		bind: 'end_trigger', label: 'End Trigger Selector', control: 'text', placeholder: '.my-end-el',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r) && rowWrapperCustom(r),
	},
	{
		bind: 'start_position', label: 'Start', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'top center', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},
	{
		bind: 'end_position', label: 'End', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'bottom bottom', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},

	/* ---- preset-specific fields ---- */
	{ bind: 'direction', label: 'Direction', control: 'select', options: DIRECTION_OPTIONS, defaultValue: 'bottomToTop', when: usesDirection },
	{ bind: 'move_direction', label: 'Move Direction', control: 'select', options: MOVE_DIRECTION_OPTIONS, defaultValue: 'none', when: isOneOf('scaleAnimation') },
	{ bind: 'orbit_direction', label: 'Orbit Direction', control: 'select', options: ORBIT_DIRECTION_OPTIONS, defaultValue: 'left', when: isOneOf('orbitTilt') },
	{ bind: 'parallax_direction', label: 'Parallax Direction', control: 'select', options: PARALLAX_DIRECTION_OPTIONS, defaultValue: 'up', when: isOneOf('scrollParallax') },
	{ bind: 'slice_axis', label: 'Slice Axis', control: 'select', options: SLICE_AXIS_OPTIONS, defaultValue: 'vertical', when: isOneOf('sliceShutter') },
	{ bind: 'slice_direction', label: 'Slice Direction', control: 'select', options: SLICE_DIRECTION_OPTIONS, defaultValue: 'alternate', when: isOneOf('sliceShutter') },
	{ bind: 'origin', label: 'Transform Origin', control: 'select', options: ORIGIN_OPTIONS, defaultValue: 'center', when: usesOrigin },
	{ bind: 'tile_order', label: 'Tile Order', control: 'select', options: TILE_ORDER_OPTIONS, defaultValue: 'random', when: isOneOf('mosaicDepth') },

	{ bind: 'start_scale', label: 'Start Scale', control: 'slider', min: 0.2, max: 2.5, step: 0.01, defaultValue: 1, when: usesStartEndScale },
	{ bind: 'end_scale', label: 'End Scale', control: 'slider', min: 0.5, max: 1.8, step: 0.01, defaultValue: 1, when: usesStartEndScale },
	{ bind: 'image_shift', label: 'Image Shift (%)', control: 'slider', min: 0, max: 40, step: 1, defaultValue: 0, when: usesImageShift },
	{ bind: 'travel', label: 'Travel Distance (px)', control: 'slider', min: 0, max: 220, step: 1, defaultValue: 0, when: usesTravel },
	{ bind: 'tilt', label: 'Tilt (deg)', control: 'slider', min: 0, max: 35, step: 1, defaultValue: 0, when: usesTilt },
	{ bind: 'rotation', label: 'Rotation (deg)', control: 'slider', min: -90, max: 90, step: 1, defaultValue: 0, when: isOneOf('scaleAnimation') },
	{ bind: 'rotation_x', label: 'Rotate X (deg)', control: 'slider', min: -90, max: 90, step: 1, defaultValue: 0, when: usesRotationX },
	{ bind: 'rotation_y', label: 'Rotate Y (deg)', control: 'slider', min: -90, max: 90, step: 1, defaultValue: 0, when: isOneOf('orbitTilt') },
	{ bind: 'rotation_z', label: 'Rotate Z (deg)', control: 'slider', min: -45, max: 45, step: 1, defaultValue: 0, when: isOneOf('orbitTilt') },
	{ bind: 'blur', label: 'Blur (px)', control: 'slider', min: 0, max: 30, step: 1, defaultValue: 0, when: usesBlur },
	{ bind: 'brightness', label: 'Brightness', control: 'slider', min: 0.5, max: 2, step: 0.05, defaultValue: 1, when: usesBrightness },
	{ bind: 'saturation', label: 'Saturation', control: 'slider', min: 0, max: 2.5, step: 0.05, defaultValue: 1, when: usesSaturation },
	{ bind: 'radius', label: 'Radius (px)', control: 'slider', min: 0, max: 80, step: 1, defaultValue: 0, when: isOneOf('cinematicMask') },
	{ bind: 'shade_opacity', label: 'Shade Opacity', control: 'slider', min: 0, max: 1, step: 0.01, defaultValue: 0, when: usesShadeOpacity },
	{ bind: 'sweep', label: 'Light Sweep', control: 'switch', defaultValue: false, when: usesSweep },
	{ bind: 'fade', label: 'Fade', control: 'switch', defaultValue: false, when: isOneOf('scaleAnimation') },

	{ bind: 'slice_count', label: 'Slice Count', control: 'slider', min: 3, max: 28, step: 1, defaultValue: 12, when: isOneOf('sliceShutter') },
	{ bind: 'slice_skew', label: 'Slice Skew (deg)', control: 'slider', min: 0, max: 24, step: 1, defaultValue: 0, when: isOneOf('sliceShutter') },
	{ bind: 'depth', label: '3D Depth', control: 'slider', min: 0, max: 520, step: 5, defaultValue: 0, when: usesDepth },
	{ bind: 'stagger', label: 'Stagger (s)', control: 'slider', min: 0, max: 1.8, step: 0.01, defaultValue: 0, when: usesStagger },

	{ bind: 'tile_columns', label: 'Tile Columns', control: 'slider', min: 2, max: 10, step: 1, defaultValue: 6, when: isOneOf('mosaicDepth') },
	{ bind: 'tile_rows', label: 'Tile Rows', control: 'slider', min: 2, max: 8, step: 1, defaultValue: 5, when: isOneOf('mosaicDepth') },
	{ bind: 'tile_scatter', label: 'Tile Scatter (px)', control: 'slider', min: 0, max: 220, step: 1, defaultValue: 0, when: isOneOf('mosaicDepth') },
	{ bind: 'tile_start_scale', label: 'Tile Start Scale', control: 'slider', min: 0.1, max: 1.4, step: 0.01, defaultValue: 1, when: isOneOf('mosaicDepth') },
	{ bind: 'tile_rotation', label: 'Tile Rotation (deg)', control: 'slider', min: 0, max: 120, step: 1, defaultValue: 0, when: isOneOf('mosaicDepth') },

	{ bind: 'wave_size', label: 'Wave Size (%)', control: 'slider', min: 4, max: 34, step: 1, defaultValue: 12, when: isOneOf('liquidClip') },

	{ bind: 'circle_start', label: 'Circle Start (%)', control: 'slider', min: 0, max: 50, step: 1, defaultValue: 0, when: isOneOf('zoomTunnel') },
	{ bind: 'circle_end', label: 'Circle End (%)', control: 'slider', min: 50, max: 125, step: 1, defaultValue: 100, when: isOneOf('zoomTunnel') },

	{ bind: 'frame_distance', label: 'Frame Distance (px)', control: 'slider', min: 0, max: 240, step: 1, defaultValue: 0, when: isOneOf('scrollParallax') },
	{ bind: 'image_distance', label: 'Image Distance (%)', control: 'slider', min: 0, max: 40, step: 1, defaultValue: 0, when: isOneOf('scrollParallax') },

	/* ---- shared timing ---- */
	{ bind: 'delay', label: 'Delay', control: 'slider', min: 0, max: 10, step: 0.05, defaultValue: 0, when: rowIsAnimated },
	{ bind: 'duration', label: 'Duration', control: 'slider', min: 0, max: 10, step: 0.1, defaultValue: 1.2, when: rowIsAnimated },
	{ bind: 'ease', label: 'Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'expo.out', when: rowIsAnimated },

	{ bind: 'markers', label: 'Markers', control: 'switch', defaultValue: false, when: (r) => rowIsAnimated(r) && rowIsScroll(r) },
];

const ROW_DEFAULTS = {
	effect: 'cinematicMask',
	trigger: 'on_scroll',
	start_position: 'top center',
	end_position: 'bottom bottom',
	wrapper: 'default',
	delay: 0,
	// Seed the cinematicMask preset so a freshly-added interaction animates
	// with sensible values immediately. Mirrors regular-animation's ROW_DEFAULTS.
	...presetRowPatch('cinematicMask'),
};

const config = {
	anchorKey: 'aae-section-aae-image-advanced-animation',
	bindPrefix: 'aae_imgadv_',
	fields: [
		{
			bind: 'interactions',
			label: 'Interactions',
			control: 'interactions',
			defaultValue: [],
			responsive: true,
			play_group: 'aae_imgadv_',
			live_change: false,
			addLabel: 'Add Interaction',
			rowFields: ROW_FIELDS,
			rowDefaults: ROW_DEFAULTS,
			help: 'Each interaction is an independent cinematic image animation: trigger + preset + config. Page-load and scroll triggers allow one each; click and hover are unlimited.',
		},
	],
};

export default config;
