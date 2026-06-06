import { isEnabled } from './predicates';

const config = {

	anchorKey: 'aae-section-aae-custom-css-anchor',

	bindPrefix: 'aae_custom_css_',

	fields: [
		{
			bind: 'enable',
			label: 'Enable Custom CSS',
			control: 'switch',
			responsive: true,
			defaultValue: false,
			play_group: 'aae_custom_css_',
		},

		{
			bind: 'css',
			label: 'Custom CSS',
			control: 'code',            
            responsive: true,
            description: 'Use "selector" to target this element specifically.',
			defaultValue: '',
			when: isEnabled,
			play_group: 'aae_custom_css_',
		},
	],
};

export default config;
