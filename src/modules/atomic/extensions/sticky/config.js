// /* eslint-env browser */

// import {
// 	showPinTrigger,
// 	showCustomPinArea,
// 	showPinEndTrigger,
// 	showCustomPinEndArea,
// } from './predicates';

// const TRIGGER_OPTIONS = [
// 	{ value: 'default', label: 'Default' },
// 	{ value: 'custom', label: 'Custom' },
// ];

// const config = {
// 	anchorKey: 'aae-section-aae-sticky',
// 	bindPrefix: 'aae_sticky_',

// 	fields: [

// 		{
// 			bind: 'enable',
// 			label: 'Enable Sticky',
// 			control: 'switch',
// 			responsive: false,
// 			defaultValue: false,
// 		},

// 		{
// 			bind: 'pin_trigger',
// 			label: 'Pin Trigger',
// 			control: 'select',
// 			options: TRIGGER_OPTIONS,
// 			defaultValue: 'default',
// 			when: showPinTrigger,
// 		},

// 		{
// 			bind: 'custom_pin_area',
// 			label: 'Custom Pin Area',
// 			control: 'text',
// 			defaultValue: '',
// 			when: showCustomPinArea,
// 		},

// 		{
// 			bind: 'pin_end_trigger',
// 			label: 'Pin End Trigger',
// 			control: 'select',
// 			options: TRIGGER_OPTIONS,
// 			defaultValue: 'default',
// 			when: showPinEndTrigger,
// 		},

// 		{
// 			bind: 'custom_pin_end_area',
// 			label: 'Custom Pin End Area',
// 			control: 'text',
// 			defaultValue: '',
// 			when: showCustomPinEndArea,
// 		},
// 	],
// };

// export default config;

/* eslint-env browser */

import {
	showPinTrigger,
	showCustomPinArea,
	showPinEndTrigger,
	showCustomPinEndArea,
	showPinFields,
	showCustomPin,
	showCustomPinStart,
	showCustomPinEnd,
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
			responsive: false,
			defaultValue: false,
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
		},

		{
			bind: 'custom_pin_area',
			label: 'Custom Pin Area',
			control: 'text',
			defaultValue: '',
			when: showCustomPinArea,
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
		},

		{
			bind: 'custom_pin_end_area',
			label: 'Custom Pin End Area',
			control: 'text',
			defaultValue: '',
			when: showCustomPinEndArea,
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
		},

		{
			bind: 'custom_pin',
			label: 'Custom Pin',
			control: 'text',
			defaultValue: '',
			when: showCustomPin,
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
		},

		{
			bind: 'custom_pin_start',
			label: 'Custom Pin Start',
			control: 'text',
			defaultValue: '',
			when: showCustomPinStart,
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
		},

		{
			bind: 'custom_pin_end',
			label: 'Custom Pin End',
			control: 'text',
			defaultValue: '',
			when: showCustomPinEnd,
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
		},
	],
};

export default config;