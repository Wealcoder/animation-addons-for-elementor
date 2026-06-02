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

const START_OPTIONS = [
	'top top', 'top center', 'top bottom', 'top 80px', 'top 10%', 'top 20%', 'top 30%', 'top 40%', 'top 50%', 'top 60%', 'top 70%', 'top 80%', 'top 90%', 'top 100%',
	'center top', 'center center', 'center bottom', 'center 80px', 'center 10%', 'center 20%', 'center 30%', 'center 40%', 'center 50%', 'center 60%', 'center 70%', 'center 80%', 'center 90%', 'center 100%',
	'bottom top', 'bottom center', 'bottom bottom', 'bottom 80px', 'bottom 10%', 'bottom 20%', 'bottom 30%', 'bottom 40%', 'bottom 50%', 'bottom 60%', 'bottom 70%', 'bottom 80%', 'bottom 90%', 'bottom 100%',
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
			help: 'Enable horizontal scroll for the section , Set Child elements width'
		},

		{
			bind: 'width',

			label: 'Width',

			control: 'text',

			datalist: WIDTH_OPTIONS,

			defaultValue: '300%',
			help: 'Set the total width of the section for horizontal scroll. Default is 300%',

			when: isEnabled,
		},
		{
			bind: 'start',

			label: 'Start',

			control: 'text',

			datalist: START_OPTIONS,

			defaultValue: 'top top',

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
		
		{ control: 'save-button', when: isEnabled, play_group: 'aae_horizontal_' },

	],
};

export default config;