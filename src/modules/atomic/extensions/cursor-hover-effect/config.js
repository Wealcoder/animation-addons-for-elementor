import {
	isEnabled,
	showPlayButton,
} from './predicates';

const config = {

	anchorKey:
		'aae-section-aae-cursor-hover-effect-anchor',

	bindPrefix:
		'aae_cursor_hover_',

	fields: [

		/*
		|--------------------------------------------------------------------------
		| Enable
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'enable',

			label: 'Enable',

			control: 'switch',

			responsive: true,

			defaultValue: false,
			play_group: 'aae_cursor_hover_',
			tab: 'content',
		
		},
		/*
		|--------------------------------------------------------------------------
		| Text
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'text',

			label: 'Text',

			control: 'text',

			defaultValue: '',

			when: isEnabled,
			tab: 'content',
		},

		/*
|--------------------------------------------------------------------------
| Font Size
|--------------------------------------------------------------------------
*/
		{
			bind: 'font_size',

			label: 'Font Size',

			control: 'slider',

			units: ['px', 'em', 'rem'],

			defaultValue: {
				size: 16,
				unit: 'px',
			},

			when: isEnabled,
			tab: 'style',

		},
		/*
		|--------------------------------------------------------------------------
		| Text Color
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'color',

			label: 'Text Color',

			control: 'color',

			defaultValue: '#ffffff',

			when: isEnabled,
			tab: 'style',
		},

		/*
		|--------------------------------------------------------------------------
		| Background
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'background',

			label: 'Background',

			control: 'color',

			defaultValue: '#000000',

			when: isEnabled,
			tab: 'style',

		},

		/*
		|--------------------------------------------------------------------------
		| Width Preset
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'width',

			label: 'Width',

			control: 'text',

			defaultValue: '120px',

			when: isEnabled,
			tab: 'style',

		},

		/*
		|--------------------------------------------------------------------------
		| Height Preset
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'height',

			label: 'Height',

			control: 'text',

			defaultValue: '120px',

			datalist: ['80', '100', '120', '150', '200'],

			when: isEnabled,
			tab: 'style',

		},

		/*
		|--------------------------------------------------------------------------
		| Padding
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'padding',

			label: 'Padding',

			control: 'dimensions',

			defaultValue: '',

			when: isEnabled,
			tab: 'style',

		},

		/*
		|--------------------------------------------------------------------------
		| Border
		|--------------------------------------------------------------------------
		*/
		{
			bind: 'border',
			label: 'Border',
			control: 'border',
			responsive: true,
			defaultValue: {
				style: '',
				width: { top: '', right: '', bottom: '', left: '' },
				color: '',
				radius: '',
			},
			when: isEnabled,
			tab: 'style',

		},

		{
			bind: 'enable_editor',

			label: 'Enable on Editor',

			control: 'switch',

			responsive: false,

			defaultValue: false,

			when: isEnabled,
		},

		{
			control: 'play-button',
			when: showPlayButton,
			play_group: 'aae_cursor_hover_',
		},
	],

};

export const play = (hoverEffect) => {
	hoverEffect.play();
};

export default config;