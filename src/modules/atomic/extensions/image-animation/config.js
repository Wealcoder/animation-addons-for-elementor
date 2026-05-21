/* eslint-env browser */

import {
	isAnimated,
	isReveal,
	isRevealOrScale,
	isScale,
	showCustomStart,
	showEnableEditor,
	showPlayButton,
} from './predicates';

/**
 * Declarative table for the Image Animation section. Same structure as the
 * other extensions — see regular-animation/config.js for full notes on the
 * row shape and rendering path.
 *
 * Targets: e-image, e-svg (gated PHP-side in Schema::image_animation_widgets).
 */

const EFFECT_OPTIONS = [
	{ value: 'none',    label: 'None' },
	{ value: 'reveal',  label: 'Reveal' },
	{ value: 'scale',   label: 'Scale' },
	{ value: 'stretch', label: 'Stretch' },
];

const START_FROM_OPTIONS = [
	{ value: 'left',   label: 'Left' },
	{ value: 'right',  label: 'Right' },
	{ value: 'top',    label: 'Top' },
	{ value: 'bottom', label: 'Bottom' },
];

const START_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
	'custom',
].map((v) => ({
	value: v,
	label: v.replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce',     label: 'Bounce' },
	{ value: 'back',       label: 'Back' },
	{ value: 'elastic',    label: 'Elastic' },
	{ value: 'slowmo',     label: 'Slowmo' },
	{ value: 'stepped',    label: 'Stepped' },
	{ value: 'sine',       label: 'Sine' },
	{ value: 'expo',       label: 'Expo' },
];

const config = {
	anchorKey:  'aae-section-aae-image-animation',
	bindPrefix: 'aae_img_',
	fields: [
		{ bind: 'effect', label: 'Animation', control: 'select',
		  options: EFFECT_OPTIONS, defaultValue: 'none' },

		// Reveal-only
		{ bind: 'start_from', label: 'Animation To', control: 'select',
		  options: START_FROM_OPTIONS, defaultValue: 'right', when: isReveal },
		{ bind: 'ease', label: 'Ease', control: 'select',
		  options: EASE_OPTIONS, defaultValue: 'power2.out', when: isReveal },

		// Scale-only
		{ bind: 'scale_start', label: 'Start Scale', control: 'number',
		  defaultValue: 0.5, when: isScale },
		{ bind: 'scale_end',   label: 'End Scale',   control: 'number',
		  defaultValue: 1,   when: isScale },

		// Reveal + Scale share the start position picker
		{ bind: 'start_pos', label: 'Animation Start', control: 'select',
		  options: START_POSITION_OPTIONS, defaultValue: 'top center',
		  when: isRevealOrScale },
		{ bind: 'custom_start', label: 'Custom', control: 'text',
		  defaultValue: 'top 90%', placeholder: 'top 90%',
		  when: showCustomStart },

		// Non-responsive editor toggle + Play Now button
		{ bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
		  responsive: false, defaultValue: false, when: showEnableEditor },
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_img_' },
	],
};

export default config;
