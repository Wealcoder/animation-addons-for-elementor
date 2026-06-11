import { isImageHoverEnabled, showPlayButton } from './predicates';

/**
 * Image Hover (Reveal-on-Hover) responsive fields.
 *
 * The native PHP Section renders the Image picker above this anchor.
 * All other controls live here — Enable switch first (responsive),
 * then the dimensional numerics (gated on the enable switch), then
 * the editor-only controls + Play button.
 *
 * All px-values are px implicit (no unit picker).
 */

const PRESET_OPTIONS = [
	{ value: 'none',          label: 'None' },
	{ value: 'magnetic-lag',  label: 'Magnetic Lag' },
	{ value: 'spring-bounce', label: 'Spring Bounce' },
	{ value: 'tilt-3d',       label: 'Tilt 3D' },
	{ value: 'trail-ghost',   label: 'Trail Ghost' },
	{ value: 'elastic-snap',  label: 'Elastic Snap' },
];

const config = {
	anchorKey: 'aae-section-aae-image-hover',
	bindPrefix: 'aae_ih_',
	fields: [
		// Responsive enable switch — controls per-breakpoint activation.
		{
			bind: 'enable', label: 'Enable', control: 'switch',
			defaultValue: false,
			play_group: 'aae_ih_'
		},

		// Hover Image picker
		{
			bind: 'image', label: 'Hover Image', control: 'media',
			defaultValue: null, when: isImageHoverEnabled,
		},

		// Animation preset — governs hover animation behavior.
		{
			bind: 'preset', label: 'Animation Preset', control: 'select',
			options: PRESET_OPTIONS, defaultValue: 'none', when: isImageHoverEnabled,
		},

		// Dimensional numerics — shown only when enabled at this bp.
		{
			bind: 'width', label: 'Width (px)', control: 'number',
			defaultValue: 300, when: isImageHoverEnabled,
		},
		{
			bind: 'height', label: 'Height (px)', control: 'number',
			defaultValue: 300, when: isImageHoverEnabled,
		},
		{
			bind: 'top', label: 'Top (px)', control: 'number',
			defaultValue: -15, when: isImageHoverEnabled,
		},
		{
			bind: 'left', label: 'Left (px)', control: 'number',
			defaultValue: 30, when: isImageHoverEnabled,
		},
		{
			bind: 'zindex', label: 'Z-Index', control: 'number',
			defaultValue: 1, when: isImageHoverEnabled,
		},

		// Editor-only controls (non-responsive).
		{
			bind: 'enable_editor', label: 'Enable in Editor', control: 'switch',
			defaultValue: false, responsive: false, when: isImageHoverEnabled,
		},
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_ih_' },
	],
};

export default config;
