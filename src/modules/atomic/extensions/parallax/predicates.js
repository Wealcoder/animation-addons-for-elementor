/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/** True when parallax is enabled at the active breakpoint (with cascade). */
export function isParallaxEnabled(s, bp) {
	return valueAt(s, 'aae_plx_enable', bp) === true;
}
