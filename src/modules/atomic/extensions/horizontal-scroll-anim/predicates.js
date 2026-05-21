/* eslint-env browser */



/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/** True when parallax is enabled at the active breakpoint (with cascade). */
export function isEnabled(s, bp) {
	return valueAt(s, 'aae_horizontal_enable', bp) === true;
}

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

