/* eslint-env browser */

import { isHoverEnabled } from './predicates';

/**
 * Image Hover (Reveal-on-Hover) responsive fields.
 *
 * The native PHP Section already renders four primitives ABOVE this
 * placeholder (Enable, Enable On Editor, Hover Image, Z-Index). This config
 * table only owns the responsive numerics — Width / Height / Top / Left —
 * which are gated on the non-responsive `aae_ih_enable` switch.
 *
 * All px implicit (no unit picker).
 */
const config = {
	anchorKey:  'aae-section-aae-image-hover',
	bindPrefix: 'aae_ih_',
	fields: [
		{ bind: 'width',  label: 'Width (px)',   control: 'number',
		  defaultValue: 300, when: isHoverEnabled },
		{ bind: 'height', label: 'Height (px)',  control: 'number',
		  defaultValue: 300, when: isHoverEnabled },
		{ bind: 'top',    label: 'Top (px)',     control: 'number',
		  defaultValue: 0,   when: isHoverEnabled },
		{ bind: 'left',   label: 'Left (px)',    control: 'number',
		  defaultValue: 0,   when: isHoverEnabled },
	],
};

export default config;
