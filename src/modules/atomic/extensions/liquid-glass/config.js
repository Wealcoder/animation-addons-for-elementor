/* eslint-env browser */

import { isEnabled, showPlayButton } from './predicates';

const PRESET_OPTIONS = [
	{ value: 'frosted', label: 'Frosted' },
	{ value: 'liquid-lens', label: 'Liquid Lens' },
	{ value: 'ripple', label: 'Ripple' },
	{ value: 'crystal', label: 'Crystal' },
	{ value: 'wave', label: 'Wave' },
	{ value: 'prism', label: 'Prism' },
	{ value: 'soft-blur', label: 'Soft Blur (no distortion)' },
	{ value: 'jelly', label: 'Jelly' },
];

const SHADOW_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'soft-glow', label: 'Soft Glow' },
	{ value: 'bright-glow', label: 'Bright Glow' },
	{ value: 'wide-bloom', label: 'Wide Bloom' },
	{ value: 'top-light', label: 'Top Light' },
	{ value: 'rim-light', label: 'Rim Light' },
	{ value: 'inner-shadow', label: 'Inner Shadow' },
];

const PLAY_GROUP = 'aae_lg_';

// Every row repaints the canvas live (live_change) so the glass can be tuned
// by eye; the distortion needs Chromium, other browsers show the blur only.
const row = (bind, label, control, defaultValue, extra = {}) => ({
	bind,
	label,
	control,
	responsive: true,
	defaultValue,
	when: isEnabled,
	live_change: true,
	play_group: PLAY_GROUP,
	...extra,
});

const config = {

	anchorKey: 'aae-section-aae-liquid-glass',

	bindPrefix: PLAY_GROUP,

	fields: [
		{
			bind: 'enable',
			label: 'Enable Liquid Glass',
			control: 'switch',
			responsive: true,
			defaultValue: false,
			live_change: true,
			play_group: PLAY_GROUP,
		},

		row('preset', 'Glass Style', 'select', 'frosted', { options: PRESET_OPTIONS }),
		row('blur', 'Blur (px) - 0 uses the style default', 'slider', 0, { min: 0, max: 50 }),
		row('distortion', 'Distortion (%)', 'slider', 100, { min: 0, max: 300 }),
		row('shadow', 'Glow', 'select', 'soft-glow', { options: SHADOW_OPTIONS }),
		row('border_width', 'Border Width (px)', 'slider', 1, { min: 0, max: 20 }),
		row('border_color', 'Border Color', 'color', 'rgba(255,255,255,0.35)'),
		row('radius', 'Border Radius (px)', 'slider', 0, { min: 0, max: 500 }),

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
