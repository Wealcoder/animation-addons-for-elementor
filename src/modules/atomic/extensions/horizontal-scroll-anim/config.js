/* eslint-env browser */

import {
	isEnabled,
	showCustomWidth,
	showCustomEnd,
} from './predicates';

const WIDTH_OPTIONS = [

	{ value: '25%', label: '25%' },
	{ value: '50%', label: '50%' },
	{ value: '75%', label: '75%' },
	{ value: '100%', label: '100%' },

	{ value: 'custom', label: 'Custom' },
];
const config = {

	anchorKey:
		'aae-section-aae-horizontal-scroll-anim',

	bindPrefix:
		'aae_horizontal_scroll_anim_',

	fields: [

		{
			bind: 'enable',

			label: 'Enable',

			control: 'switch',

			responsive: false,

			defaultValue: false,
		},

		{
			bind: 'width',

			label: 'Width',

			control: 'select',

			options: WIDTH_OPTIONS,

			defaultValue: '100%',

			when: isEnabled,
		},

		{
			bind: 'width_custom',

			label: 'Custom Width',

			control: 'text',

			defaultValue: '50%',

			when: showCustomWidth,
		},

		{
			bind: 'end',

			label: 'End',

			control: 'select',

			options: WIDTH_OPTIONS,

			defaultValue: '100%',

			when: isEnabled,
		},

		{
			bind: 'end_custom',

			label: 'Custom End',

			control: 'text',

			defaultValue: '50%',

			when: showCustomEnd,
		},

	],
};

export default config;