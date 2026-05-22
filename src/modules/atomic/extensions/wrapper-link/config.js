import {
	isEnabled,
	showPlayButton,
} from './predicates';

const config = {
	anchorKey:
		'aae-section-aae-wrapper-link-play',

	bindPrefix: '',

	fields: [
		{
			bind: 'aae_wrapper_link_enable',
			label: 'Enable Wrapper Link',
			control: 'switch',
			defaultValue: false,
			responsive: false,
			tab: 'Content',
		},
		{
			bind: 'aae_wrapper_link',
			label: 'Link',
			control: 'link',
			responsive: false,
			when: isEnabled,
			tab: 'Content',
		},
		{
			bind: 'aae_wrapper_link_is_external',
			label: 'Open in new tab',
			control: 'switch',
			defaultValue: false,
			responsive: false,
			when: isEnabled,
			tab: 'Content',
		},
	],
};

export default config;
