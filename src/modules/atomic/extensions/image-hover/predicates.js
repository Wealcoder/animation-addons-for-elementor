/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/** True when image hover is enabled at the active breakpoint (with cascade). */
export function isImageHoverEnabled(s, bp) {
	return valueAt(s, 'aae_ih_enable', bp) === true;
}

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function showPlayButton(s, bp) {
	return isImageHoverEnabled(s, bp) && plainBool(s, 'aae_ih_enable_editor');
}
