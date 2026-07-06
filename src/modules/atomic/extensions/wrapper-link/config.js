import {
	isEnabled,
	isCustomSource
} from './predicates';

const config = {
	anchorKey:'aae-section-aae-wrapper-link',

	bindPrefix: '',

	fields: [
		{
			bind: 'aae_wrapper_link_enable',
			label: 'Enable Wrapper Link',
			control: 'switch',
			defaultValue: false,
			responsive: false,			
		},
		{
			// "Current Post" links each Loop Grid card to its own post (the
			// per-repeat URL rides the card's data-aae-post-url attribute).
			bind: 'aae_wrapper_link_source',
			label: 'Link Source',
			control: 'select',
			options: [
				{ value: 'custom', label: 'Custom URL' },
				{ value: 'post', label: 'Current Post' },
			],
			defaultValue: 'custom',
			responsive: false,
			when: isEnabled,
		},
		{
			bind: 'aae_wrapper_link',
			label: 'Link',
			control: 'link',
			responsive: false,
			when: isCustomSource,
		},
		{
			bind: 'aae_wrapper_link_is_external',
			label: 'Open in new tab',
			control: 'switch',
			defaultValue: false,
			responsive: false,
			when: isEnabled,			
		},
	],
};

export default config;
