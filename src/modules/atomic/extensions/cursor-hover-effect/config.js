import {
	isEnabledOnEditor,
	showCustomWidth,
	showCustomHeight,
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
			bind: 'enable_editor',

			label: 'Enable on Editor',

			control: 'switch',

			responsive: false,

			defaultValue: false,
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

			control: 'select',

			defaultValue: '100px',

			options: [

				{
					label: '80px',
					value: '80px',
				},

				{
					label: '100px',
					value: '100px',
				},

				{
					label: '150px',
					value: '150px',
				},

				{
					label: '200px',
					value: '200px',
				},

				{
					label: 'Custom',
					value: 'custom',
				},
			],

		},

		/*
		|--------------------------------------------------------------------------
		| Width Custom
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'width_custom',

			label: 'Custom Width',

			control: 'dimension',

			defaultValue: '120px',

			when: showCustomWidth,
		},

		/*
		|--------------------------------------------------------------------------
		| Height Preset
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'height',

			label: 'Height',

			control: 'select',

			defaultValue: '100px',

			options: [

				{
					label: '80px',
					value: '80px',
				},

				{
					label: '100px',
					value: '100px',
				},

				{
					label: '150px',
					value: '150px',
				},

				{
					label: '200px',
					value: '200px',
				},

				{
					label: 'Custom',
					value: 'custom',
				},
			],

		},

		/*
		|--------------------------------------------------------------------------
		| Height Custom
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'height_custom',

			label: 'Custom Height',

			control: 'dimension',

			defaultValue: '120px',

			when: showCustomHeight,
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
	],
};

export default config;