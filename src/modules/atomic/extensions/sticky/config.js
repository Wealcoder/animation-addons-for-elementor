import {
	showPinTrigger,
	showCustomPinArea,
	showPinEndTrigger,
	showCustomPinEndArea,
	showPinFields,
	showCustomPin,
	showCustomPinStart,
	showCustomPinEnd,
	showEnableEditor,
	showPlayButton
} from './predicates';

const TRIGGER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom', label: 'Custom' },
];

const PIN_OPTIONS = [
	{ value: true, label: 'True' },
	{ value: false, label: 'False' },
	{ value: 'custom', label: 'Custom' },
];

const BOOLEAN_OPTIONS = [
	{ value: true, label: 'True' },
	{ value: false, label: 'False' },
];

const POSITION_OPTIONS = [
	'top top',
	'top center',
	'top bottom',

	'center top',
	'center center',
	'center bottom',

	'bottom top',
	'bottom center',
	'bottom bottom',

	'custom',
].map((v) => ({
	value: v,
	label: v.replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const config = {

	anchorKey: 'aae-section-aae-sticky',

	bindPrefix: 'aae_sticky_',

	fields: [

		/*
		|--------------------------------------------------------------------------
		| Enable Sticky
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'enable',
			label: 'Enable Sticky',
			control: 'switch',
			responsive: true,
			defaultValue: false,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin Trigger
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_trigger',
			label: 'Pin Trigger',
			control: 'select',
			options: TRIGGER_OPTIONS,
			defaultValue: 'default',
			when: showPinTrigger,
			tab: 'Content', // Goes to Content tab
		},

		{
			bind: 'custom_pin_area',
			label: 'Custom Pin Area',
			control: 'text',
			defaultValue: '',
			when: showCustomPinArea,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin End Trigger
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_end_trigger',
			label: 'Pin End Trigger',
			control: 'select',
			options: TRIGGER_OPTIONS,
			defaultValue: 'default',
			when: showPinEndTrigger,
			tab: 'Content', // Goes to Content tab
		},

		{
			bind: 'custom_pin_end_area',
			label: 'Custom Pin End Area',
			control: 'text',
			defaultValue: '',
			when: showCustomPinEndArea,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin',
			label: 'Pin',
			control: 'select',
			options: PIN_OPTIONS,
			defaultValue: true,
			when: showPinFields,
			tab: 'Content', // Goes to Content tab
		},

		{
			bind: 'custom_pin',
			label: 'Custom Pin',
			control: 'text',
			defaultValue: '',
			when: showCustomPin,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin Start
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_start',
			label: 'Pin Start',
			control: 'select',
			options: POSITION_OPTIONS,
			defaultValue: 'top top',
			when: showPinFields,
			tab: 'Content', // Goes to Content tab
		},

		{
			bind: 'custom_pin_start',
			label: 'Custom Pin Start',
			control: 'text',
			defaultValue: '',
			when: showCustomPinStart,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin End
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_end',
			label: 'Pin End',
			control: 'select',
			options: POSITION_OPTIONS,
			defaultValue: 'bottom bottom',
			when: showPinFields,
			tab: 'Content', // Goes to Content tab
		},

		{
			bind: 'custom_pin_end',
			label: 'Custom Pin End',
			control: 'text',
			defaultValue: '',
			when: showCustomPinEnd,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin Spacing
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_spacing',
			label: 'Pin Spacing',
			control: 'select',
			options: BOOLEAN_OPTIONS,
			defaultValue: true,
			when: showPinFields,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Pin Markers
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'pin_markers',
			label: 'Pin Markers',
			control: 'switch',
			responsive: false,
			defaultValue: false,
			when: showPinFields,
			tab: 'Content', // Goes to Content tab
		},

		/*
		|--------------------------------------------------------------------------
		| Toggle Class
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'toggle_class',
			label: 'Toggle Class',
			control: 'text',
			responsive: true,
			defaultValue: '',
			placeholder: 'class-selector',
			when: showPinFields,
			tab: 'Style',
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
			when: showPinFields,
			tab: 'Style',
		},	

		/*
		|--------------------------------------------------------------------------
		| Background Color
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'bg_color',
			label: 'Background Color',
			control: 'color',
			responsive: true,
			defaultValue: '',
			when: showPinFields,
			tab: 'Style',
		},


		/*
		|--------------------------------------------------------------------------
		| Editor
		|--------------------------------------------------------------------------
		*/

		{
			bind: 'enable_editor',
			label: 'Enable On Editor',
			control: 'switch',
			responsive: false,
			defaultValue: false,
			when: showEnableEditor,
		},
		{
			control: 'play-button',
			when: showPlayButton,
		},
	],
};

export default config;