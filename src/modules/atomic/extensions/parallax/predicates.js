/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/** True when parallax is enabled at the active breakpoint (with cascade). */
export function isParallaxEnabled(s, bp) {
	return valueAt(s, 'aae_plx_enable', bp) === true;
}

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function showPlayButton(s, bp) {
	return isParallaxEnabled(s, bp) && plainBool(s, 'aae_plx_enable_editor');
}
