import {
	isEnabled
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
			bind: 'aae_wrapper_link',
			label: 'Link',
			control: 'link',
			responsive: false,
			when: isEnabled,			
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
