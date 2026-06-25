/* eslint-env browser */

import { PREMIUM_EFFECTS } from './presets';

/**
 * Text Animation section — REPEATER architecture.
 *
 * The whole section is a single `interactions` repeater: every row is a full
 * independent text interaction (effect + trigger + per-effect config). Text
 * effects are preset-only (no custom GSAP props) — selecting the effect is
 * enough; the runtime owns the SplitText + tween for each.
 *
 * Concurrency rule (shared with regular/image, enforced in the repeater UI,
 * runtime, and PHP):
 *   - on_page_load                  → max 1 per element
 *   - on_scroll / play_with_scroll  → one slot shared (max 1 total)
 *   - mouseover / click             → unlimited
 *
 * Per-row field `when(rowData, bp)` predicates read the FLAT row object.
 */

/* ---------- option lists ---------- */

const EFFECT_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'char', label: 'Character' },
	{ value: 'word', label: 'Word' },
	{ value: 'text_move', label: 'Text Move' },
	{ value: 'text_reveal', label: 'Text Reveal' },
	{ value: 'text_scale', label: 'Text Scale' },
	{ value: 'text_invert', label: 'Text Invert' },

	...Object.keys(PREMIUM_EFFECTS).map((key) => ({
		value: key.toLowerCase().replace(/[^a-z0-9]+/g, '_'),
		label: key,
	})),
];

const TRIGGER_OPTIONS = [
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'click', label: 'On Click' },
	{ value: 'mouseover', label: 'On Hover' },
	{ value: 'on_slide_change', label: 'On Slide Change' },
];

const ROTATION_DIR_OPTIONS = [
	{ value: 'x', label: 'X' },
	{ value: 'y', label: 'Y' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom', 'top 25%', 'top 50%', 'top 75%',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
];

const TRANSFORM_ORIGIN_OPTIONS = [
	'top left', 'top center', 'top right',
	'center left', 'center center', 'center right',
	'bottom left', 'bottom center', 'bottom right',
	'top center -50',
];

const EASE_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce', label: 'Bounce' },
	{ value: 'back', label: 'Back' },
	{ value: 'elastic', label: 'Elastic' },
	{ value: 'slowmo', label: 'Slowmo' },
	{ value: 'sine', label: 'Sine' },
	{ value: 'expo', label: 'Expo' },
];

const SCALE_BREAK_OPTIONS = [
	{ value: 'lines', label: 'Lines' },
	{ value: 'words', label: 'Words' },
	{ value: 'chars', label: 'Chars' },
];

/* ---------- per-row predicates (flat rowData) ---------- */

const PREMIUM_IDS = Object.keys(PREMIUM_EFFECTS).map((k) => k.toLowerCase().replace(/[^a-z0-9]+/g, '_'));
const DURATION_EFFECTS = ['char', 'word', 'text_reveal', 'text_move', 'text_scale'];
const TRANSLATE_EFFECTS = ['char', 'word'];
const SCROLL_TRIGGERS = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];

const rowEffect = (r) => r?.effect || 'none';
const rowIsPremium = (r) => PREMIUM_IDS.includes(rowEffect(r));
const rowIsAnimated = (r) => rowEffect(r) !== 'none';
const rowTrigger = (r) => r?.trigger || 'on_scroll';
const rowIsScroll = (r) => SCROLL_TRIGGERS.includes(rowTrigger(r));
const rowIsSelector = (r) => SELECTOR_TRIGGERS.includes(rowTrigger(r));

const rowIsDuration = (r) => rowIsPremium(r) || DURATION_EFFECTS.includes(rowEffect(r));
const rowIsTranslate = (r) => TRANSLATE_EFFECTS.includes(rowEffect(r));
const rowIsMove = (r) => rowEffect(r) === 'text_move';
const rowIsInvert = (r) => rowEffect(r) === 'text_invert';
const rowIsScale = (r) => rowEffect(r) === 'text_scale';

/* ---------- per-row field schema ---------- */

const ROW_FIELDS = [
	{ bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'char' },
	{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: rowIsAnimated },

	{
		bind: 'trigger_selector', label: 'Trigger Selector', control: 'text', placeholder: '.my-class',
		when: (r) => rowIsAnimated(r) && rowIsSelector(r),
	},
	{
		bind: 'start_position', label: 'Start', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'top 85%', when: (r) => rowIsAnimated(r) && rowIsScroll(r) && !rowIsInvert(r),
	},
	{
		bind: 'end_position', label: 'End', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'bottom 30%', when: (r) => rowIsAnimated(r) && rowIsScroll(r) && !rowIsInvert(r),
	},
	// Invert uses its own scroll positions.
	{
		bind: 'invert_start', label: 'Start', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'top 85%', when: (r) => rowIsInvert(r) && rowIsScroll(r),
	},
	{
		bind: 'invert_end', label: 'End', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
		placeholder: 'bottom center', when: (r) => rowIsInvert(r) && rowIsScroll(r),
	},

	{ bind: 'delay', label: 'Delay', control: 'slider', min: 0, max: 10, step: 0.05, defaultValue: 0.15, when: (r) => rowIsAnimated(r) && !rowIsInvert(r) },
	{ bind: 'duration', label: 'Duration', control: 'slider', min: 0, max: 10, step: 0.1, defaultValue: 1, when: rowIsDuration },
	{ bind: 'stagger', label: 'Stagger', control: 'slider', min: -1, max: 5, step: 0.01, defaultValue: 0.02, when: rowIsDuration },
	{ bind: 'ease', label: 'Easing', control: 'select', options: EASE_OPTIONS, defaultValue: '', when: rowIsPremium },

	{ bind: 'translate_x', label: 'Transform-X', control: 'number', defaultValue: 20, when: rowIsTranslate },
	{ bind: 'translate_y', label: 'Transform-Y', control: 'number', defaultValue: 0, when: rowIsTranslate },

	{ bind: 'rotation_dir', label: 'Rotation Direction', control: 'select', options: ROTATION_DIR_OPTIONS, defaultValue: 'x', when: rowIsMove },
	{ bind: 'rotation', label: 'Rotation Value', control: 'number', defaultValue: -80, when: rowIsMove },
	{ bind: 'transform_origin', label: 'Transform Origin', control: 'text', datalist: TRANSFORM_ORIGIN_OPTIONS, placeholder: 'top center -50', when: rowIsMove },

	// Scale-specific
	{ bind: 'scale_ease', label: 'Scale Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'back', when: rowIsScale },
	{ bind: 'scale_num', label: 'Scale', control: 'number', defaultValue: 1.5, when: rowIsScale },
	{ bind: 'scale_break', label: 'Text Break By', control: 'select', options: SCALE_BREAK_OPTIONS, defaultValue: 'lines', when: rowIsScale },

	{ bind: 'markers', label: 'Markers', control: 'switch', defaultValue: false, when: (r) => rowIsAnimated(r) && rowIsScroll(r) },
];

const ROW_DEFAULTS = {
	effect: 'char',
	trigger: 'on_scroll',
	delay: 0.15,
	duration: 1,
	stagger: 0.02,
	translate_x: 20,
	translate_y: 0,
	start_position: 'top 85%',
	end_position: 'bottom 30%',
};

/* ---------- the section table ---------- */

const config = {
	anchorKey: 'aae-section-aae-text-animation',
	bindPrefix: 'aae_text_',
	fields: [
		{
			bind: 'interactions',
			label: 'Interactions',
			control: 'interactions',
			defaultValue: [],
			responsive: true,
			play_group: 'aae_text_',
			live_change: false,
			addLabel: 'Add Interaction',
			rowFields: ROW_FIELDS,
			rowDefaults: ROW_DEFAULTS,
			help: 'Each interaction is an independent text animation: trigger + effect + config. Page-load and scroll triggers allow one each; click and hover are unlimited.',
		},
		// No global "Enable On Editor" / "Play" — each interaction row has its
		// own ▶ play button for isolated preview.
	],
};

export default config;
