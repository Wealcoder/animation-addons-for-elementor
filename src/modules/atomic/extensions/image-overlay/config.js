/* eslint-env browser */

import {
	isEnabled,
	isColorType,
	isGradientType,
	showPlayButton,
} from './predicates';

const TYPE_OPTIONS = [
	{ value: 'color', label: 'Color' },
	{ value: 'gradient', label: 'Gradient' },
];

// Matches Elementor core's own `mix-blend-mode` style-schema enum
// (Style_Schema::get_effects_props()) so the choices read the same way
// they would under the native Background/Effects sections.
const BLEND_MODE_OPTIONS = [
	{ value: 'normal', label: 'Normal' },
	{ value: 'multiply', label: 'Multiply' },
	{ value: 'screen', label: 'Screen' },
	{ value: 'overlay', label: 'Overlay' },
	{ value: 'darken', label: 'Darken' },
	{ value: 'lighten', label: 'Lighten' },
	{ value: 'color-dodge', label: 'Color Dodge' },
	{ value: 'color-burn', label: 'Color Burn' },
	{ value: 'hard-light', label: 'Hard Light' },
	{ value: 'soft-light', label: 'Soft Light' },
	{ value: 'difference', label: 'Difference' },
	{ value: 'exclusion', label: 'Exclusion' },
	{ value: 'hue', label: 'Hue' },
	{ value: 'saturation', label: 'Saturation' },
	{ value: 'color', label: 'Color' },
	{ value: 'luminosity', label: 'Luminosity' },
];

const PLAY_GROUP = 'aae_img_ovl_';

const config = {

	anchorKey: 'aae-section-aae-image-overlay',

	bindPrefix: PLAY_GROUP,

	fields: [
		{
			bind: 'enable',
			label: 'Enable Overlay',
			control: 'switch',
			responsive: true,
			defaultValue: false,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'type',
			label: 'Overlay Type',
			control: 'select',
			options: TYPE_OPTIONS,
			responsive: true,
			defaultValue: 'color',
			when: isEnabled,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'color',
			label: 'Color',
			control: 'color',
			responsive: true,
			defaultValue: '#000000',
			when: isColorType,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'gradient_color_1',
			label: 'Gradient Color 1',
			control: 'color',
			responsive: true,
			defaultValue: '#000000',
			when: isGradientType,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'gradient_color_2',
			label: 'Gradient Color 2',
			control: 'color',
			responsive: true,
			defaultValue: '#ffffff',
			when: isGradientType,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'gradient_angle',
			label: 'Gradient Angle',
			control: 'slider',
			responsive: true,
			defaultValue: 180,
			min: 0,
			max: 360,
			when: isGradientType,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'opacity',
			label: 'Opacity',
			control: 'slider',
			responsive: true,
			defaultValue: 50,
			min: 0,
			max: 100,
			when: isEnabled,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		{
			bind: 'blend_mode',
			label: 'Blend Mode',
			control: 'select',
			options: BLEND_MODE_OPTIONS,
			responsive: true,
			defaultValue: 'multiply',
			when: isEnabled,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		// Editor-only controls (non-responsive).
		{
			bind: 'enable_editor',
			label: 'Enable in Editor',
			control: 'switch',
			defaultValue: false,
			responsive: false,
			when: isEnabled,
		},
		{
			control: 'play-button',
			when: showPlayButton,
			play_group: PLAY_GROUP,
		},
	],
};

export default config;
