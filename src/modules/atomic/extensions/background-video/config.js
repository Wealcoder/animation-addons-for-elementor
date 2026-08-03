import { isBgVideoEnabled, showBgVideoFile, showBgVideoLink } from './predicates';

/**
 * Background Video — panel fields for e-flexbox / e-div-block / e-grid.
 *
 * The atomic Background style control has no video option (colour / image /
 * gradient only), so this section adds one. Field set mirrors v3's
 * Group_Control_Background video fields.
 *
 * Enable is the only non-responsive field — it is the feature's on/off. Every
 * other row is per-breakpoint, so a heavy clip can be swapped for a lighter one
 * (or dropped entirely) on smaller screens.
 */

const SOURCE_OPTIONS = [
	{ value: 'file', label: 'Media File' },
	{ value: 'url', label: 'External URL' },
];

const config = {
	anchorKey: 'aae-section-aae-background-video',
	bindPrefix: 'aae_bgv_',
	fields: [
		{
			bind: 'enable',
			label: 'Enable',
			control: 'switch',
			responsive: false,
			defaultValue: false,
		},

		{
			bind: 'source',
			label: 'Source',
			control: 'select',
			options: SOURCE_OPTIONS,
			defaultValue: 'file',
			when: isBgVideoEnabled,
		},

		{
			bind: 'file',
			label: 'Video File',
			control: 'media',
			mediaType: 'video',
			defaultValue: null,
			when: showBgVideoFile,
			help: 'mp4 is the safest choice — webm/ogg are not supported everywhere.',
		},

		{
			bind: 'link',
			label: 'Video URL',
			control: 'text',
			defaultValue: '',
			when: showBgVideoLink,
			help: 'Direct link to a video file (mp4).',
		},

		{
			bind: 'poster',
			label: 'Poster Image',
			control: 'media',
			defaultValue: null,
			when: isBgVideoEnabled,
			help: 'Shown until the video can play, and instead of it on devices that skip playback.',
		},

		{
			bind: 'play_once',
			label: 'Play Once',
			control: 'switch',
			defaultValue: false,
			when: isBgVideoEnabled,
		},

		{
			bind: 'play_on_mobile',
			label: 'Play On Mobile',
			control: 'switch',
			defaultValue: false,
			when: isBgVideoEnabled,
			help: 'Off by default — autoplaying video is costly on mobile data. The poster shows instead.',
		},
	],
};

export default config;
