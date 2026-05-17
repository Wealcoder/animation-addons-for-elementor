/* eslint-env browser */

import {
	showPinTrigger,
	showCustomPinArea,
	showPinEndTrigger,
	showCustomPinEndArea,
} from './predicates';

const TRIGGER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom', label: 'Custom' },
];

const config = {
	anchorKey: 'aae-section-aae-sticky',
	bindPrefix: 'aae_sticky_',

	fields: [

		{
			bind: 'enable',
			label: 'Enable Sticky',
			control: 'switch',
			responsive: false,
			defaultValue: false,
		},

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
	],
};

export default config;