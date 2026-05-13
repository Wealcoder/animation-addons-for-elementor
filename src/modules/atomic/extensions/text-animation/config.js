/* eslint-env browser */

import {
	isAnimated,
	isDurationEffect,
	isInvert,
	isMove,
	isScale,
	isSpin,
	isTranslateEffect,
	showEnableEditor,
	showPlayButton,
	showEndCustom,
	showScrollCustomBlock,
	showSpinScrollFields,
	showStartCustom,
	showTriggerSelector,
	showWrapper,
	showWrapperSelector,
} from './predicates';

/**
 * Declarative table for the Text Animation section. Same structure as
 * regular-animation/config.js — see that file for full notes.
 */

/* ---------- option lists ---------- */

const EFFECT_OPTIONS = [
	{ value: 'none',        label: 'None' },
	{ value: 'char',        label: 'Character' },
	{ value: 'word',        label: 'Word' },
	{ value: 'text_move',   label: 'Text Move' },
	{ value: 'text_reveal', label: 'Text Reveal' },
	{ value: 'text_scale',  label: 'Text Scale' },
	{ value: 'text_invert', label: 'Text Invert' },
	{ value: 'text_spin',   label: '3D Spin' },
];

const TRIGGER_OPTIONS = [
	{ value: 'in-view',          label: 'In View' },
	{ value: 'on_scroll',        label: 'On Scroll' },
	{ value: 'on_page_load',     label: 'On Page Load' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'mouseover',        label: 'On Hover' },
	{ value: 'click',            label: 'On Click' },
];

const WRAPPER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom',  label: 'Custom' },
];

const ROTATION_DIR_OPTIONS = [
	{ value: 'x', label: 'X' },
	{ value: 'y', label: 'Y' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
	'custom',
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const SCALE_EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce',     label: 'Bounce' },
	{ value: 'back',       label: 'Back' },
	{ value: 'elastic',    label: 'Elastic' },
	{ value: 'slowmo',     label: 'Slowmo' },
	{ value: 'stepped',    label: 'Stepped' },
	{ value: 'sine',       label: 'Sine' },
	{ value: 'expo',       label: 'Expo' },
];

const SCALE_BREAK_OPTIONS = [
	{ value: 'lines', label: 'Lines' },
	{ value: 'words', label: 'Words' },
	{ value: 'chars', label: 'Chars' },
];

/* ---------- the table ---------- */

const config = {
	anchorKey:  'aae-section-aae-text-animation',
	bindPrefix: 'aae_text_',
	fields: [
		{ bind: 'effect',  label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'none' },

		{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: isAnimated },

		{ bind: 'trigger_selector', label: 'Trigger Selector', control: 'text',
		  defaultValue: '', placeholder: '.my-class', when: showTriggerSelector },

		{ bind: 'wrapper', label: 'Text Wrapper', control: 'select', options: WRAPPER_OPTIONS, defaultValue: 'default', when: showWrapper },

		{ bind: 'wrapper_selector', label: 'Custom Wrapper Selector', control: 'text',
		  defaultValue: '', placeholder: '.my-wrapper', when: showWrapperSelector },

		{ bind: 'start_trigger',  label: 'Start Trigger', control: 'text',
		  defaultValue: '', placeholder: '.start_area', when: showScrollCustomBlock },
		{ bind: 'end_trigger',    label: 'End Trigger', control: 'text',
		  defaultValue: '', placeholder: '.end_area',   when: showScrollCustomBlock },
		{ bind: 'start_position', label: 'Start', control: 'select',
		  options: SCROLL_POSITION_OPTIONS, defaultValue: 'top top', when: showScrollCustomBlock },
		{ bind: 'start_custom',   label: 'Custom Start', control: 'text',
		  defaultValue: 'top top', placeholder: 'top top+=100', when: showStartCustom },
		{ bind: 'end_position',   label: 'End', control: 'select',
		  options: SCROLL_POSITION_OPTIONS, defaultValue: 'bottom top', when: showScrollCustomBlock },
		{ bind: 'end_custom',     label: 'Custom End', control: 'text',
		  defaultValue: 'bottom top', placeholder: 'bottom top+=100', when: showEndCustom },

		// Invert-specific
		{ bind: 'invert_start', label: 'Invert Start', control: 'text',
		  defaultValue: 'top 85%', placeholder: 'top 85%', when: isInvert },
		{ bind: 'invert_end',   label: 'Invert End', control: 'text',
		  defaultValue: 'bottom center', placeholder: 'bottom center', when: isInvert },

		// Spin-specific (+ scroll trigger)
		{ bind: 'spin_start',  label: 'Spin Start', control: 'text',
		  defaultValue: 'top 50%', placeholder: 'top 50%', when: showSpinScrollFields },
		{ bind: 'spin_end',    label: 'Spin End', control: 'text',
		  defaultValue: 'bottom 30%', placeholder: 'bottom 30%', when: showSpinScrollFields },
		{ bind: 'spin_toggle', label: 'Toggle Actions', control: 'text',
		  defaultValue: 'play none none reverse', placeholder: 'play none none reverse', when: isSpin },

		// Numerics (responsive)
		{ bind: 'delay',       label: 'Delay',       control: 'number', defaultValue: 0.15, when: isAnimated },
		{ bind: 'duration',    label: 'Duration',    control: 'number', defaultValue: 1,    when: isDurationEffect },
		{ bind: 'stagger',     label: 'Stagger',     control: 'number', defaultValue: 0.02, when: isDurationEffect },
		{ bind: 'translate_x', label: 'Transform-X', control: 'number', defaultValue: 20,   when: isTranslateEffect },
		{ bind: 'translate_y', label: 'Transform-Y', control: 'number', defaultValue: 0,    when: isTranslateEffect },

		{ bind: 'rotation_dir',     label: 'Rotation Direction', control: 'select',
		  options: ROTATION_DIR_OPTIONS, defaultValue: 'x', when: isMove },
		{ bind: 'rotation',         label: 'Rotation Value',     control: 'number',
		  defaultValue: -80, when: isMove },
		{ bind: 'transform_origin', label: 'Transform Origin',   control: 'text',
		  defaultValue: 'top center -50', placeholder: 'top center -50', when: isMove },

		// Scale-specific
		{ bind: 'scale_ease',  label: 'Scale Ease',     control: 'select',
		  options: SCALE_EASE_OPTIONS, defaultValue: 'back', when: isScale },
		{ bind: 'scale_num',   label: 'Scale',          control: 'number', defaultValue: 1.5, when: isScale },
		{ bind: 'scale_break', label: 'Text Break By',  control: 'select',
		  options: SCALE_BREAK_OPTIONS, defaultValue: 'lines', when: isScale },

		// Non-responsive control rows.
		{ bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
		  responsive: false, defaultValue: false, when: showEnableEditor },
		{ control: 'play-button', when: showPlayButton },
	],
};

export default config;
