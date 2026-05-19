import {
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

		},

		/*
		|--------------------------------------------------------------------------
		| Width Preset
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'width',

			label: 'Width',

			control: 'dimension',

			defaultValue: '120px',

			datalist: ['80', '100', '120', '150', '200'],
			

		},

		
		

		/*
		|--------------------------------------------------------------------------
		| Height Preset
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'height',

			label: 'Height',

			control: 'dimension',

			defaultValue: '120px',

			datalist: ['80', '100', '120', '150', '200'],			

		},		

		/*
		|--------------------------------------------------------------------------
		| Border
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'border',

			label: 'Border',

			control: 'text',

			defaultValue:
				'1px solid #ffffff',

		},

		/*
		|--------------------------------------------------------------------------
		| Border Radius
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'border_radius',

			label: 'Border Radius',

			control: 'dimensions',

			defaultValue: '100%',

		},

		{
			bind: 'enable_editor',

			label: 'Enable on Editor',

			control: 'switch',

			responsive: false,

			defaultValue: false,
		},

		{
			control: 'play-button',
			when: showPlayButton,
			play_group: 'aae_cursor_hover_',
		},
	],
};

export default config;