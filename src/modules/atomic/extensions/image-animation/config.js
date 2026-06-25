/* eslint-env browser */

import { CUSTOM_PROPERTY_OPTIONS } from '../regular-animation/config';
import { PRESETS, presetRowPatch } from '../regular-animation/presets';

/**
 * Image Animation section — REPEATER architecture.
 *
 * Each row is a full independent image interaction (effect + trigger +
 * config). Effects:
 *   - reveal / scale / stretch : built-in presets (bespoke runtime logic)
 *   - premium presets          : fadeUp / blurReveal / … (from the shared
 *     regular-animation PRESETS table; selecting one fills the custom-props
 *     rows + sets method=fromTo, and the props are removable like regular)
 *   - custom                   : user-defined GSAP from/to props
 *
 * Concurrency rule (shared): page_load + on_scroll + play_with_scroll share
 * ONE slot; click + hover unlimited.
 */

// reveal/scale/stretch are bespoke; the rest come from the shared preset table
// (same as regular animation) plus a 'custom' free-form entry.
const PRESET_EFFECT_OPTIONS = Object.keys(PRESETS)
	.filter((k) => k !== 'custom')
	.map((k) => ({ value: k, label: k.replace(/([A-Z])/g, ' $1').replace(/^./, (c) => c.toUpperCase()).trim() }));

const EFFECT_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'custom', label: 'Custom Animation' },
	{ value: 'reveal', label: 'Reveal' },
	{ value: 'scale', label: 'Scale' },
	{ value: 'stretch', label: 'Stretch' },
	...PRESET_EFFECT_OPTIONS,
];

// Built-in effects with bespoke runtime; everything else uses custom props.
const BUILTIN_EFFECTS = ['reveal', 'scale', 'stretch'];

const TRIGGER_OPTIONS = [
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'click', label: 'On Click' },
	{ value: 'mouseover', label: 'On Hover' },
	{ value: 'on_slide_change', label: 'On Slide Change' },
];

const START_FROM_OPTIONS = [
	{ value: 'left', label: 'Left' },
	{ value: 'right', label: 'Right' },
	{ value: 'top', label: 'Top' },
	{ value: 'bottom', label: 'Bottom' },
];

const METHOD_OPTIONS = [
	{ value: 'from', label: 'From' },
	{ value: 'to', label: 'To' },
	{ value: 'fromTo', label: 'From To' },
];
// "Set" (instant state) only for a custom animation — premium presets carry
// their own from/to, so instant set is meaningless there.
const SET_METHOD_OPTION = { value: 'set', label: 'Set' };
const methodOptionsFor = (r) =>
	(rowEffect(r) === 'custom') ? [...METHOD_OPTIONS, SET_METHOD_OPTION] : METHOD_OPTIONS;

const EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce', label: 'Bounce' },
	{ value: 'back', label: 'Back' },
	{ value: 'elastic', label: 'Elastic' },
	{ value: 'slowmo', label: 'Slowmo' },
	{ value: 'sine', label: 'Sine' },
	{ value: 'expo', label: 'Expo' },
	{ value: 'none', label: 'None' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
];

/* ---------- per-row predicates (flat rowData) ---------- */

const SCROLL_TRIGGERS = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];

const rowEffect = (r) => r?.effect || 'none';
const rowIsAnimated = (r) => rowEffect(r) !== 'none';
const rowTrigger = (r) => r?.trigger || 'on_scroll';
const rowIsScroll = (r) => SCROLL_TRIGGERS.includes(rowTrigger(r));
const rowIsSelector = (r) => SELECTOR_TRIGGERS.includes(rowTrigger(r));

const rowIsReveal = (r) => rowEffect(r) === 'reveal';
const rowIsScale = (r) => rowEffect(r) === 'scale';
// "Props" effects = anything NOT a built-in (custom + every premium preset).
// These expose the method + custom-props repeaters; selecting a preset
// auto-fills those props.
const rowUsesProps = (r) => rowIsAnimated(r) && !BUILTIN_EFFECTS.includes(rowEffect(r));

const CUSTOM_PROPS_CELLS = [
	{
		bind: 'property', type: 'select', placeholder: 'Property',
		options: CUSTOM_PROPERTY_OPTIONS, width: 7, freeSolo: true, unique: true,
	},
	{ bind: 'value', type: 'dynamic-value', placeholder: 'value', width: 5 },
];

/* ---------- per-row field schema ---------- */

const ROW_FIELDS = [
	{
		bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'reveal',
		// Selecting a premium preset fills custom_props / custom_props_to +
		// sets method=fromTo. reveal/scale/stretch/custom return null (no fill).
		onSet: (_row, val) => (BUILTIN_EFFECTS.includes(val) || val === 'custom' ? null : presetRowPatch(val)),
	},
	{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: rowIsAnimated },

	{
		bind: 'trigger_selector', label: 'Trigger Selector', control: 'text', placeholder: '.my-class',
		when: (r) => rowIsAnimated(r) && rowIsSelector(r),
	},
	{
		bind: 'start_position', label: 'Start', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'top center', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},
	{
		bind: 'end_position', label: 'End', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'bottom bottom', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},

	// Reveal-only
	{ bind: 'start_from', label: 'Animation To', control: 'select', options: START_FROM_OPTIONS, defaultValue: 'right', when: rowIsReveal },

	// Scale-only
	{ bind: 'scale_start', label: 'Start Scale', control: 'number', defaultValue: 0.5, when: rowIsScale },
	{ bind: 'scale_end', label: 'End Scale', control: 'number', defaultValue: 1, when: rowIsScale },

	// Custom + preset effects: method + removable props repeaters. Preset
	// effects fill these on select; the user can still tweak / remove rows.
	{ bind: 'method', label: 'Method', control: 'select', options: methodOptionsFor, defaultValue: 'from', when: rowUsesProps },
	{
		bind: 'custom_props',
		label: (r) => (r?.method === 'fromTo' ? 'From Properties' : 'Custom Properties'),
		control: 'repeater', addLabel: 'Add Property',
		rowDefaults: { property: '', value: '' }, cells: CUSTOM_PROPS_CELLS, when: rowUsesProps,
	},
	{
		bind: 'custom_props_to', label: 'To Properties', control: 'repeater', addLabel: 'Add Property',
		rowDefaults: { property: '', value: '' }, cells: CUSTOM_PROPS_CELLS,
		when: (r) => rowUsesProps(r) && r?.method === 'fromTo',
	},

	// Shared timing
	{ bind: 'delay', label: 'Delay', control: 'slider', min: 0, max: 10, step: 0.05, defaultValue: 0, when: rowIsAnimated },
	{ bind: 'duration', label: 'Duration', control: 'slider', min: 0, max: 10, step: 0.1, defaultValue: 1.5, when: rowIsAnimated },
	{ bind: 'ease', label: 'Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'power2.out', when: rowIsAnimated },

	{ bind: 'markers', label: 'Markers', control: 'switch', defaultValue: false, when: (r) => rowIsAnimated(r) && rowIsScroll(r) },
];

const ROW_DEFAULTS = {
	effect: 'reveal',
	trigger: 'on_scroll',
	start_from: 'right',
	scale_start: 0.5,
	scale_end: 1,
	method: 'from',
	delay: 0,
	duration: 1.5,
	ease: 'power2.out',
	start_position: 'top center',
	end_position: 'bottom bottom',
	custom_props: [],
	custom_props_to: [],
};

const config = {
	anchorKey: 'aae-section-aae-image-animation',
	bindPrefix: 'aae_img_',
	fields: [
		{
			bind: 'interactions',
			label: 'Interactions',
			control: 'interactions',
			defaultValue: [],
			responsive: true,
			play_group: 'aae_img_',
			live_change: false,
			addLabel: 'Add Interaction',
			rowFields: ROW_FIELDS,
			rowDefaults: ROW_DEFAULTS,
			help: 'Each interaction is an independent image animation: trigger + effect + config. Page-load and scroll triggers allow one each; click and hover are unlimited.',
		},
		// No global "Enable On Editor" / "Play" — each interaction row has its
		// own ▶ play button for isolated preview.
	],
};

export default config;
