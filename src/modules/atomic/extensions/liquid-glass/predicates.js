/* eslint-env browser */

import { valueAt, plainBool } from '../../responsive-section/helpers';

export function isEnabled(s, bp) {
	return valueAt(s, 'aae_lg_enable', bp) === true;
}

export function showPlayButton(s, bp) {
	return isEnabled(s, bp) && plainBool(s, 'aae_lg_enable_editor');
}
