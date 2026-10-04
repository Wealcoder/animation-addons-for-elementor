/* eslint-env browser */

import { valueAt, plainBool } from '../../responsive-section/helpers';

export function isEnabled(s, bp) {
	return valueAt(s, 'aae_img_ovl_enable', bp) === true;
}

export function isColorType(s, bp) {
	if (!isEnabled(s, bp)) return false;
	const type = valueAt(s, 'aae_img_ovl_type', bp);
	return !type || type === 'color';
}

export function isGradientType(s, bp) {
	return isEnabled(s, bp) && valueAt(s, 'aae_img_ovl_type', bp) === 'gradient';
}

export function showPlayButton(s, bp) {
	return isEnabled(s, bp) && plainBool(s, 'aae_img_ovl_enable_editor');
}
