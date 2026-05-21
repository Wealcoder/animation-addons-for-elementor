/* eslint-env browser */

import {
	isEnabled,	
} from './predicates';

const WIDTH_OPTIONS = [
	'200%', '300%', '400%', '500%', '600%', '700%', '800%', '900%', '1000%'
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const END_OPTIONS = [
	'2000', '3000', '4000', '5000', '6000', '7000', '8000', '9000', '10000'
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

const config = {

	anchorKey:
		'aae-section-aae-horizontal',

	bindPrefix:
		'aae_horizontal_',

	fields: [

		{
			bind: 'enable',
			label: 'Enable',
			control: 'switch',
			defaultValue: false,
		},

		{
			bind: 'width',

			label: 'Width',

			control: 'text',

			datalist: WIDTH_OPTIONS,

			defaultValue: '300%',

			when: isEnabled,
		},


		{
			bind: 'end',

			label: 'End',

			control: 'text',

			datalist: END_OPTIONS,

			defaultValue: '3000',

			when: isEnabled,
		},
		{ control: 'play-button', when: isEnabled, play_group: 'aae_horizontal_' },

	],
};

export default config;