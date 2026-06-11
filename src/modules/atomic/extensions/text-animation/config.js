/* eslint-env browser */
import {
	isAnimated,
	isScrollTrigger,
	isDurationEffect,
	isInvert,
	isMove,
	isScale,
	isTranslateEffect,
	showEnableEditor,
	showPlayButton,
	showScrollCustomBlock,
	showScrollPosition,
	showStartCustom,

	showTriggerSelector,
	showWrapper,
	showTriggerDropdown,
	showDelay,
	isPremiumEffect,
	isMoveOrPremium,
	showTextShadow
} from './predicates';
import { PREMIUM_EFFECTS } from './presets';

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
	...Object.keys(PREMIUM_EFFECTS).map(key => ({
		value: key.toLowerCase().replace(/[^a-z0-9]+/g, '_'),
		label: key
	}))
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
	'top top', 'top center', 'top bottom', 'top 25%', 'top 50%', 'top 75%', 'top 100%','top=+200px','top=+500px','top=30%','top=-200px','top=-500px','top=-30%',
	'center top', 'center center', 'center bottom', 'center 25%', 'center 50%', 'center 75%', 'center 100%','center=+200px','center=+500px','center=30%','center=-200px','center=-500px','center=-30%',
	'bottom top', 'bottom center', 'bottom bottom', 'bottom 25%', 'bottom 50%', 'bottom 75%', 'bottom 100%','bottom=+200px','bottom=+500px','bottom=30%','bottom=-200px','bottom=-500px','bottom=-30%'
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const TRANSFORM_ORIGIN_OPTIONS = [
	'top left', 'top center', 'top right',
	'center left', 'center center', 'center right',
	'bottom left', 'bottom center', 'bottom right',
	'top center -50', '50% 50% -30px', '50% 0%', 'top=50px', 'bottom=50px', 'right=50px', 'left=50px', 'top=-50px', 'bottom=-50px', 'right=-50px', 'left=-50px'
].map((v) => ({
	value: v,
	label: v.replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const EASE_OPTIONS = [
	{ value: '', label: 'Default' },
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

		{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'in-view', when: showTriggerDropdown, responsive: true },
		

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
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'top top', when: showScrollPosition, responsive: true
		},
		
		{
			bind: 'end_position', label: 'End', control: 'text',
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'bottom top', when: showScrollPosition, responsive: true
		},		

		// Invert-specific
		{
			bind: 'invert_start', label: 'Start', control: 'text',
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'top 85%', placeholder: 'top 85%', when: isInvert, responsive: true
		},
		{
			bind: 'invert_end', label: 'End', control: 'text',
			datalist: SCROLL_POSITION_OPTIONS, defaultValue: 'bottom center', placeholder: 'bottom center', when: isInvert, responsive: true
		},

		{
			bind: 'delay',
			label: 'Delay',
			control: 'slider',
			min: 0,
			max: 10,
			step: 0.05,
			defaultValue: 0.15,
			when: showDelay, responsive: true
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
		{ bind: 'stagger', label: 'Stagger', control: 'stagger', min: -1, max: 100, step: 0.01, defaultValue: 0.02, when: isDurationEffect, responsive: true },
		{
			bind: 'ease', label: 'Easing', control: 'select', options: EASE_OPTIONS, defaultValue: '', when: isPremiumEffect, responsive: true
		},
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
			datalist: TRANSFORM_ORIGIN_OPTIONS, defaultValue: '', placeholder: 'top center -50', when: isMoveOrPremium, responsive: true
		},
		{
			bind: 'text_shadow', label: 'Text Shadow', control: 'text_shadow',
			defaultValue: '', placeholder: '-30px 20px 0px rgba(0,255,255,0.5)', when: showTextShadow, responsive: true
		},

		// Scale-specific
		{
			bind: 'scale_ease', label: 'Scale Ease', control: 'select',
			options: EASE_OPTIONS, defaultValue: 'back', when: isScale, responsive: true
		},
		{ bind: 'scale_num', label: 'Scale', control: 'number', defaultValue: 1.5, when: isScale, responsive: true },
		{
			bind: 'scale_break', label: 'Text Break By', control: 'select',
			options: SCALE_BREAK_OPTIONS, defaultValue: 'lines', when: isScale, responsive: true
		},

		// Non-responsive control rows.
		{
			bind: 'markers', label: 'Markers', control: 'switch',
			responsive: false, defaultValue: false, when: isScrollTrigger
		},	
		{
			bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
			responsive: false, defaultValue: false, when: showEnableEditor
		},
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_text_' },
	],
};

export default config;
