/* eslint-env browser */

import { isParallaxEnabled, showPlayButton } from './predicates';

/**
 * Declarative table for the Parallax section. Same structure as the other
 * extensions — see regular-animation/config.js for full notes on the field
 * shape and rendering path.
 *
 * All three fields are responsive (per-bp switch + numbers). Visibility of
 * Speed / Lag is gated on the Enable toggle at the active breakpoint.
 */
const config = {
	anchorKey: 'aae-section-aae-parallax',
	bindPrefix: 'aae_plx_',
	fields: [
		{
			bind: 'enable', label: 'Enable Scroll Smoother', control: 'switch',
			defaultValue: false
		},

		{
			bind: 'speed', label: 'Speed', control: 'number',
			defaultValue: 0.9, when: isParallaxEnabled, datalist: ['0.6', '0.7', '0.8', '0.9'],
		},

		{
			bind: 'lag', label: 'Lag', control: 'number',
			defaultValue: 0, when: isParallaxEnabled
		},

		{
			bind: 'enable_editor', label: 'Enable in Editor', control: 'switch',
			defaultValue: false, when: isParallaxEnabled, responsive: false
		},
		{ control: 'play-button', when: showPlayButton },
	],
};

export default config;
