/* eslint-env browser */
import {
	isAnimated,
	isScrollTrigger,
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
	showWrapper
} from './predicates';

/**
 * Declarative table for the Text Animation section. Same structure as
 * regular-animation/config.js — see that file for full notes.
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
	{ value: 'text_spin', label: '3D Spin' },
];

const TRIGGER_OPTIONS = [
	{ value: 'in-view', label: 'In View' },
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'click', label: 'On Click' },
	{ value: 'mouseover', label: 'On Hover' },

];

const WRAPPER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom', label: 'Custom' },
];

const ROTATION_DIR_OPTIONS = [
	{ value: 'x', label: 'X' },
	{ value: 'y', label: 'Y' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom'
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const SCALE_EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce', label: 'Bounce' },
	{ value: 'back', label: 'Back' },
	{ value: 'elastic', label: 'Elastic' },
	{ value: 'slowmo', label: 'Slowmo' },
	{ value: 'stepped', label: 'Stepped' },
	{ value: 'sine', label: 'Sine' },
	{ value: 'expo', label: 'Expo' },
];

const SCALE_BREAK_OPTIONS = [
	{ value: 'lines', label: 'Lines' },
	{ value: 'words', label: 'Words' },
	{ value: 'chars', label: 'Chars' },
];

/* ---------- the table ---------- */

const config = {
	anchorKey: 'aae-section-aae-text-animation',
	bindPrefix: 'aae_text_',
	fields: [
		{ bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'none', play_group: 'aae_text_', responsive: true },

		{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'in-view', when: isAnimated, responsive: true },
		

		{
			bind: 'trigger_selector', label: 'Trigger Selector', control: 'text',
			defaultValue: '', placeholder: '.my-class', when: showTriggerSelector, responsive: true
		},

		{ bind: 'wrapper', label: 'Text Wrapper', control: 'select', options: WRAPPER_OPTIONS, defaultValue: 'default', when: showWrapper, responsive: true },

		{
			bind: 'start_trigger', label: 'Start Trigger', control: 'text',
			defaultValue: '', placeholder: '.start_area', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'end_trigger', label: 'End Trigger', control: 'text',
			defaultValue: '', placeholder: '.end_area', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'start_position', label: 'Start', control: 'text',
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'top top', when: showScrollCustomBlock, responsive: true
		},
		
		{
			bind: 'end_position', label: 'End', control: 'text',
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'bottom top', when: showScrollCustomBlock, responsive: true
		},		

		// Invert-specific
		{
			bind: 'invert_start', label: 'Invert Start', control: 'text',
			defaultValue: 'top 85%', placeholder: 'top 85%', when: isInvert, responsive: true
		},
		{
			bind: 'invert_end', label: 'Invert End', control: 'text',
			defaultValue: 'bottom center', placeholder: 'bottom center', when: isInvert, responsive: true
		},

		// Spin-specific (+ scroll trigger)
		{
			bind: 'spin_start', label: 'Spin Start', control: 'text',
			defaultValue: 'top 85%', placeholder: 'top 85%', when: showSpinScrollFields, responsive: true
		},
		{
			bind: 'spin_end', label: 'Spin End', control: 'text',
			defaultValue: 'bottom 30%', placeholder: 'bottom 30%', when: showSpinScrollFields, responsive: true
		},
		{
			bind: 'spin_toggle', label: 'Toggle Actions', control: 'text',
			defaultValue: 'play none none reverse', placeholder: 'play none none reverse', when: isSpin, responsive: true
		},

		{
			bind: 'delay',
			label: 'Delay',
			control: 'slider',
			min: 0,
			max: 10,
			step: 0.05,
			defaultValue: 0.15,
			when: isAnimated, responsive: true
		},
		{
			bind: 'duration',
			label: 'Duration',
			control: 'slider',
			min: 0,
			max: 10,
			step: 0.1,
			defaultValue: 1,
			when: isDurationEffect, responsive: true
		},
		{ bind: 'stagger', label: 'Stagger', control: 'number', defaultValue: 0.02, when: isDurationEffect, responsive: true },
		{ bind: 'translate_x', label: 'Transform-X', control: 'number', defaultValue: 20, when: isTranslateEffect, responsive: true },
		{ bind: 'translate_y', label: 'Transform-Y', control: 'number', defaultValue: 0, when: isTranslateEffect, responsive: true },

		{
			bind: 'rotation_dir', label: 'Rotation Direction', control: 'select',
			options: ROTATION_DIR_OPTIONS, defaultValue: 'x', when: isMove, responsive: true
		},
		{
			bind: 'rotation', label: 'Rotation Value', control: 'number',
			defaultValue: -80, when: isMove, responsive: true
		},
		{
			bind: 'transform_origin', label: 'Transform Origin', control: 'text',
			defaultValue: 'top center -50', placeholder: 'top center -50', when: isMove, responsive: true
		},

		// Scale-specific
		{
			bind: 'scale_ease', label: 'Scale Ease', control: 'select',
			options: SCALE_EASE_OPTIONS, defaultValue: 'back', when: isScale, responsive: true
		},
		{ bind: 'scale_num', label: 'Scale', control: 'number', defaultValue: 1.5, when: isScale, responsive: true },
		{
			bind: 'scale_break', label: 'Text Break By', control: 'select',
			options: SCALE_BREAK_OPTIONS, defaultValue: 'lines', when: isScale, responsive: true
		},

		{
			bind: 'spin_color', label: 'Spin Text Color', control: 'color',
			defaultValue: '', when: isSpin, responsive: true
		},

		{
			bind: 'markers', label: 'Markers', control: 'switch',
			responsive: false, defaultValue: false, when: isScrollTrigger
		},

		// Non-responsive control rows.
		{
			bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
			responsive: false, defaultValue: false, when: showEnableEditor
		},
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_text_' },
	],
};

export default config;
